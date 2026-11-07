<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff', [
            'departments' => Department::orderBy('name')->get(),
            'staffMembers' => User::where('role', 'staff')->with('department')->orderBy('full_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $attributes['role'] = 'staff';
        $attributes['email_verified_at'] = now();

        $staff = User::create($attributes);

        return redirect()->route('admin.staff.index')
            ->with('status', "{$staff->full_name} was created as office staff.");
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === 'staff', 404);

        $attributes = $this->validatedAttributes($request, $staff);
        $password = $attributes['password'] ?? null;
        unset($attributes['password']);

        if ($password !== null && $password !== '') {
            $attributes['password'] = Hash::make($password);
        }

        $staff->update($attributes);

        return redirect()->route('admin.staff.index')
            ->with('status', "{$staff->full_name} was updated.");
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
            'password' => [$staff === null ? 'required' : 'nullable', 'string', 'min:12', 'confirmed'],
        ];

        if ($staff !== null) {
            $rules['password'] = ['nullable', 'string', 'min:12', 'confirmed'];
        }

        return $request->validate($rules);
    }
}
