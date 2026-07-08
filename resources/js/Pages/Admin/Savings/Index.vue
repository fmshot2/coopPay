<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Pagination } from '@/components/ui/pagination'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import { Search, Calendar, CheckCircle, XCircle, ExternalLink, Wallet, MoreHorizontal, Pencil } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

const props = defineProps({
    savings: Object,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')
const perPage = ref(props.filters?.per_page?.toString() || '50')

const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 }).format(amount ?? 0)

const statusVariant = (s) => ({ approved: 'default', pending: 'secondary', rejected: 'destructive' })[s] ?? 'outline'

const updateFilters = () => {
    router.get(route('admin.savings.index'), {
        search: search.value || undefined,
        status: status.value,
        date_filter: dateFilter.value,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
        per_page: perPage.value,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

watchDebounced(
    [search, status, dateFilter, fromDate, toDate, perPage],
    updateFilters,
    { debounce: 400, maxWait: 800 }
)

// --- Approve ---
const approve = (contribution) => {
    if (confirm(`Approve ${formatCurrency(contribution.amount)} from ${contribution.member_name}?`)) {
        router.patch(route('admin.contributions.approve-savings', contribution.id), {}, {
            onSuccess: () => toast.success('Savings contribution approved'),
        })
    }
}

// --- Reject inline ---
const rejectingId = ref(null)
const rejectForm = useForm({ admin_note: '' })

const startReject = (c) => { rejectingId.value = c.id; rejectForm.admin_note = '' }
const cancelReject = () => { rejectingId.value = null; rejectForm.reset() }

const submitReject = (c) => {
    rejectForm.patch(route('admin.contributions.reject-savings', c.id), {
        onSuccess: () => { rejectingId.value = null; rejectForm.reset(); toast.success('Contribution rejected') },
    })
}

// --- Edit modal ---
const editOpen = ref(false)
const editForm = useForm({
    id: null,
    amount: '',
    narration: '',
    admin_note: '',
    status: '',
})

const openEdit = (c) => {
    editForm.id = c.id
    editForm.amount = c.amount
    editForm.narration = c.narration ?? ''
    editForm.admin_note = c.admin_note ?? ''
    editForm.status = c.status
    editOpen.value = true
}

const submitEdit = () => {
    editForm.patch(route('admin.savings.update', editForm.id), {
        onSuccess: () => {
            editOpen.value = false
            editForm.reset()
            toast.success('Savings record updated')
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Header (unchanged) -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Savings Contributions</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Review all member savings contributions.
                    </p>
                </div>
                <!-- <div class="flex items-center gap-3">
                    <div class="bg-primary/10 border border-primary/20 rounded-lg px-4 py-2">
                        <p class="text-xs text-muted-foreground">Pending</p>
                        <p class="text-lg font-semibold">{{ stats?.total_pending ?? 0 }}</p>
                    </div>
                    <div class="bg-background rounded-lg px-4 py-2 border border-border">
                        <p class="text-xs text-muted-foreground">Approved</p>
                        <p class="text-lg font-semibold">{{ stats?.total_approved ?? 0 }}</p>
                    </div>
                    <div class="bg-background rounded-lg px-4 py-2 border border-border">
                        <p class="text-xs text-muted-foreground">Rejected</p>
                        <p class="text-lg font-semibold">{{ stats?.total_rejected ?? 0 }}</p>
                    </div>
                </div> -->
            </div>

            <!-- Stat Cards -->
            <Deferred data="stats">
                <template #fallback>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card v-for="i in 3" :key="i" class="animate-pulse">
                            <CardHeader class="pb-2">
                                <div class="h-4 w-24 bg-muted rounded" />
                            </CardHeader>
                            <CardContent>
                                <div class="h-8 w-20 bg-muted rounded" />
                            </CardContent>
                        </Card>
                    </div>
                </template>
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Pending</CardTitle>
                            <Clock class="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ stats?.total_pending ?? 0 }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Approved</CardTitle>
                            <CheckSquare class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ stats?.total_approved ?? 0 }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Rejected</CardTitle>
                            <AlertCircle class="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ stats?.total_rejected ?? 0 }}</p>
                        </CardContent>
                    </Card>
                </div>
            </Deferred>

            <!-- Filters -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input v-model="search" placeholder="Search member name, email, ID..."
                                class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl" />
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <Select v-model="status">
                                <SelectTrigger class="w-36 h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="dateFilter">
                                <SelectTrigger class="w-40 h-10 bg-background border-none shadow-sm rounded-xl">
                                    <Calendar class="h-4 w-4 mr-2 opacity-50" />
                                    <SelectValue placeholder="Date Range" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Time</SelectItem>
                                    <SelectItem value="today">Today</SelectItem>
                                    <SelectItem value="last_week">Last 7 Days</SelectItem>
                                    <SelectItem value="last_month">Last 30 Days</SelectItem>
                                    <SelectItem value="last_year">Last 1 Year</SelectItem>
                                    <!-- <SelectItem value="custom">Custom Range</SelectItem> -->
                                </SelectContent>
                            </Select>
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- you can toggle this using dateFilter value -->
                                <!-- <div v-if="dateFilter === 'custom'" class="flex flex-wrap items-center gap-2 mt-4"> -->
                                <div class="relative flex-1 max-w-md">
                                    <Input v-model="fromDate" type="date" class="w-36 h-10 text-xs" />
                                    <Calendar
                                        class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500 pointer-events-none" />
                                </div>
                                <span class="text-muted-foreground">to</span>
                                <div class="relative flex-1 max-w-md">
                                    <Calendar
                                        class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500 pointer-events-none" />
                                    <Input v-model="toDate" type="date" class="w-36 h-10 text-xs" />
                                </div>
                            </div>
                        </div>
                    </div>

                </CardHeader>
            </Card>

            <!-- Table -->
            <Card class="border-none shadow-none bg-transparent">
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <div v-if="savings.data.length === 0" class="text-center py-20">
                            <Wallet class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No savings contributions found</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Period</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Narration</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Receipt</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Last Update</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <template v-for="c in savings.data" :key="c.id">
                                        <tr class="hover:bg-muted/20 transition-colors">
                                            <td class="py-4 px-6">
                                                <p class="font-medium text-foreground">{{ c.member_name }}</p>
                                                <p class="text-xs text-muted-foreground font-mono">{{ c.member_id }}</p>
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">
                                                {{ c.month }}
                                            </td>
                                            <td class="py-4 px-6 text-right font-medium text-primary">
                                                {{ formatCurrency(c.amount) }}
                                            </td>
                                            <td class="py-4 px-6 text-muted-foreground text-xs max-w-48 truncate">
                                                {{ c.narration || '—' }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <a v-if="c.screenshot_path" :href="`/storage/${c.screenshot_path}`"
                                                    target="_blank"
                                                    class="text-xs text-primary flex items-center gap-1 hover:underline">
                                                    <ExternalLink class="h-3 w-3" /> View
                                                </a>
                                                <span v-else class="text-xs text-muted-foreground">—</span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge :variant="statusVariant(c.status)"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ c.status }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">{{ c.created_at }}</td>

                                            <!-- Actions dropdown -->
                                            <td class="py-4 px-6 text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger as-child>
                                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg">
                                                            <MoreHorizontal class="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" class="w-48">
                                                        <!-- Edit always available -->
                                                        <DropdownMenuItem class="cursor-pointer" @click="openEdit(c)">
                                                            <Pencil class="h-4 w-4 mr-2" />
                                                            Update savings record
                                                        </DropdownMenuItem>

                                                        <!-- Approve/Reject only when pending -->
                                                        <template v-if="c.status === 'pending'">
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem
                                                                class="cursor-pointer text-primary focus:text-primary"
                                                                @click="approve(c)">
                                                                <CheckCircle class="h-4 w-4 mr-2" />
                                                                Approve
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                class="cursor-pointer text-destructive focus:text-destructive"
                                                                @click="startReject(c)">
                                                                <XCircle class="h-4 w-4 mr-2" />
                                                                Reject
                                                            </DropdownMenuItem>
                                                        </template>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </td>
                                        </tr>

                                        <!-- Inline reject row -->
                                        <tr v-if="rejectingId === c.id" class="bg-destructive/5">
                                            <td colspan="8" class="px-6 py-3">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                                    <textarea v-model="rejectForm.admin_note" rows="2"
                                                        placeholder="Reason for rejection (required)..."
                                                        class="flex-1 border border-destructive/50 rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-destructive/30 resize-none" />
                                                    <div class="flex items-center gap-2">
                                                        <Button size="sm" variant="destructive"
                                                            class="h-8 rounded-lg text-xs"
                                                            :disabled="rejectForm.processing" @click="submitReject(c)">
                                                            {{ rejectForm.processing ? 'Rejecting...' : 'Confirm' }}
                                                        </Button>
                                                        <Button size="sm" variant="outline"
                                                            class="h-8 rounded-lg text-xs" @click="cancelReject">
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                </div>
                                                <p v-if="rejectForm.errors.admin_note"
                                                    class="text-xs text-destructive mt-2">
                                                    {{ rejectForm.errors.admin_note }}
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="savings.last_page > 1"
                        class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-muted-foreground">Rows per page</span>
                            <Select v-model="perPage">
                                <SelectTrigger class="w-18 h-8 bg-background border-none shadow-sm rounded-lg text-xs">
                                    <SelectValue :placeholder="perPage" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10</SelectItem>
                                    <SelectItem value="25">25</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Pagination :links="savings.links" />
                    </div>
                </CardContent>
            </Card>

            <!-- Edit Modal -->
            <Dialog v-model:open="editOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Savings Contribution</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="edit-amount">Amount (₦)</Label>
                            <Input id="edit-amount" v-model="editForm.amount" type="number" min="0" step="0.01"
                                :class="editForm.errors.amount ? 'border-destructive' : ''" />
                            <p v-if="editForm.errors.amount" class="text-xs text-destructive">{{ editForm.errors.amount
                            }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit-status">Status</Label>
                            <Select v-model="editForm.status">
                                <SelectTrigger id="edit-status"
                                    :class="editForm.errors.status ? 'border-destructive' : ''">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="editForm.errors.status" class="text-xs text-destructive">{{ editForm.errors.status
                            }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit-narration">Narration</Label>
                            <textarea id="edit-narration" v-model="editForm.narration" rows="2"
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                placeholder="Optional note..." />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit-admin-note">Admin Note</Label>
                            <textarea id="edit-admin-note" v-model="editForm.admin_note" rows="2"
                                class="w-full border border-input rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                placeholder="Internal note..." />
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button variant="outline" @click="editOpen = false">Cancel</Button>
                        <Button :disabled="editForm.processing" @click="submitEdit">
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>
