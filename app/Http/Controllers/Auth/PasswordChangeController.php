<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function edit(): View
    {
        return view('auth.password-change');
    }

    public function update(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'different:current_password'],
        ]);
        $user = $request->user();

        DB::transaction(function () use ($user, $validated, $activityLogger): void {
            $user->forceFill([
                'password' => $validated['password'],
                'must_change_password' => false,
            ])->save();

            $activityLogger->record($user, 'account.password_changed', 'user', $user->id, $user->department_id);
        });

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your password was changed.');
    }
}
