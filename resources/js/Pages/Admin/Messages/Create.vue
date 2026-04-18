<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ArrowLeft, Send } from 'lucide-vue-next'

const props = defineProps({
    members: Array,
})

const form = useForm({
    receiver_id: '',
    subject: '',
    body: '',
})

const submit = () => {
    form.post(route('admin.messages.send'), {
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="router.visit(route('admin.messages.index'))">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">New Message</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Send a message to a cooperative member
                    </p>
                </div>
            </div>

            <!-- Message Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Compose Message</CardTitle>
                    <CardDescription>
                        Fill in the details below to send a message
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="receiver_id">Recipient <span class="text-destructive">*</span></Label>
                            <select v-model="form.receiver_id" id="receiver_id"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                :class="form.errors.receiver_id ? 'border-destructive' : ''">
                                <option value="">Select a member...</option>
                                <option v-for="member in members" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.email }})
                                </option>
                            </select>
                            <p v-if="form.errors.receiver_id" class="text-xs text-destructive">
                                {{ form.errors.receiver_id }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="subject">Subject</Label>
                            <Input v-model="form.subject" id="subject" placeholder="Message subject (optional)"
                                :class="form.errors.subject ? 'border-destructive' : ''" />
                            <p v-if="form.errors.subject" class="text-xs text-destructive">
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="body">Message <span class="text-destructive">*</span></Label>
                            <textarea v-model="form.body" id="body" rows="6" placeholder="Write your message..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                :class="form.errors.body ? 'border-destructive' : ''" />
                            <p v-if="form.errors.body" class="text-xs text-destructive">
                                {{ form.errors.body }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing">
                                <Send class="h-4 w-4 mr-2" />
                                {{ form.processing ? 'Sending...' : 'Send Message' }}
                            </Button>
                            <Button variant="outline" type="button" @click="router.visit(route('admin.messages.index'))">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
