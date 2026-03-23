<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'                   => $user->id,
                    'name'                 => $user->name,
                    'email'                => $user->email,
                    'member_id'            => $user->member_id,
                    'phone'                => $user->phone,
                    'profile_photo'        => $user->profile_photo ? '/storage/' . $user->profile_photo : null,
                    'is_active'            => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                    'roles'                => $user->getRoleNames(),
                    'permissions'          => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'info'    => $request->session()->get('info'),
            ],
        ]);
    }
}
