<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    use RespondsWithJson;

    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $roles = Role::withCount('permissions')
                ->with('permissions')
                ->get()
                ->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions_count' => $role->permissions_count,
                    'permissions' => $role->permissions->pluck('name'),
                    'created_at' => $role->created_at->format('M d, Y'),
                ]);

            return $this->respond('Admin/Roles/Index', [
                'roles' => $roles,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load roles.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            $permissions = Permission::all()->pluck('name');

            return $this->respond('Admin/Roles/Create', [
                'permissions' => $permissions,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load role creation form.');
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['exists:permissions,name'],
            ]);

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return $this->respondSuccess("Role {$role->name} created successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create role.');
        }
    }

    public function edit(Role $role): Response|JsonResponse|RedirectResponse
    {
        try {
            $permissions = Permission::all()->pluck('name');
            $rolePermissions = $role->permissions->pluck('name');

            return $this->respond('Admin/Roles/Edit', [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                ],
                'permissions' => $permissions,
                'rolePermissions' => $rolePermissions,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load role for editing.');
        }
    }

    public function update(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['exists:permissions,name'],
            ]);

            $role->update([
                'name' => $request->name,
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }

            return $this->respondSuccess("Role {$role->name} updated successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update role.');
        }
    }

    public function destroy(Role $role): JsonResponse|RedirectResponse
    {
        try {
            // Prevent deleting admin role
            if ($role->name === 'admin') {
                return $this->respondSingleError('Cannot delete the admin role.');
            }

            $role->delete();

            return $this->respondSuccess("Role {$role->name} deleted successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete role.');
        }
    }
}
