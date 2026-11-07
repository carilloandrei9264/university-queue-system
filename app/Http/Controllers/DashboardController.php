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
                'Departments' => route('admin.departments.index'),
                'Services' => route('admin.services.index'),
                'Office staff' => route('admin.staff.index'),
                'Students' => route('admin.students.index'),
            ],
        ]);
    }
}
