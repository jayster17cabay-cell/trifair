<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Forced password change for accounts created with the shared default
 * operator password. The account cannot use the application until a new
 * password has been saved.
 */
class ForcePasswordController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->requiresPasswordChange()) {
            return $this->redirectAfterChange($user);
        }

        return view('auth.force-password', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user->requiresPasswordChange()) {
            return $this->redirectAfterChange($user);
        }

        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (Hash::check($data['new_password'], $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => 'Please choose a password different from your current one.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'must_change_password' => false,
        ])->save();

        ActivityLogger::log(
            'operator_update_password',
            'Changed the default password after first sign-in',
            $user,
            'operator'
        );

        return $this->redirectAfterChange($user)
            ->with('success', 'Password updated. You can now use your account.');
    }

    private function redirectAfterChange($user)
    {
        if ($user->isOperator()) {
            $operator = $user->operator;

            if ($operator && $operator->status === 'pending') {
                return redirect()->route('operator.pending');
            }

            return redirect()->route('operator.dashboard');
        }

        if ($user->isOperatorPresident()) {
            return redirect()->route('dashboard');
        }

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        if ($user->isTfrbOfficer()) {
            return redirect()->route('tfrb-officer.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
