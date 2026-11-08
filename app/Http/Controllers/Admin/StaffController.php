<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ServiceWindow;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View|Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');
        $temporaryPassword = $request->session()->pull('temporary_password');
        $response = response()->view('admin.staff', [
            'departments' => Department::query()
                ->with(['windows' => fn ($query) => $query->where('is_active', true)->with('staff')])
                ->orderBy('name')
                ->get(),
            'staffMembers' => User::query()
                ->where('role', 'staff')
                ->with(['department', 'serviceWindow'])
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhereHas('department', fn (Builder $departmentQuery) => $departmentQuery->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('full_name')
                ->paginate(15)
                ->withQueryString(),
            'temporaryPassword' => $temporaryPassword,
            'temporaryAccountName' => $request->session()->pull('temporary_account_name'),
        ]);

        return is_string($temporaryPassword) ? $response->header('Cache-Control', 'no-store, private') : $response;
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $windowId = (int) $attributes['window_id'];
        unset($attributes['window_id']);
        $attributes['role'] = 'staff';
        $attributes['email_verified_at'] = now();
        $temporaryPassword = Str::password(24, symbols: false);
        $attributes['password'] = $temporaryPassword;
        $attributes['must_change_password'] = true;

        $staff = DB::transaction(function () use ($attributes, $windowId, $request, $activityLogger): User {
            $staff = User::create($attributes);
            $this->assignWindow($staff, $windowId);
            $activityLogger->record($request->user(), 'staff.created', 'staff', $staff->id, $staff->department_id);

            return $staff;
        });

        return redirect()->route('admin.staff.index')
            ->with('status', "{$staff->full_name} was created as office staff.")
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_account_name', $staff->full_name);
    }

    public function update(Request $request, User $staff, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        $attributes = $this->validatedAttributes($request, $staff);
        $windowId = (int) $attributes['window_id'];
        unset($attributes['window_id']);
        DB::transaction(function () use ($request, $staff, $attributes, $windowId, $activityLogger): void {
            $staff->update($attributes);
            $this->assignWindow($staff, $windowId);
            $activityLogger->record($request->user(), 'staff.updated', 'staff', $staff->id, $staff->department_id);
        });

        return redirect()->route('admin.staff.index')
            ->with('status', "{$staff->full_name} was updated.");
    }

    public function resetPassword(Request $request, User $staff, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);
        $temporaryPassword = Str::password(24, symbols: false);

        DB::transaction(function () use ($request, $staff, $activityLogger, $temporaryPassword): void {
            $staff->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
            ])->save();
            $activityLogger->record(
                $request->user(),
                'staff.temporary_password_reset',
                'staff',
                $staff->id,
                $staff->department_id,
            );
        });

        return redirect()->route('admin.staff.index')
            ->with('status', "A new temporary password was issued for {$staff->full_name}.")
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_account_name', $staff->full_name);
    }

    private function validatedAttributes(Request $request, ?User $staff = null): array
    {
        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff?->id),
            ],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) use ($staff): void {
                    $query->where('is_active', true);

                    if ($staff !== null) {
                        $query->orWhere('id', $staff->department_id);
                    }
                }),
            ],
            'window_id' => [
                'required',
                'integer',
                Rule::exists('service_windows', 'id')->where(function ($query) use ($request): void {
                    $query->where('department_id', $request->input('department_id'))
                        ->where('is_active', true);
                }),
            ],
            'is_active' => ['required', 'boolean'],
        ];

        return $request->validate($rules);
    }

    private function assignWindow(User $staff, int $windowId): void
    {
        $windows = ServiceWindow::query()
            ->where(function ($query) use ($staff, $windowId): void {
                $query->where('staff_id', $staff->id)->orWhereKey($windowId);
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $window = $windows->firstWhere('id', $windowId);
        $previousWindow = $windows->firstWhere('staff_id', $staff->id);

        if ($window === null || ! $window->is_active || (int) $window->department_id !== (int) $staff->department_id) {
            throw ValidationException::withMessages([
                'window_id' => 'Choose an active window in the staff member’s assigned office.',
            ]);
        }

        if ($window->staff_id !== null && (int) $window->staff_id !== (int) $staff->id) {
            throw ValidationException::withMessages([
                'window_id' => 'That service window is already assigned to another staff member.',
            ]);
        }

        $hasUnresolvedTicket = fn (ServiceWindow $assignedWindow): bool => $assignedWindow->tickets()
            ->whereDate('queue_date', Carbon::now(config('app.timezone'))->toDateString())
            ->whereIn('status', ['serving', 'skipped'])
            ->exists();

        if ($previousWindow !== null && (int) $previousWindow->id !== $windowId && $hasUnresolvedTicket($previousWindow)) {
            throw ValidationException::withMessages([
                'window_id' => 'Finish or recall tickets at the current window before changing this staff assignment.',
            ]);
        }

        if (! $staff->is_active && $hasUnresolvedTicket($window)) {
            throw ValidationException::withMessages([
                'is_active' => 'A staff member with an active or recallable ticket cannot be deactivated.',
            ]);
        }

        if ($previousWindow !== null && (int) $previousWindow->id !== $windowId) {
            $previousWindow->update(['staff_id' => null, 'is_on_break' => false]);
        }

        $window->update([
            'staff_id' => $staff->is_active ? $staff->id : null,
            'is_on_break' => (int) $window->staff_id === (int) $staff->id ? $window->is_on_break : false,
        ]);
    }
}
