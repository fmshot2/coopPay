<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Edit, Trash2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

const props = defineProps({
    division: Object,
})

const toggleActive = () => {
    router.patch(route('admin.divisions.toggle-active', props.division.id))
}

const deleteDivision = () => {
    if (confirm('Are you sure you want to delete this division?')) {
        router.delete(route('admin.divisions.destroy', props.division.id), {
            onSuccess: () => {
                toast.success('Division deleted successfully')
            }
        })
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-3xl m-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="route('admin.divisions.index')">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">{{ division.name }}</h2>
                        <p class="text-sm text-muted-foreground mt-1">Division Details</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button :variant="division.is_active ? 'destructive' : 'default'" size="sm" @click="toggleActive">
                        {{ division.is_active ? 'Deactivate' : 'Activate' }}
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="route('admin.divisions.edit', division.id)">
                            <Edit class="h-4 w-4 mr-2" />
                            Edit
                        </Link>
                    </Button>
                    <Button variant="ghost" size="sm" @click="deleteDivision">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Division Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Name</p>
                            <p class="text-sm font-medium">{{ division.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Status</p>
                            <Badge :variant="division.is_active ? 'default' : 'secondary'">
                                {{ division.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Members Count</p>
                            <p class="text-sm font-medium">{{ division.members_count }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Created</p>
                            <p class="text-sm font-medium">{{ division.created_at }}</p>
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <p class="text-xs text-muted-foreground">Description</p>
                            <p class="text-sm font-medium">{{ division.description || '—' }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
