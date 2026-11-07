<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('admin.departments', [
            'departments' => Department::withCount(['services', 'staff'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $department = Department::create($this->validatedAttributes($request));

        return redirect()->route('admin.departments.index')
            ->with('status', "{$department->name} was created.");
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validatedAttributes($request, $department));

        return redirect()->route('admin.departments.index')
            ->with('status', "{$department->name} was updated.");
    }

    private function validatedAttributes(Request $request, ?Department $department = null): array
    {
        $attributes = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department?->id),
            ],
            'prefix' => [
                'required',
                'string',
                'regex:/^[A-Z]{2,4}$/',
                Rule::unique('departments', 'prefix')->ignore($department?->id),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        return $attributes;
    }
}
