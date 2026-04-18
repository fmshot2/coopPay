<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ArrowLeft, Mail, MailOpen, Send, Trash2 } from 'lucide-vue-next'

const props = defineProps({
    message: Object,
})

// Reply form
const showReply = ref(false)
const replyForm = useForm({
    body: '',
})

const submitReply = () => {
    replyForm.post(route('admin.messages.reply', props.message.id), {
        onSuccess: () => {
            showReply.value = false
            replyForm.reset()
        },
    })
}

const submitEmailReply = () => {
    replyForm.post(route('admin.messages.reply-email', props.message.id), {
        onSuccess: () => {
            showReply.value = false
            replyForm.reset()
        },
    })
}

const deleteMessage = () => {
    if (confirm('Are you sure you want to delete this message?')) {
        router.delete(route('admin.messages.destroy', props.message.id))
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" @click="router.visit(route('admin.messages.index'))">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">Message Details</h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            View and reply to message
                        </p>
                    </div>
                </div>
                <Button variant="destructive" @click="deleteMessage">
                    <Trash2 class="h-4 w-4 mr-2" />
                    Delete
                </Button>
            </div>

            <!-- Message Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <MailOpen v-if="message.is_read" class="h-5 w-5 text-muted-foreground" />
                                <Mail v-else class="h-5 w-5 text-primary" />
                                <CardTitle class="text-base">{{ message.subject || 'No Subject' }}</CardTitle>
                                <Badge v-if="!message.is_read" variant="secondary">New</Badge>
                            </div>
                            <CardDescription>
                                From: {{ message.sender?.name || 'Unknown' }} · {{ message.created_at }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="bg-muted/40 rounded-md p-4">
                        <p class="text-sm text-foreground whitespace-pre-line">{{ message.body }}</p>
                    </div>

                    <!-- Reply Section -->
                    <div class="border-t pt-4">
                        <div v-if="!showReply" class="flex gap-2">
                            <Button @click="showReply = true">
                                <Send class="h-4 w-4 mr-2" />
                                Reply In-App
                            </Button>
                            <Button variant="outline" @click="showReply = true">
                                <Mail class="h-4 w-4 mr-2" />
                                Reply via Email
                            </Button>
                        </div>

                        <div v-else class="space-y-3">
                            <textarea v-model="replyForm.body" rows="4" placeholder="Write your reply..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                :class="replyForm.errors.body ? 'border-destructive' : ''" />
                            <p v-if="replyForm.errors.body" class="text-xs text-destructive">
                                {{ replyForm.errors.body }}
                            </p>
                            <div class="flex gap-2">
                                <Button @click="submitReply" :disabled="replyForm.processing">
                                    {{ replyForm.processing ? 'Sending...' : 'Send Reply' }}
                                </Button>
                                <Button variant="outline" @click="submitEmailReply" :disabled="replyForm.processing">
                                    {{ replyForm.processing ? 'Sending...' : 'Send via Email' }}
                                </Button>
                                <Button variant="outline" @click="showReply = false; replyForm.reset()">
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
