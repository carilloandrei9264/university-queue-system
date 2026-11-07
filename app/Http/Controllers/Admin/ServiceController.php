<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services', [
            'departments' => Department::orderBy('name')->get(),
            'services' => Service::with('department')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $service = Service::create($attributes);

        return redirect()->route('admin.services.index')
            ->with('status', "{$service->name} was created.");
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validatedAttributes($request, $service));

        return redirect()->route('admin.services.index')
            ->with('status', "{$service->name} was updated.");
    }

    private function validatedAttributes(Request $request, ?Service $service = null): array
    {
        $departmentId = $request->input('department_id');

        $departmentRule = Rule::exists('departments', 'id')->where('is_active', true);

        if ($service !== null && ($service->tickets()->exists() || ! $service->department->is_active)) {
            $departmentRule = Rule::in([$service->department_id]);
        }

        return $request->validate([
            'department_id' => ['required', 'integer', $departmentRule],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')
                    ->where('department_id', $departmentId)
                    ->ignore($service?->id),
            ],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
