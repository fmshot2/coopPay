<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const form = useForm({
    email: '',
    phone: '',
})

const submit = () => {
    form.post(route('profile.complete.post'), {
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
        style="background: oklch(0.18 0.06 150);">
        <!-- Background Pattern -->
        <div class="absolute inset-0 z-0" style="
    background-image:
        linear-gradient(oklch(0.25 0.08 150 / 0.8) 1px, transparent 1px),
        linear-gradient(90deg, oklch(0.25 0.08 150 / 0.8) 1px, transparent 1px),
        radial-gradient(ellipse at 20% 50%, oklch(0.35 0.12 150 / 0.3) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, oklch(0.4 0.15 150 / 0.2) 0%, transparent 50%),
        radial-gradient(ellipse at 60% 80%, oklch(0.3 0.1 150 / 0.25) 0%, transparent 55%);
    background-size: 40px 40px, 40px 40px, 100% 100%, 100% 100%, 100% 100%;
"></div>

        <!-- Floating circles decoration -->
        <div class="absolute top-10 left-10 w-64 h-64 rounded-full z-0"
            style="background: radial-gradient(circle, oklch(0.4 0.15 150 / 0.15) 0%, transparent 70%);"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 rounded-full z-0"
            style="background: radial-gradient(circle, oklch(0.5 0.18 150 / 0.1) 0%, transparent 70%);"></div>
        <div class="absolute top-1/2 left-1/4 w-48 h-48 rounded-full z-0"
            style="background: radial-gradient(circle, oklch(0.45 0.16 150 / 0.08) 0%, transparent 70%);"></div>

        <!-- Content -->
        <div class="w-full max-w-md space-y-6 relative z-10">

            <!-- Logo / Header -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary mb-2">
                    <span class="text-primary-foreground font-bold text-2xl">C</span>
                </div>
                <h1 class="text-2xl font-bold text-white">CoopPay</h1>
                <p class="text-sm" style="color: oklch(0.75 0.08 150)">
                    Complete Your Profile
                </p>
            </div>

            <!-- Profile Completion Card -->
            <Card class="border-0 shadow-2xl">
                <CardHeader>
                    <CardTitle>Complete Your Profile</CardTitle>
                    <CardDescription>
                        Please provide your email and phone number to continue using the application.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email address</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="you@example.com"
                                autocomplete="email" :class="form.errors.email ? 'border-destructive' : ''" />
                            <p v-if="form.errors.email" class="text-xs text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input id="phone" v-model="form.phone" type="tel" placeholder="08012345678"
                                autocomplete="tel" :class="form.errors.phone ? 'border-destructive' : ''" />
                            <p v-if="form.errors.phone" class="text-xs text-destructive">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <!-- Submit -->
                        <Button type="submit" class="w-full" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Complete Profile' }}
                        </Button>

                    </form>
                </CardContent>
            </Card>

            <!-- Footer -->
            <p class="text-center text-xs" style="color: oklch(0.65 0.06 150)">
                Having trouble? Contact your administrator.
            </p>

        </div>
    </div>
</template>
