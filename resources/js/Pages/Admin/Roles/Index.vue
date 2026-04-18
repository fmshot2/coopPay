<script setup>
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    roles: Array,
})

const deleteRole = (role) => {
    if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
        router.delete(route('admin.roles.destroy', role.id))
    }
}
</script>

<template>
    <AppLayout title="Roles Management">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Roles Management
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage user roles and their permissions
                    </p>
                </div>
                <Link :href="route('admin.roles.create')">
                    <Button>Create Role</Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>All Roles</CardTitle>
                        <CardDescription>
                            List of all roles in the system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-3 px-4">Name</th>
                                        <th class="text-left py-3 px-4">Guard</th>
                                        <th class="text-left py-3 px-4">Permissions</th>
                                        <th class="text-left py-3 px-4">Created</th>
                                        <th class="text-right py-3 px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="role in roles" :key="role.id" class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ role.name }}</td>
                                        <td class="py-3 px-4">{{ role.guard_name }}</td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="permission in role.permissions" :key="permission"
                                                    class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                    {{ permission }}
                                                </span>
                                                <span v-if="role.permissions.length === 0" class="text-gray-400 text-sm">
                                                    No permissions
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">{{ role.created_at }}</td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <Link :href="route('admin.roles.edit', role.id)">
                                                    <Button variant="outline" size="sm">Edit</Button>
                                                </Link>
                                                <Button
                                                    v-if="role.name !== 'admin'"
                                                    variant="destructive"
                                                    size="sm"
                                                    @click="deleteRole(role)"
                                                >
                                                    Delete
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
