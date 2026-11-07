<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        $this->ensureLocalPasswordLoginIsAvailable();

        return view('auth.login', [
            'demoLogin' => config('app.demo_login_enabled') && ! app()->environment('production'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureLocalPasswordLoginIsAvailable();

        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => mb_strtolower(trim($email))]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $credentials['is_active'] = true;

        $user = User::query()->where('email', $credentials['email'])->first();
        $demoAccountUnavailable = $user?->is_demo
            && (app()->environment('production') || ! config('app.demo_login_enabled'));

        if ($demoAccountUnavailable || ! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'The provided credentials are invalid or inactive.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->must_change_password) {
            return redirect()->route('password.change');
        }

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

    private function ensureLocalPasswordLoginIsAvailable(): void
    {
        abort_unless(
            config('app.local_password_login_enabled'),
            503,
            'Local password sign-in is currently unavailable.',
        );
    }
}
