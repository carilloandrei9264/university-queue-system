<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
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
                ->withCount([
                    'services',
                    'staff',
                    'windows as active_windows_count' => fn ($query) => $query->where('is_active', true),
                ])
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
        [$attributes, $windowCount] = $this->validatedAttributes($request);
        $department = DB::transaction(function () use ($attributes, $windowCount, $request, $activityLogger): Department {
            $department = Department::create($attributes);
            $this->syncWindows($department, $windowCount);
            $activityLogger->record($request->user(), 'department.created', 'department', $department->id, $department->id);

            return $department;
        });

        return redirect()->route('admin.departments.index')
            ->with('status', "{$department->name} was created.");
    }

    public function update(Request $request, Department $department, ActivityLogger $activityLogger): RedirectResponse
    {
        [$attributes, $windowCount] = $this->validatedAttributes($request, $department);
        DB::transaction(function () use ($request, $department, $attributes, $windowCount, $activityLogger): void {
            $this->syncWindows($department, $windowCount);
            $department->update($attributes);
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
            'window_count' => ['sometimes', 'required', 'integer', 'between:1,20'],
        ]);
        $windowCount = (int) ($attributes['window_count'] ?? $department?->windows()->where('is_active', true)->count() ?? 3);
        unset($attributes['window_count']);

        return [$attributes, $windowCount];
    }

    private function syncWindows(Department $department, int $count): void
    {
        $removedWindows = $department->windows()
            ->where('is_active', true)
            ->where('position', '>', $count)
            ->lockForUpdate()
            ->get();

        foreach ($removedWindows as $window) {
            if ($window->tickets()
                ->whereDate('queue_date', Carbon::now(config('app.timezone'))->toDateString())
                ->whereIn('status', ['serving', 'skipped'])
                ->exists()) {
                abort(422, 'A window with a ticket being served or eligible for recall cannot be removed.');
            }

            $window->update([
                'staff_id' => null,
                'is_active' => false,
                'is_on_break' => false,
            ]);
        }

        for ($position = 1; $position <= $count; $position++) {
            $window = $department->windows()->where('position', $position)->first();

            if ($window === null) {
                $department->windows()->create([
                    'name' => "Window {$position}",
                    'position' => $position,
                    'is_active' => true,
                ]);

                continue;
            }

            $window->update(['name' => "Window {$position}", 'is_active' => true]);
        }
    }
}
