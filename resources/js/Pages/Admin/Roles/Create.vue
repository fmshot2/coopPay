<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    permissions: Array,
})

const form = useForm({
    name: '',
    permissions: [],
})

const submit = () => {
    form.post(route('admin.roles.store'))
}

const togglePermission = (permission) => {
    const index = form.permissions.indexOf(permission)
    if (index === -1) {
        form.permissions.push(permission)
    } else {
        form.permissions.splice(index, 1)
    }
}
</script>

<template>
    <AppLayout title="Create Role">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Role
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Create New Role</CardTitle>
                        <CardDescription>
                            Add a new role and assign permissions
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="name">Role Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g., manager"
                                    :class="form.errors.name ? 'border-destructive' : ''"
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Permissions</Label>
                                <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto border rounded-md p-3">
                                    <div v-for="permission in permissions" :key="permission" class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :id="permission"
                                            :checked="form.permissions.includes(permission)"
                                            @change="togglePermission(permission)"
                                            class="rounded border-input"
                                        />
                                        <Label :for="permission" class="font-normal cursor-pointer text-sm">
                                            {{ permission }}
                                        </Label>
                                    </div>
                                </div>
                                <p v-if="form.errors.permissions" class="text-xs text-destructive">
                                    {{ form.errors.permissions }}
                                </p>
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="$inertia.visit(route('admin.roles.index'))">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Creating...' : 'Create Role' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
