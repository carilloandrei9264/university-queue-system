<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class StudentController extends Controller
{
    public function index(Request $request): View|Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        $temporaryPassword = $request->session()->pull('temporary_password');
        $response = response()->view('admin.students', [
            'students' => User::query()
                ->where('role', 'student')
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
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
        $attributes['role'] = 'student';
        $temporaryPassword = Str::password(24, symbols: false);
        $attributes['password'] = $temporaryPassword;
        $attributes['must_change_password'] = true;

        $student = DB::transaction(function () use ($attributes, $request, $activityLogger): User {
            $student = User::create($attributes);
            $activityLogger->record($request->user(), 'student.created', 'student', $student->id);

            return $student;
        });

        return redirect()->route('admin.students.index')
            ->with('status', "Student account for {$student->full_name} was created.")
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_account_name', $student->full_name);
    }

    public function update(Request $request, User $student, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        DB::transaction(function () use ($request, $student, $activityLogger): void {
            $student->update($this->validatedAttributes($request, $student));
            $activityLogger->record($request->user(), 'student.updated', 'student', $student->id);
        });

        return redirect()->route('admin.students.index')
            ->with('status', "Student record for {$student->full_name} was updated.");
    }

    public function resetPassword(Request $request, User $student, ActivityLogger $activityLogger): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $temporaryPassword = Str::password(24, symbols: false);

        DB::transaction(function () use ($request, $student, $activityLogger, $temporaryPassword): void {
            $student->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
            ])->save();
            $activityLogger->record($request->user(), 'student.temporary_password_reset', 'student', $student->id);
        });

        return redirect()->route('admin.students.index')
            ->with('status', "A new temporary password was issued for {$student->full_name}.")
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_account_name', $student->full_name);
    }

    private function validatedAttributes(Request $request, ?User $student = null): array
    {
        $email = $request->input('email');
        $studentId = $request->input('student_id');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        if (is_string($studentId)) {
            $request->merge(['student_id' => trim($studentId)]);
        }

        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student?->id),
            ],
            'student_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'student_id')->ignore($student?->id),
            ],
            'course' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
