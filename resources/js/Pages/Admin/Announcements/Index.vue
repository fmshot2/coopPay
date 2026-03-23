<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Pin, Pencil, Trash2, MessageSquare, Plus } from 'lucide-vue-next'

const props = defineProps({
    announcements: Array,
})

// Create form
const showCreate = ref(false)
const createForm = useForm({
    title: '',
    body: '',
    is_pinned: false,
    published_at: '',
})

const submitCreate = () => {
    createForm.post(route('admin.announcements.store'), {
        onSuccess: () => {
            showCreate.value = false
            createForm.reset()
        },
    })
}

// Edit form
const editingId = ref(null)
const editForm = useForm({
    title: '',
    body: '',
    is_pinned: false,
    published_at: '',
})

const startEdit = (a) => {
    editingId.value = a.id
    editForm.title = a.title
    editForm.body = a.body
    editForm.is_pinned = a.is_pinned
    editForm.published_at = a.published_at
}

const cancelEdit = () => {
    editingId.value = null
    editForm.reset()
}

const submitEdit = (a) => {
    editForm.patch(route('admin.announcements.update', a.id), {
        onSuccess: () => {
            editingId.value = null
        },
    })
}

const destroy = (a) => {
    if (confirm(`Delete announcement "${a.title}"?`)) {
        router.delete(route('admin.announcements.destroy', a.id))
    }
}

// Comment form
const commentingId = ref(null)
const commentForm = useForm({ body: '' })

const submitComment = (a) => {
    commentForm.post(route('admin.announcements.comment', a.id), {
        onSuccess: () => {
            commentingId.value = null
            commentForm.reset()
        },
    })
}

const deleteComment = (comment) => {
    if (confirm('Delete this comment?')) {
        router.delete(route('admin.announcements.comments.destroy', comment.id))
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Announcements</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Post and manage announcements for cooperative members
                    </p>
                </div>
                <Button @click="showCreate = !showCreate">
                    <Plus class="h-4 w-4 mr-2" />
                    New Announcement
                </Button>
            </div>

            <!-- Create Form -->
            <Card v-if="showCreate">
                <CardHeader>
                    <CardTitle class="text-base">New Announcement</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="space-y-2">
                            <Label>Title <span class="text-destructive">*</span></Label>
                            <Input v-model="createForm.title" placeholder="Announcement title..."
                                :class="createForm.errors.title ? 'border-destructive' : ''" />
                            <p v-if="createForm.errors.title" class="text-xs text-destructive">
                                {{ createForm.errors.title }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label>Body <span class="text-destructive">*</span></Label>
                            <textarea v-model="createForm.body" rows="4" placeholder="Announcement content..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                :class="createForm.errors.body ? 'border-destructive' : ''" />
                            <p v-if="createForm.errors.body" class="text-xs text-destructive">
                                {{ createForm.errors.body }}
                            </p>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <input id="is_pinned" v-model="createForm.is_pinned" type="checkbox"
                                    class="rounded border-input" />
                                <Label for="is_pinned" class="font-normal cursor-pointer">Pin this announcement</Label>
                            </div>
                            <div class="space-y-1 flex-1">
                                <Label>Schedule (optional)</Label>
                                <Input v-model="createForm.published_at" type="datetime-local" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" :disabled="createForm.processing">
                                {{ createForm.processing ? 'Posting...' : 'Post Announcement' }}
                            </Button>
                            <Button variant="outline" type="button" @click="showCreate = false">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Announcements List -->
            <div v-if="announcements.length === 0" class="text-center py-10">
                <MessageSquare class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                <p class="text-sm text-muted-foreground">No announcements yet</p>
            </div>

            <Card v-for="a in announcements" :key="a.id">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-1 flex-1">
                            <div class="flex items-center gap-2">
                                <Pin v-if="a.is_pinned" class="h-4 w-4 text-primary" />
                                <CardTitle class="text-base">{{ a.title }}</CardTitle>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Posted by {{ a.author }} · {{ a.published_at }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <Button variant="ghost" size="icon" @click="startEdit(a)">
                                <Pencil class="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive"
                                @click="destroy(a)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">

                    <!-- Edit form -->
                    <form v-if="editingId === a.id" @submit.prevent="submitEdit(a)" class="space-y-3">
                        <Input v-model="editForm.title" />
                        <textarea v-model="editForm.body" rows="4"
                            class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                        <div class="flex items-center gap-2">
                            <input id="edit_pinned" v-model="editForm.is_pinned" type="checkbox" />
                            <Label for="edit_pinned" class="font-normal">Pinned</Label>
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit" size="sm" :disabled="editForm.processing">
                                {{ editForm.processing ? 'Saving...' : 'Save' }}
                            </Button>
                            <Button variant="outline" size="sm" type="button" @click="cancelEdit">
                                Cancel
                            </Button>
                        </div>
                    </form>

                    <!-- Body -->
                    <p v-else class="text-sm text-foreground whitespace-pre-line">{{ a.body }}</p>

                    <!-- Comments -->
                    <div class="border-t pt-4 space-y-3">
                        <p class="text-xs font-medium text-muted-foreground">
                            {{ a.comments.length }} comment{{ a.comments.length !== 1 ? 's' : '' }}
                        </p>

                        <div v-for="comment in a.comments" :key="comment.id"
                            class="flex items-start justify-between bg-muted/40 rounded-md px-3 py-2">
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-foreground">{{ comment.author }}</p>
                                <p class="text-sm text-foreground">{{ comment.body }}</p>
                                <p class="text-xs text-muted-foreground">{{ comment.created_at }}</p>
                            </div>
                            <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive h-6 w-6"
                                @click="deleteComment(comment)">
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>

                        <!-- Add comment -->
                        <div v-if="commentingId === a.id" class="space-y-2">
                            <textarea v-model="commentForm.body" rows="2" placeholder="Write a comment..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                            <div class="flex gap-2">
                                <Button size="sm" :disabled="commentForm.processing" @click="submitComment(a)">
                                    {{ commentForm.processing ? 'Posting...' : 'Post Comment' }}
                                </Button>
                                <Button size="sm" variant="outline" @click="commentingId = null; commentForm.reset()">
                                    Cancel
                                </Button>
                            </div>
                        </div>
                        <Button v-else variant="outline" size="sm" @click="commentingId = a.id">
                            <MessageSquare class="h-4 w-4 mr-2" />
                            Add Comment
                        </Button>
                    </div>

                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
