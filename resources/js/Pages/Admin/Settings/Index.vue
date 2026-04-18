<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Settings, Save } from 'lucide-vue-next'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    cooperative_account: props.settings?.cooperative_account || '',
    organization_name: props.settings?.organization_name || '',
    contact_email: props.settings?.contact_email || '',
    contact_phone: props.settings?.contact_phone || '',
    address: props.settings?.address || '',
})

const submit = () => {
    form.patch(route('admin.settings.update'), {
        onSuccess: () => {
            // Form will be reset on success
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Settings</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage cooperative settings and configuration
                    </p>
                </div>
            </div>

            <!-- Settings Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">General Settings</CardTitle>
                    <CardDescription>
                        Configure cooperative account and contact information
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Cooperative Account -->
                        <div class="space-y-2">
                            <Label for="cooperative_account">Cooperative Account Number</Label>
                            <Input v-model="form.cooperative_account" id="cooperative_account"
                                placeholder="Enter cooperative account number"
                                :class="form.errors.cooperative_account ? 'border-destructive' : ''" />
                            <p v-if="form.errors.cooperative_account" class="text-xs text-destructive">
                                {{ form.errors.cooperative_account }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                This will be displayed on the member dashboard
                            </p>
                        </div>

                        <!-- Organization Name -->
                        <div class="space-y-2">
                            <Label for="organization_name">Organization Name</Label>
                            <Input v-model="form.organization_name" id="organization_name"
                                placeholder="Enter organization name"
                                :class="form.errors.organization_name ? 'border-destructive' : ''" />
                            <p v-if="form.errors.organization_name" class="text-xs text-destructive">
                                {{ form.errors.organization_name }}
                            </p>
                        </div>

                        <!-- Contact Email -->
                        <div class="space-y-2">
                            <Label for="contact_email">Contact Email</Label>
                            <Input v-model="form.contact_email" id="contact_email" type="email"
                                placeholder="Enter contact email"
                                :class="form.errors.contact_email ? 'border-destructive' : ''" />
                            <p v-if="form.errors.contact_email" class="text-xs text-destructive">
                                {{ form.errors.contact_email }}
                            </p>
                        </div>

                        <!-- Contact Phone -->
                        <div class="space-y-2">
                            <Label for="contact_phone">Contact Phone</Label>
                            <Input v-model="form.contact_phone" id="contact_phone"
                                placeholder="Enter contact phone number"
                                :class="form.errors.contact_phone ? 'border-destructive' : ''" />
                            <p v-if="form.errors.contact_phone" class="text-xs text-destructive">
                                {{ form.errors.contact_phone }}
                            </p>
                        </div>

                        <!-- Address -->
                        <div class="space-y-2">
                            <Label for="address">Address</Label>
                            <textarea v-model="form.address" id="address" rows="3"
                                placeholder="Enter organization address"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                :class="form.errors.address ? 'border-destructive' : ''" />
                            <p v-if="form.errors.address" class="text-xs text-destructive">
                                {{ form.errors.address }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="h-4 w-4 mr-2" />
                                {{ form.processing ? 'Saving...' : 'Save Settings' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
