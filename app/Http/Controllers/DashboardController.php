<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        return match ($user->role) {
            'student' => redirect()->route('student.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            default => abort(403),
        };
    }

    public function student(): View
    {
        return view('dashboard', ['title' => 'Student dashboard']);
    }

    public function staff(Request $request): View
    {
        return view('dashboard', [
            'title' => 'Office staff dashboard',
            'department' => $request->user()->department,
        ]);
    }

    public function admin(): View
    {
        return view('dashboard', [
            'title' => 'Admin dashboard',
            'adminLinks' => [
                'Departments' => [
                    'url' => route('admin.departments.index'),
                    'description' => 'Set up offices and control queue availability.',
                ],
                'Services' => [
                    'url' => route('admin.services.index'),
                    'description' => 'Manage the services students can queue for.',
                ],
                'Office staff' => [
                    'url' => route('admin.staff.index'),
                    'description' => 'Create accounts and assign staff to an office.',
                ],
                'Students' => [
                    'url' => route('admin.students.index'),
                    'description' => 'Create and manage student accounts.',
                ],
                'Activity log' => [
                    'url' => route('admin.activity.index'),
                    'description' => 'Review recent account, office, and queue changes.',
                ],
            ],
        ]);
    }
}
