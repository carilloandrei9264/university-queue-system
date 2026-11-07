<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        return view('admin.departments', [
            'departments' => Department::query()
                ->withCount(['services', 'staff'])
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('prefix', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $department = DB::transaction(function () use ($request, $activityLogger): Department {
            $department = Department::create($this->validatedAttributes($request));
            $activityLogger->record($request->user(), 'department.created', 'department', $department->id, $department->id);

            return $department;
        });

        return redirect()->route('admin.departments.index')
            ->with('status', "{$department->name} was created.");
    }

    public function update(Request $request, Department $department, ActivityLogger $activityLogger): RedirectResponse
    {
        DB::transaction(function () use ($request, $department, $activityLogger): void {
            $department->update($this->validatedAttributes($request, $department));
            $activityLogger->record($request->user(), 'department.updated', 'department', $department->id, $department->id);
        });

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
