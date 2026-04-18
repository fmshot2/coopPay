<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    permission: Object,
})

const form = useForm({
    name: props.permission.name,
})

const submit = () => {
    form.patch(route('admin.permissions.update', props.permission.id))
}
</script>

<template>
    <AppLayout title="Edit Permission">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Permission
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Permission: {{ permission.name }}</CardTitle>
                        <CardDescription>
                            Update permission details
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
                                    {{ form.processing ? 'Updating...' : 'Update Permission' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
