<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    member: Object,
    roles: Array,
    permissions: Array,
    divisions: Array,
})

const form = useForm({
    name: props.member.name,
    email: props.member.email,
    phone: props.member.phone || '',
    member_id: props.member.member_id || '',
    division_id: props.member.division_id || '',
    is_active: props.member.is_active,
    roles: [...props.member.roles],
    permissions: [...props.member.permissions],
})

const submit = () => {
    form.patch(route('admin.members.update', props.member.id))
}

const toggleRole = (role) => {
    const index = form.roles.indexOf(role)
    if (index === -1) {
        form.roles.push(role)
    } else {
        form.roles.splice(index, 1)
    }
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
    <AppLayout title="Edit Member">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Member
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Member: {{ member.name }}</CardTitle>
                        <CardDescription>
                            Update member details, roles, and permissions
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="name">Full Name</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="John Doe"
                                        :class="form.errors.name ? 'border-destructive' : ''"
                                    />
                                    <p v-if="form.errors.name" class="text-xs text-destructive">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="member_id">Member ID</Label>
                                    <Input
                                        id="member_id"
                                        v-model="form.member_id"
                                        type="text"
                                        placeholder="COOP-001"
                                        :class="form.errors.member_id ? 'border-destructive' : ''"
                                    />
                                    <p v-if="form.errors.member_id" class="text-xs text-destructive">
                                        {{ form.errors.member_id }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="email">Email</Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="john@example.com"
                                        :class="form.errors.email ? 'border-destructive' : ''"
                                    />
                                    <p v-if="form.errors.email" class="text-xs text-destructive">
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="08012345678"
                                        :class="form.errors.phone ? 'border-destructive' : ''"
                                    />
                                    <p v-if="form.errors.phone" class="text-xs text-destructive">
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="division_id">Division</Label>
                                <select
                                    id="division_id"
                                    v-model="form.division_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select a division</option>
                                    <option v-for="division in divisions" :key="division.id" :value="division.id">
                                        {{ division.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label>Status</Label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        v-model="form.is_active"
                                        class="rounded border-input"
                                    />
                                    <Label for="is_active" class="font-normal cursor-pointer">
                                        Active
                                    </Label>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label>Roles</Label>
                                <div class="grid grid-cols-2 gap-2 max-h-30 overflow-y-auto border rounded-md p-3">
                                    <div v-for="role in roles" :key="role" class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :id="'role-' + role"
                                            :checked="form.roles.includes(role)"
                                            @change="toggleRole(role)"
                                            class="rounded border-input"
                                        />
                                        <Label :for="'role-' + role" class="font-normal cursor-pointer text-sm">
                                            {{ role }}
                                        </Label>
                                    </div>
                                </div>
                                <p v-if="form.errors.roles" class="text-xs text-destructive">
                                    {{ form.errors.roles }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label>Permissions</Label>
                                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border rounded-md p-3">
                                    <div v-for="permission in permissions" :key="permission" class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :id="'permission-' + permission"
                                            :checked="form.permissions.includes(permission)"
                                            @change="togglePermission(permission)"
                                            class="rounded border-input"
                                        />
                                        <Label :for="'permission-' + permission" class="font-normal cursor-pointer text-sm">
                                            {{ permission }}
                                        </Label>
                                    </div>
                                </div>
                                <p v-if="form.errors.permissions" class="text-xs text-destructive">
                                    {{ form.errors.permissions }}
                                </p>
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="$inertia.visit(route('admin.members.show', member.id))">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Updating...' : 'Update Member' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
