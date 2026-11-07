<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        return view('admin.students', [
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
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $attributes['role'] = 'student';

        $student = User::create($attributes);

        return redirect()->route('admin.students.index')
            ->with('status', "Student record for {$student->full_name} was created. Sign-in is not enabled until credentials are configured.");
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $student->update($this->validatedAttributes($request, $student));

        return redirect()->route('admin.students.index')
            ->with('status', "Student record for {$student->full_name} was updated.");
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
