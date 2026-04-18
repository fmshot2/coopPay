<script setup>
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

defineProps({
    permissions: Array,
})

const deletePermission = (permission) => {
    if (confirm(`Are you sure you want to delete the permission "${permission.name}"?`)) {
        router.delete(route('admin.permissions.destroy', permission.id))
    }
}
</script>

<template>
    <AppLayout title="Permissions Management">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Permissions Management
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage system permissions
                    </p>
                </div>
                <Link :href="route('admin.permissions.create')">
                    <Button>Create Permission</Button>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>All Permissions</CardTitle>
                        <CardDescription>
                            List of all permissions in the system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-3 px-4">Name</th>
                                        <th class="text-left py-3 px-4">Guard</th>
                                        <th class="text-left py-3 px-4">Roles</th>
                                        <th class="text-left py-3 px-4">Created</th>
                                        <th class="text-right py-3 px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="permission in permissions" :key="permission.id" class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">{{ permission.name }}</td>
                                        <td class="py-3 px-4">{{ permission.guard_name }}</td>
                                        <td class="py-3 px-4">{{ permission.roles_count }} roles</td>
                                        <td class="py-3 px-4">{{ permission.created_at }}</td>
                                        <td class="py-3 px-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <Link :href="route('admin.permissions.edit', permission.id)">
                                                    <Button variant="outline" size="sm">Edit</Button>
                                                </Link>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    @click="deletePermission(permission)"
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
