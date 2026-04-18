<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
    name: '',
})

const submit = () => {
    form.post(route('admin.permissions.store'))
}
</script>

<template>
    <AppLayout title="Create Permission">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Permission
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Create New Permission</CardTitle>
                        <CardDescription>
                            Add a new permission to the system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="space-y-2">
                                <Label for="name">Permission Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="e.g., manage-users"
                                    :class="form.errors.name ? 'border-destructive' : ''"
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">
                                    {{ form.errors.name }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Use lowercase with hyphens (e.g., manage-users, view-reports)
                                </p>
                            </div>

                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="$inertia.visit(route('admin.permissions.index'))">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Creating...' : 'Create Permission' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
