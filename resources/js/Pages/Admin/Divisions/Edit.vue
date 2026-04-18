<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { ArrowLeft } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

const props = defineProps({
    division: Object,
})

const form = useForm({
    name: props.division.name,
    description: props.division.description || '',
    is_active: props.division.is_active,
})

const submit = () => {
    form.patch(route('admin.divisions.update', props.division.id), {
        onSuccess: () => {
            toast.success('Division updated successfully')
        },
        onError: () => {
            toast.error('Failed to update division. Please check the form.')
        }
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-xl m-auto">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('admin.divisions.show', division.id)">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Edit Division</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Update division details
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Division Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">Name <span class="text-destructive">*</span></Label>
                            <Input 
                                id="name" 
                                v-model="form.name" 
                                placeholder="e.g. Main Division"
                                :class="form.errors.name ? 'border-destructive' : ''" 
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Brief description of this division..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                            />
                        </div>

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

                        <div class="flex items-center gap-3 pt-4 border-t">
                            <Button type="submit" :disabled="form.processing" class="rounded-xl px-8">
                                {{ form.processing ? 'Updating...' : 'Update Division' }}
                            </Button>
                            <Button variant="outline" as-child class="rounded-xl">
                                <Link :href="route('admin.divisions.show', division.id)">Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
