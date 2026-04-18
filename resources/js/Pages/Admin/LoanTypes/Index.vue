<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tags, Plus, Pencil } from 'lucide-vue-next'

const props = defineProps({
    loanTypes: Array,
})

// Create form
const createForm = useForm({
    name:        '',
    description: '',
})

const submitCreate = () => {
    createForm.post(route('admin.loan-types.store'), {
        onSuccess: () => createForm.reset(),
    })
}

// Edit form
const editingId = ref(null)
const editForm  = useForm({
    name:        '',
    description: '',
})

const startEdit = (type) => {
    editingId.value  = type.id
    editForm.name        = type.name
    editForm.description = type.description ?? ''
}

const cancelEdit = () => {
    editingId.value = null
    editForm.reset()
}

const submitEdit = (type) => {
    editForm.patch(route('admin.loan-types.update', type.id), {
        onSuccess: () => {
            editingId.value = null
        },
    })
}

const toggleActive = (type) => {
    useForm({}).patch(route('admin.loan-types.toggle-active', type.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 m-auto">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">Loan Types</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage the types of loans available to members
                </p>
            </div>

            <!-- Create New Loan Type -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Add New Loan Type</CardTitle>
                    <CardDescription>Create a new loan category</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="name">Name <span class="text-destructive">*</span></Label>
                                <Input
                                    id="name"
                                    v-model="createForm.name"
                                    placeholder="e.g. Medical"
                                    :class="createForm.errors.name ? 'border-destructive' : ''"
                                />
                                <p v-if="createForm.errors.name" class="text-xs text-destructive">
                                    {{ createForm.errors.name }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Input
                                    id="description"
                                    v-model="createForm.description"
                                    placeholder="Brief description..."
                                />
                            </div>
                        </div>
                        <Button type="submit" :disabled="createForm.processing">
                            <Plus class="h-4 w-4 mr-2" />
                            {{ createForm.processing ? 'Adding...' : 'Add Loan Type' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Loan Types List -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">All Loan Types</CardTitle>
                    <CardDescription>{{ loanTypes.length }} loan types configured</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="loanTypes.length === 0" class="text-center py-10">
                        <Tags class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No loan types yet</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="type in loanTypes"
                            :key="type.id"
                            class="border rounded-md p-4"
                        >
                            <!-- View mode -->
                            <div v-if="editingId !== type.id" class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-foreground">{{ type.name }}</p>
                                        <Badge :variant="type.is_active ? 'default' : 'outline'" class="text-xs">
                                            {{ type.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                        <Badge variant="secondary" class="text-xs">
                                            {{ type.loans_count }} loan{{ type.loans_count !== 1 ? 's' : '' }}
                                        </Badge>
                                    </div>
                                    <p v-if="type.description" class="text-xs text-muted-foreground">
                                        {{ type.description }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="startEdit(type)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :class="type.is_active ? 'text-destructive hover:text-destructive' : ''"
                                        @click="toggleActive(type)"
                                    >
                                        {{ type.is_active ? 'Deactivate' : 'Activate' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Edit mode -->
                            <form v-else @submit.prevent="submitEdit(type)" class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label>Name</Label>
                                        <Input
                                            v-model="editForm.name"
                                            :class="editForm.errors.name ? 'border-destructive' : ''"
                                        />
                                        <p v-if="editForm.errors.name" class="text-xs text-destructive">
                                            {{ editForm.errors.name }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label>Description</Label>
                                        <Input v-model="editForm.description" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button type="submit" size="sm" :disabled="editForm.processing">
                                        {{ editForm.processing ? 'Saving...' : 'Save' }}
                                    </Button>
                                    <Button variant="outline" size="sm" type="button" @click="cancelEdit">
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
