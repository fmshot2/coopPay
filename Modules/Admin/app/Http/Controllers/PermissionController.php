<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use RespondsWithJson;

    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $permissions = Permission::withCount('roles')
                ->get()
                ->map(fn($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'roles_count' => $permission->roles_count,
                    'created_at' => $permission->created_at->format('M d, Y'),
                ]);

            return $this->respond('Admin/Permissions/Index', [
                'permissions' => $permissions,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load permissions.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Admin/Permissions/Create');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load permission creation form.');
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            ]);

            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);

            return $this->respondSuccess("Permission {$permission->name} created successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create permission.');
        }
    }

    public function edit(Permission $permission): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Admin/Permissions/Edit', [
                'permission' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load permission for editing.');
        }
    }

    public function update(Request $request, Permission $permission): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
            ]);

            $permission->update([
                'name' => $request->name,
            ]);

            return $this->respondSuccess("Permission {$permission->name} updated successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update permission.');
        }
    }

    public function destroy(Permission $permission): JsonResponse|RedirectResponse
    {
        try {
            $permission->delete();

            return $this->respondSuccess("Permission {$permission->name} deleted successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete permission.');
        }
    }
}
