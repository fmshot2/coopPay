<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Pagination } from '@/components/ui/pagination'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Pin, Pencil, Trash2, MessageSquare, Plus, Search, Calendar, Eye } from 'lucide-vue-next'

const props = defineProps({
    announcements: Object,
    filters:       Object,
})

// Filters
const search     = ref(props.filters?.search || '')
const pinned     = ref(props.filters?.pinned || 'all')
const dateFrom   = ref(props.filters?.date_from || '')
const dateTo     = ref(props.filters?.date_to || '')
const perPage    = ref(props.filters?.per_page?.toString() || '10')

const updateFilters = () => {
    router.get(route('admin.announcements.index'), {
        search:    search.value || undefined,
        pinned:    pinned.value !== 'all' ? pinned.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value || undefined,
        per_page:  perPage.value,
    }, {
        preserveState:  true,
        preserveScroll: true,
        replace:        true,
    })
}

watchDebounced(
    [search, pinned, dateFrom, dateTo, perPage],
    updateFilters,
    { debounce: 500, maxWait: 1000 }
)

// Truncate helper
const truncate = (str, length = 60) => {
    if (!str) return ''
    return str.length > length ? str.slice(0, length) + '...' : str
}

// ===== Modal =====
const modalOpen        = ref(false)
const selectedAnnouncement = ref(null)

const openModal = (a) => {
    selectedAnnouncement.value = a
    modalOpen.value = true
    commentForm.reset()
}

const closeModal = () => {
    modalOpen.value = false
    selectedAnnouncement.value = null
}

// Comment form
const commentForm = useForm({ body: '' })

const submitComment = () => {
    if (!selectedAnnouncement.value) return
    commentForm.post(route('admin.announcements.comment', selectedAnnouncement.value.id), {
        onSuccess: () => {
            commentForm.reset()
        },
        preserveScroll: true,
    })
}

const deleteComment = (comment) => {
    if (confirm('Delete this comment?')) {
        router.delete(route('admin.announcements.comments.destroy', comment.id), {
            preserveScroll: true,
        })
    }
}

// ===== Create =====
const showCreate  = ref(false)
const createForm  = useForm({
    title:        '',
    body:         '',
    is_pinned:    false,
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

// ===== Edit =====
const editingId = ref(null)
const editForm  = useForm({
    title:        '',
    body:         '',
    is_pinned:    false,
    published_at: '',
})

const startEdit = (a) => {
    editingId.value       = a.id
    editForm.title        = a.title
    editForm.body         = a.body
    editForm.is_pinned    = a.is_pinned
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
    if (confirm(`Delete "${a.title}"?`)) {
        router.delete(route('admin.announcements.destroy', a.id))
    }
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Announcements</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Post and manage announcements for cooperative members
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Date From -->
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <Input v-model="dateFrom" type="date" class="w-[140px] h-9 text-xs" placeholder="From" />
                        <span class="text-muted-foreground text-xs">—</span>
                        <Input v-model="dateTo" type="date" class="w-[140px] h-9 text-xs" placeholder="To" />
                    </div>
                    <Button @click="showCreate = !showCreate">
                        <Plus class="h-4 w-4 mr-2" />
                        New Announcement
                    </Button>
                </div>
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
                            <Input
                                v-model="createForm.title"
                                placeholder="Announcement title..."
                                :class="createForm.errors.title ? 'border-destructive' : ''"
                            />
                            <p v-if="createForm.errors.title" class="text-xs text-destructive">
                                {{ createForm.errors.title }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label>Body <span class="text-destructive">*</span></Label>
                            <textarea
                                v-model="createForm.body"
                                rows="4"
                                placeholder="Announcement content..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                :class="createForm.errors.body ? 'border-destructive' : ''"
                            />
                            <p v-if="createForm.errors.body" class="text-xs text-destructive">
                                {{ createForm.errors.body }}
                            </p>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <input id="is_pinned" v-model="createForm.is_pinned" type="checkbox" class="rounded border-input" />
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
                            <Button variant="outline" type="button" @click="showCreate = false">Cancel</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search title or body..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl"
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Select v-model="pinned">
                                <SelectTrigger class="w-[140px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="yes">Pinned</SelectItem>
                                    <SelectItem value="no">Not Pinned</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">

                        <!-- Empty -->
                        <div v-if="announcements.data.length === 0" class="text-center py-20">
                            <MessageSquare class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No announcements found</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Title</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Body</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Pinned</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Comments</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Author</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Published</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <template v-for="a in announcements.data" :key="a.id">
                                        <!-- Normal row -->
                                        <tr v-if="editingId !== a.id" class="hover:bg-muted/20 transition-colors">
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-2">
                                                    <Pin v-if="a.is_pinned" class="h-3 w-3 text-primary shrink-0" />
                                                    <span class="font-medium text-foreground">{{ truncate(a.title, 40) }}</span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-muted-foreground max-w-[200px]">
                                                {{ truncate(a.body, 60) }}
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <Badge v-if="a.is_pinned" class="text-[10px]">Pinned</Badge>
                                                <span v-else class="text-xs text-muted-foreground">—</span>
                                            </td>
                                            <td class="py-4 px-6 text-center text-muted-foreground">
                                                {{ a.comments.length }}
                                            </td>
                                            <td class="py-4 px-6 text-muted-foreground text-xs">{{ a.author }}</td>
                                            <td class="py-4 px-6 text-muted-foreground text-xs">{{ a.published_at }}</td>
                                            <td class="py-4 px-6 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="h-8 w-8 rounded-lg"
                                                        @click="openModal(a)"
                                                    >
                                                        <Eye class="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="h-8 w-8 rounded-lg"
                                                        @click="startEdit(a)"
                                                    >
                                                        <Pencil class="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        class="h-8 w-8 rounded-lg text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        @click="destroy(a)"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Inline edit row -->
                                        <tr v-else class="bg-muted/10">
                                            <td colspan="7" class="px-6 py-4">
                                                <form @submit.prevent="submitEdit(a)" class="space-y-3">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <Input v-model="editForm.title" placeholder="Title" />
                                                        <div class="flex items-center gap-4">
                                                            <div class="flex items-center gap-2">
                                                                <input id="edit_pinned" v-model="editForm.is_pinned" type="checkbox" />
                                                                <Label for="edit_pinned" class="font-normal text-sm">Pinned</Label>
                                                            </div>
                                                            <Input v-model="editForm.published_at" type="datetime-local" class="flex-1" />
                                                        </div>
                                                    </div>
                                                    <textarea
                                                        v-model="editForm.body"
                                                        rows="3"
                                                        class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                                    />
                                                    <div class="flex gap-2">
                                                        <Button type="submit" size="sm" :disabled="editForm.processing">
                                                            {{ editForm.processing ? 'Saving...' : 'Save' }}
                                                        </Button>
                                                        <Button variant="outline" size="sm" type="button" @click="cancelEdit">
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="announcements.last_page > 1" class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-muted-foreground">Rows per page</span>
                                <Select v-model="perPage" @update:modelValue="updateFilters">
                                    <SelectTrigger class="w-[70px] h-8 bg-background border-none shadow-sm rounded-lg text-xs">
                                        <SelectValue :placeholder="perPage" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <p class="text-xs font-medium text-muted-foreground">
                                Showing {{ announcements.from }}-{{ announcements.to }} of {{ announcements.total }}
                            </p>
                        </div>
                        <Pagination :links="announcements.links" />
                    </div>
                </CardContent>
            </Card>

        </div>

        <!-- ===== VIEW MODAL ===== -->
        <Dialog :open="modalOpen" @update:open="closeModal">
            <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <div class="flex items-center gap-2">
                        <Pin v-if="selectedAnnouncement?.is_pinned" class="h-4 w-4 text-primary" />
                        <DialogTitle>{{ selectedAnnouncement?.title }}</DialogTitle>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Posted by {{ selectedAnnouncement?.author }} · {{ selectedAnnouncement?.published_at }}
                    </p>
                </DialogHeader>

                <!-- Body -->
                <div class="py-4 border-b">
                    <p class="text-sm text-foreground whitespace-pre-line leading-relaxed">
                        {{ selectedAnnouncement?.body }}
                    </p>
                </div>

                <!-- Comments -->
                <div class="space-y-4 pt-2">
                    <p class="text-sm font-medium text-foreground">
                        {{ selectedAnnouncement?.comments?.length ?? 0 }} Comment{{ selectedAnnouncement?.comments?.length !== 1 ? 's' : '' }}
                    </p>

                    <div class="space-y-3 max-h-48 overflow-y-auto">
                        <div
                            v-for="comment in selectedAnnouncement?.comments"
                            :key="comment.id"
                            class="flex items-start justify-between bg-muted/40 rounded-md px-3 py-2"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-medium text-foreground">{{ comment.author }}</p>
                                <p class="text-sm text-foreground">{{ comment.body }}</p>
                                <p class="text-xs text-muted-foreground">{{ comment.created_at }}</p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-6 w-6 text-destructive hover:text-destructive shrink-0"
                                @click="deleteComment(comment)"
                            >
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>
                        <div v-if="!selectedAnnouncement?.comments?.length" class="text-center py-4">
                            <p class="text-xs text-muted-foreground">No comments yet</p>
                        </div>
                    </div>

                    <!-- Add Comment -->
                    <div class="border-t pt-4 space-y-2">
                        <Label class="text-sm">Add a comment</Label>
                        <textarea
                            v-model="commentForm.body"
                            rows="2"
                            placeholder="Write a comment..."
                            class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                            :class="commentForm.errors.body ? 'border-destructive' : ''"
                        />
                        <p v-if="commentForm.errors.body" class="text-xs text-destructive">
                            {{ commentForm.errors.body }}
                        </p>
                        <Button
                            size="sm"
                            :disabled="commentForm.processing || !commentForm.body"
                            @click="submitComment"
                        >
                            <MessageSquare class="h-4 w-4 mr-2" />
                            {{ commentForm.processing ? 'Posting...' : 'Post Comment' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
