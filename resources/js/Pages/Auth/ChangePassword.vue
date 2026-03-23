<script setup>
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const form = useForm({
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.change.post'), {
        onFinish: () => form.reset(),
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
                    Please set a new password to continue
                </p>
            </div>

            <!-- Card -->
            <Card>
                <CardHeader>
                    <CardTitle>Change your password</CardTitle>
                    <CardDescription>
                        Your administrator requires you to set a new password before continuing.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- New Password -->
                        <div class="space-y-2">
                            <Label for="password">New Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                :class="form.errors.password ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.password" class="text-xs text-destructive">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                :class="form.errors.password_confirmation ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.password_confirmation" class="text-xs text-destructive">
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Submit -->
                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Saving...' : 'Set new password' }}
                        </Button>

                    </form>
                </CardContent>
            </Card>

        </div>
    </div>
</template>
