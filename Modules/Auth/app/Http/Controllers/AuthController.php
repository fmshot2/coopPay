<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    // Show login form
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    // Handle login
    public function login(Request $request)
    {
        // Check if login is with member_id + name (for members) or email (for admin)
        $isMemberLogin = $request->filled('member_id') && $request->filled('name');
        $isAdminLogin = $request->filled('email');

        if ($isMemberLogin) {
            // Member login with member_id + name
            $credentials = $request->validate([
                'member_id' => ['required', 'string'],
                'name' => ['required', 'string'],
                'password' => ['required'],
            ]);

            $user = \App\Models\User::where('member_id', $credentials['member_id'])
                ->where('name', $credentials['name'])
                ->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return back()->withErrors([
                    'member_id' => 'These credentials do not match our records.',
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
        } elseif ($isAdminLogin) {
            // Admin login with email
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if (!Auth::attempt($credentials, $request->boolean('remember'))) {
                return back()->withErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
            }
        } else {
            return back()->withErrors([
                'email' => 'Please provide valid login credentials.',
            ]);
        }

        $user = Auth::user();

        // Block inactive users
        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact admin.',
            ]);
        }

        $request->session()->regenerate();

        // Force password change on first login
        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        // Check if profile is complete (email and phone are required)
        if ($this->isProfileIncomplete($user)) {
            return redirect()->route('profile.complete');
        }

        // Redirect based on role
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.members.index');
        }

        return redirect()->route('member.dashboard');
    }

    // Show change password form
    public function showChangePassword(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    // Handle change password
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => $request->password,
            'must_change_password' => false,
        ]);

        // Check if profile is complete after password change
        if ($this->isProfileIncomplete($user)) {
            return redirect()->route('profile.complete');
        }

        // Redirect based on role after password change
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('member.dashboard');
    }

    // Show profile completion form
    public function showProfileComplete(): Response
    {
        return Inertia::render('Auth/ProfileComplete');
    }

    // Handle profile completion
    public function profileComplete(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email,' . Auth::id()],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user = Auth::user();
        $user->update([
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Redirect based on role after profile completion
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('member.dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Check if profile is incomplete
    private function isProfileIncomplete($user): bool
    {
        return empty($user->email) || empty($user->phone);
    }
}
