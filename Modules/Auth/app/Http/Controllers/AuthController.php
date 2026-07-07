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

        // Issue the API token up front (before the checks below) so that a
        // token-based client always has something to authenticate follow-up
        // requests with, even if a password change or profile completion is
        // required next. Harmless/unused for the Inertia (session) flow.
        $token = $user->createToken('api-token')->plainTextToken;

        // Force password change on first login
        if ($user->must_change_password) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Password change required.',
                    'token' => $token,
                    'data' => [
                        'user' => $user,
                        'requires_password_change' => true,
                        'requires_profile_completion' => $this->isProfileIncomplete($user),
                        'role' => $user->hasRole('admin') ? 'admin' : 'member',
                    ],
                ])
                : redirect()->route('password.change');
        }

        // Check if profile is complete (email and phone are required)
        if ($this->isProfileIncomplete($user)) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Profile completion required.',
                    'token' => $token,
                    'data' => [
                        'user' => $user,
                        'requires_password_change' => false,
                        'requires_profile_completion' => true,
                        'role' => $user->hasRole('admin') ? 'admin' : 'member',
                    ],
                ])
                : redirect()->route('profile.complete');
        }

        // Redirect based on role (web) / return role (api)
        if ($user->hasRole('admin')) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Logged in successfully.',
                    'token'   => $token,
                    'data' => [
                        'user' => $user,
                        'requires_password_change' => false,
                        'requires_profile_completion' => false,
                        'role' => 'admin',
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
                    'requires_password_change' => false,
                    'requires_profile_completion' => false,
                    'role' => 'member',
                ],
            ])
            : redirect()->route('member.dashboard');
    }

    // Return the currently authenticated user (API clients use this to
    // restore session state on load, e.g. after a page refresh).
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user(),
                'role' => $request->user()->hasRole('admin') ? 'admin' : 'member',
            ],
        ]);
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
                    'data' => [
                        'requires_profile_completion' => true,
                        'role' => $user->hasRole('admin') ? 'admin' : 'member',
                    ],
                ])
                : redirect()->route('profile.complete');
        }

        // Redirect based on role after password change
        if ($user->hasRole('admin')) {
            return $request->expectsJson()
                ? response()->json([
                    'success' => true,
                    'message' => 'Password updated successfully.',
                    'data' => ['requires_profile_completion' => false, 'role' => 'admin'],
                ])
                : redirect()->route('admin.dashboard');
        }

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
                'data' => ['requires_profile_completion' => false, 'role' => 'member'],
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
                    'data' => ['role' => 'admin'],
                ])
                : redirect()->route('admin.dashboard');
        }

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => 'Profile completed successfully.',
                'data' => ['role' => 'member'],
            ])
            : redirect()->route('member.dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        // API/token clients: revoke just the token that was used for this request.
        if ($request->expectsJson()) {
            $request->user()?->currentAccessToken()?->delete();

            return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
        }

        // Web/session clients.
        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
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
