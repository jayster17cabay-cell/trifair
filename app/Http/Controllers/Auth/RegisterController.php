<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\Toda;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $todas = Toda::where('is_active', true)->orderBy('name')->get();
        return view('auth.register', compact('todas'));
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim($request->input('email')))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'contact_number' => 'required|string|max:20',
            'license_number' => 'required|string|max:50',
            'plate_number' => 'required|string|max:20|unique:operators,plate_number',
            'body_number' => 'required|string|max:20|unique:operators,body_number',
            'toda_id' => 'nullable|exists:todas,id',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'plate_number.unique' => 'This plate number is already registered in the system.',
            'body_number.unique' => 'This body number is already registered in the system.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['contact_number'],
            'role' => 'operator',
            'is_active' => true,
        ]);

        Operator::create([
            'user_id' => $user->id,
            'toda_id' => $data['toda_id'] ?? null,
            'contact_number' => $data['contact_number'],
            'license_number' => $data['license_number'],
            'plate_number' => $data['plate_number'],
            'body_number' => $data['body_number'],
            'qr_code' => Str::random(32),
            'status' => 'pending',
        ]);

        try {
            event(new Registered($user));
        } catch (\Exception $e) {
            Log::error('Email verification failed: ' . get_class($e) . ': ' . $e->getMessage());
            $user->markEmailAsVerified();
        }

        Auth::login($user);

        return $user->hasVerifiedEmail()
            ? redirect()->route('operator.pending')
            : redirect()->route('verification.notice');
    }
}
