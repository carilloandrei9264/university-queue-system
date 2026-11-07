<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        $this->ensureDemoLoginIsAvailable();

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureDemoLoginIsAvailable();

        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'The provided credentials are invalid or inactive.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $dashboardRoute = match (Auth::user()->role) {
            'student' => 'student.dashboard',
            'staff' => 'staff.dashboard',
            'admin' => 'admin.dashboard',
            default => abort(403),
        };

        return redirect()->route($dashboardRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureDemoLoginIsAvailable(): void
    {
        abort_unless(
            config('app.demo_login_enabled') && ! app()->environment('production'),
            503,
            'Demo password sign-in is only available in local and testing environments.',
        );
    }
}
