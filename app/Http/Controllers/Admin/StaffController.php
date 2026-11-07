<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'departments' => Department::orderBy('name')->get(),
            'staffMembers' => User::query()
                ->where('role', 'staff')
                ->with('department')
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
        $attributes['role'] = 'staff';
        $attributes['email_verified_at'] = now();
        $temporaryPassword = Str::password(24, symbols: false);
        $attributes['password'] = $temporaryPassword;
        $attributes['must_change_password'] = true;

        $staff = DB::transaction(function () use ($attributes, $request, $activityLogger): User {
            $staff = User::create($attributes);
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
        DB::transaction(function () use ($request, $staff, $attributes, $activityLogger): void {
            $staff->update($attributes);
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
            'is_active' => ['required', 'boolean'],
        ];

        return $request->validate($rules);
    }
}
