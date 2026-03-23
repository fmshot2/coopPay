<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { ArrowLeft } from 'lucide-vue-next'

const form = useForm({
    name:      '',
    email:     '',
    phone:     '',
    member_id: '',
})

const submit = () => {
    form.post(route('admin.members.store'))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-xl">

            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('admin.members.index')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Add Member</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Create a new cooperative member account
                    </p>
                </div>
            </div>

            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Member Details</CardTitle>
                    <CardDescription>
                        A default password of 'password' will be set. Member will be required to change it on first login.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Full Name <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Aliyu Musa"
                                :class="form.errors.name ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email Address <span class="text-destructive">*</span></Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="e.g. aliyu@example.com"
                                :class="form.errors.email ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.email" class="text-xs text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="e.g. 08012345678"
                                :class="form.errors.phone ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.phone" class="text-xs text-destructive">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <!-- Member ID -->
                        <div class="space-y-2">
                            <Label for="member_id">
                                Member ID
                                <span class="text-muted-foreground text-xs font-normal ml-1">
                                    (optional — auto-generated if left blank)
                                </span>
                            </Label>
                            <Input
                                id="member_id"
                                v-model="form.member_id"
                                placeholder="e.g. COOP-011"
                                :class="form.errors.member_id ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.member_id" class="text-xs text-destructive">
                                {{ form.errors.member_id }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Creating...' : 'Create Member' }}
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('admin.members.index')">Cancel</Link>
                            </Button>
                        </div>

                    </form>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
