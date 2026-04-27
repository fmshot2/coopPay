<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ProfileController extends Controller
{
    use RespondsWithJson;
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            return $this->respond('Member/Profile/Index', [
                'member' => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'phone'         => $user->phone,
                    'member_id'     => $user->member_id,
                    'profile_photo' => $user->profile_photo,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load profile.');
        }
    }

    public function updateProfile(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $user = Auth::user();

            $request->validate([
                'name'  => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:20'],
            ]);

            $user->update([
                'name'  => $request->name,
                'phone' => $request->phone,
            ]);

            return $this->respondSuccess('Profile updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update profile.');
        }
    }

    public function updatePhoto(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            ]);

            $user = Auth::user();

            // Delete old photo if exists
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $path = $request->file('photo')->store('profiles', 'public');

            $user->update(['profile_photo' => $path]);

            return $this->respondSuccess('Profile photo updated.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update profile photo.');
        }
    }

    public function updatePassword(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'current_password' => ['required'],
                'password'         => ['required', 'confirmed', 'min:8'],
            ]);

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->respondSingleError('Current password is incorrect.');
            }

            $user->update([
                'password'             => $request->password,
                'must_change_password' => false,
            ]);

            return $this->respondSuccess('Password changed successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to change password.');
        }
    }
}
