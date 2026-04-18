<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Mail, MailOpen, Trash2, Plus, Eye } from 'lucide-vue-next'

const props = defineProps({
    messages: Object,
    unreadCount: Number,
})

const deleteMessage = (message) => {
    if (confirm('Are you sure you want to delete this message?')) {
        router.delete(route('member.messages.destroy', message.id))
    }
}

const markAsRead = (message) => {
    if (!message.is_read) {
        router.patch(route('member.messages.read', message.id), {}, {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Messages</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        View and send messages to admin
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Badge v-if="unreadCount > 0" variant="destructive">
                        {{ unreadCount }} unread
                    </Badge>
                    <Button @click="router.visit(route('member.messages.create'))">
                        <Plus class="h-4 w-4 mr-2" />
                        New Message
                    </Button>
                </div>
            </div>

            <!-- Messages List -->
            <div v-if="messages.data.length === 0" class="text-center py-10">
                <Mail class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                <p class="text-sm text-muted-foreground">No messages yet</p>
            </div>

            <div v-else class="space-y-3">
                <Card v-for="message in messages.data" :key="message.id"
                    :class="{ 'border-primary/50 bg-primary/5': !message.is_read }">
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div class="mt-1">
                                    <MailOpen v-if="message.is_read" class="h-5 w-5 text-muted-foreground" />
                                    <Mail v-else class="h-5 w-5 text-primary" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-sm font-medium text-foreground truncate">
                                            {{ message.subject || 'No Subject' }}
                                        </p>
                                        <Badge v-if="!message.is_read" variant="secondary" class="text-xs">
                                            New
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground mb-2">
                                        From: {{ message.sender?.name || 'Unknown' }} · {{ message.created_at }}
                                    </p>
                                    <p class="text-sm text-muted-foreground line-clamp-2">
                                        {{ message.body }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button variant="ghost" size="icon"
                                    @click="router.visit(route('member.messages.show', message.id))">
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive"
                                    @click="deleteMessage(message)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div v-if="messages.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Showing {{ messages.from }} to {{ messages.to }} of {{ messages.total }} messages
                </p>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="messages.current_page === 1"
                        @click="router.visit(messages.prev_page_url)">
                        Previous
                    </Button>
                    <Button variant="outline" size="sm" :disabled="messages.current_page === messages.last_page"
                        @click="router.visit(messages.next_page_url)">
                        Next
                    </Button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
