<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'), {
        onFinish: () => form.reset('email'),
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <div class="min-h-screen bg-background flex items-center justify-center p-4">
        <div class="w-full max-w-md space-y-6">

            <!-- Header -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary mb-2">
                    <span class="text-primary-foreground font-bold text-2xl">C</span>
                </div>
                <h1 class="text-2xl font-bold text-foreground">CoopPay</h1>
                <p class="text-sm text-muted-foreground">
                    Enter your email to reset your password
                </p>
            </div>

            <!-- Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Reset your password</CardTitle>
                    <CardDescription>
                        We'll send you a link to reset your password.
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

                        <!-- Submit -->
                        <Button type="submit" class="w-full" :disabled="form.processing">
                            {{ form.processing ? 'Sending...' : 'Send reset link' }}
                        </Button>

                    </form>
                </CardContent>
            </Card>

            <!-- Success message -->
            <p v-if="form.status" class="text-center text-xs text-muted-foreground">
                {{ form.status }}
            </p>

        </div>
    </div>
</template>