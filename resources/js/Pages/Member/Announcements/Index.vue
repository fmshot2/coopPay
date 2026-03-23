<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Pin, MessageSquare, Trash2 } from 'lucide-vue-next'

const props = defineProps({
    announcements: Array,
})

// Comment form per announcement
const commentingId = ref(null)
const commentForm  = useForm({ body: '' })

const submitComment = (a) => {
    commentForm.post(route('member.announcements.comment', a.id), {
        onSuccess: () => {
            commentingId.value = null
            commentForm.reset()
        },
    })
}

const deleteComment = (comment) => {
    if (confirm('Delete your comment?')) {
        router.delete(route('member.announcements.comments.destroy', comment.id))
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">Announcements</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Latest updates from the cooperative
                </p>
            </div>

            <!-- Empty state -->
            <div v-if="announcements.length === 0" class="text-center py-10">
                <MessageSquare class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                <p class="text-sm text-muted-foreground">No announcements yet</p>
            </div>

            <!-- Announcements -->
            <Card v-for="a in announcements" :key="a.id">
                <CardHeader>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <Pin v-if="a.is_pinned" class="h-4 w-4 text-primary" />
                            <CardTitle class="text-base">{{ a.title }}</CardTitle>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Posted by {{ a.author }} · {{ a.published_at }}
                        </p>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">

                    <!-- Body -->
                    <p class="text-sm text-foreground whitespace-pre-line">{{ a.body }}</p>

                    <!-- Comments -->
                    <div class="border-t pt-4 space-y-3">
                        <p class="text-xs font-medium text-muted-foreground">
                            {{ a.comments.length }} comment{{ a.comments.length !== 1 ? 's' : '' }}
                        </p>

                        <div
                            v-for="comment in a.comments"
                            :key="comment.id"
                            class="flex items-start justify-between bg-muted/40 rounded-md px-3 py-2"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-foreground">{{ comment.author }}</p>
                                <p class="text-sm text-foreground">{{ comment.body }}</p>
                                <p class="text-xs text-muted-foreground">{{ comment.created_at }}</p>
                            </div>
                            <Button
                                v-if="comment.is_mine"
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:text-destructive h-6 w-6"
                                @click="deleteComment(comment)"
                            >
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>

                        <!-- Add comment -->
                        <div v-if="commentingId === a.id" class="space-y-2">
                            <textarea
                                v-model="commentForm.body"
                                rows="2"
                                placeholder="Write a comment..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                            />
                            <p v-if="commentForm.errors.body" class="text-xs text-destructive">
                                {{ commentForm.errors.body }}
                            </p>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    :disabled="commentForm.processing"
                                    @click="submitComment(a)"
                                >
                                    {{ commentForm.processing ? 'Posting...' : 'Post Comment' }}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="commentingId = null; commentForm.reset()"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            @click="commentingId = a.id"
                        >
                            <MessageSquare class="h-4 w-4 mr-2" />
                            Comment
                        </Button>
                    </div>

                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
