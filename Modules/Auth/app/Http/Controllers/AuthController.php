<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    use RespondsWithJson;

    // Show login form
    public function showLogin(): Response
    {
        return $this->respond('Auth/Login');
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
                if ($request->expectsJson()) {
                    return $this->respondError([
                        'member_id' => 'These credentials do not match our records.',
                    ], 422);
                }

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
                if ($request->expectsJson()) {
                    return $this->respondError([
                        'email' => 'These credentials do not match our records.',
                    ], 422);
                }

                return back()->withErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
            }
        } else {
            return $request->expectsJson()
                ? $this->respondError(['email' => 'Please provide valid login credentials.'], 422)
                : back()->withErrors([
                    'email' => 'Please provide valid login credentials.',
                ]);
        }

        $user = Auth::user();

        // Block inactive users
        if (!$user->is_active) {
            Auth::logout();

            return $request->expectsJson()
                ? $this->respondError(['email' => 'Your account has been deactivated. Please contact admin.'], 403)
                : back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact admin.',
                ]);
        }

        // ONLY regenerate session for web requests (not API)
        if (!$request->expectsJson()) {
            $request->session()->regenerate();
        }

        // Force password change on first login
        if ($user->must_change_password) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Password change required.',
                    'redirect' => route('password.change'),
                ])
                : redirect()->route('password.change');
        }

        // Check if profile is complete (email and phone are required)
        if ($this->isProfileIncomplete($user)) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Profile completion required.',
                    'redirect' => route('profile.complete'),
                ])
                : redirect()->route('profile.complete');
        }

        // Create token for API authentication
        $token = $user->createToken('api-token')->plainTextToken;

        // Redirect based on role
        if ($user->hasRole('admin')) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Logged in successfully.',
                    'token'   => $token,
                    'data' => [
                        'user' => $user,
                        'redirect' => route('admin.members.index'),
                    ],
                ])
                : redirect()->route('admin.members.index');
        }

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
                'token' => $token,
                'data' => [
                    'user' => $user,
                    'redirect' => route('member.dashboard'),
                ],
            ])
            : redirect()->route('member.dashboard');
    }

    // Show change password form
    public function showChangePassword(): Response
    {
        return $this->respond('Auth/ChangePassword');
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
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Profile completion required.',
                    'redirect' => route('profile.complete'),
                ])
                : redirect()->route('profile.complete');
        }

        // Redirect based on role after password change
        if ($user->hasRole('admin')) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Password updated successfully.',
                    'redirect' => route('admin.dashboard'),
                ])
                : redirect()->route('admin.dashboard');
        }

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
                'redirect' => route('member.dashboard'),
            ])
            : redirect()->route('member.dashboard');
    }

    // Show profile completion form
    public function showProfileComplete(): Response
    {
        return $this->respond('Auth/ProfileComplete');
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
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Profile completed successfully.',
                    'redirect' => route('admin.dashboard'),
                ])
                : redirect()->route('admin.dashboard');
        }

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => 'Profile completed successfully.',
                'redirect' => route('member.dashboard'),
            ])
            : redirect()->route('member.dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Logged out successfully.'])
            : redirect()->route('login');
    }

    // Show forgot password form
    public function showForgotPasswordForm(): Response
    {
        return $this->respond('Auth/ForgotPassword');
    }

    // Send reset link email
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // We will send the reset link via email (using Laravel's Password broker)
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Check if the link was sent successfully
        if ($status === Password::RESET_LINK_SENT) {
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => __($status)])
                : back()->with(['status' => __($status)]);
        }

        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => __($status), 'errors' => ['email' => __($status)]], 422)
            : back()->withErrors(['email' => __($status)]);
    }

    // Show reset password form
    public function showResetPasswordForm(string $token): Response
    {
        return $this->respond('Auth/ResetPassword', ['token' => $token]);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Here we will attempt to reset the user's password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Update the user's password and set must_change_password to false
                $user->forceFill([
                    'password' => bcrypt($password),
                    'must_change_password' => false,
                ])->save();

                // Optionally, you can remove the token here if you want to one-time use
                // But the Password::reset method already deletes the token
            }
        );

        // If the password was reset successfully, redirect to login with success message
        if ($status === Password::PASSWORD_RESET) {
            return $request->expectsJson()
                ? response()->json(['success' => true, 'message' => __($status)])
                : redirect()->route('login')->with(['status' => __($status)]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => __($status),
                'errors' => ['email' => __($status)],
            ], 422);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    // Check if profile is incomplete
    private function isProfileIncomplete($user): bool
    {
        return empty($user->email) || empty($user->phone);
    }
}
