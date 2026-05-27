<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Pagination } from '@/components/ui/pagination'
import { Search, Calendar, CheckCircle, XCircle, ExternalLink, Wallet } from 'lucide-vue-next'

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
const perPage = ref(props.filters?.per_page?.toString() || '10')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}

const statusVariant = (status) => {
    if (status === 'approved') return 'default'
    if (status === 'pending') return 'secondary'
    if (status === 'rejected') return 'destructive'
    return 'outline'
}

const updateFilters = () => {
    router.get(route('admin.savings.index'), {
        search: search.value || undefined,
        status: status.value,
        date_filter: dateFilter.value,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

watchDebounced(
    [search, status, dateFilter, fromDate, toDate, perPage],
    updateFilters,
    { debounce: 400, maxWait: 800 }
)

const approve = (contribution) => {
    if (confirm(`Approve ${formatCurrency(contribution.amount)} from ${contribution.member_name}?`)) {
        router.patch(route('admin.contributions.approve-savings', contribution.id))
    }
}

const rejectingId = ref(null)
const rejectForm = useForm({ admin_note: '' })

const startReject = (contribution) => {
    rejectingId.value = contribution.id
    rejectForm.admin_note = ''
}

const cancelReject = () => {
    rejectingId.value = null
    rejectForm.reset()
}

const submitReject = (contribution) => {
    rejectForm.patch(route('admin.contributions.reject-savings', contribution.id), {
        onSuccess: () => {
            rejectingId.value = null
            rejectForm.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Savings Contributions</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Review all member savings contributions and approve or reject pending submissions.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 border border-primary/20 rounded-lg px-4 py-2">
                        <p class="text-xs text-muted-foreground">Pending</p>
                        <p class="text-lg font-semibold text-foreground">{{ stats?.total_pending ?? 0 }}</p>
                    </div>
                    <div class="bg-background rounded-lg px-4 py-2 border border-border">
                        <p class="text-xs text-muted-foreground">Approved</p>
                        <p class="text-lg font-semibold text-foreground">{{ stats?.total_approved ?? 0 }}</p>
                    </div>
                    <div class="bg-background rounded-lg px-4 py-2 border border-border">
                        <p class="text-xs text-muted-foreground">Rejected</p>
                        <p class="text-lg font-semibold text-foreground">{{ stats?.total_rejected ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search member name or ID..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl"
                                />
                            </div>
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
                                    <SelectItem value="custom">Custom Range</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div v-if="dateFilter === 'custom'" class="flex flex-wrap items-center gap-2 mt-4">
                        <Input v-model="fromDate" type="date" class="w-36 h-10 text-xs" />
                        <span class="text-muted-foreground">to</span>
                        <Input v-model="toDate" type="date" class="w-36 h-10 text-xs" />
                    </div>
                </CardContent>
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
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Narration</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Receipt</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Date</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <template v-for="contribution in savings.data" :key="contribution.id">
                                        <tr class="hover:bg-muted/20 transition-colors">
                                            <td class="py-4 px-6">
                                                <p class="font-medium text-foreground">{{ contribution.member_name }}</p>
                                                <p class="text-xs text-muted-foreground font-mono">{{ contribution.member_id }}</p>
                                            </td>
                                            <td class="py-4 px-6 text-right font-medium text-primary">
                                                {{ formatCurrency(contribution.amount) }}
                                            </td>
                                            <td class="py-4 px-6 text-muted-foreground text-xs max-w-56 truncate">
                                                {{ contribution.narration || '—' }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <template v-if="contribution.screenshot_path">
                                                    <a :href="`/storage/${contribution.screenshot_path}`" target="_blank"
                                                        class="text-xs text-primary flex items-center gap-1 hover:underline">
                                                        <ExternalLink class="h-3 w-3" />
                                                        View
                                                    </a>
                                                </template>
                                                <template v-else>
                                                    <span class="text-xs text-muted-foreground">—</span>
                                                </template>
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge :variant="statusVariant(contribution.status)"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ contribution.status }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">{{ contribution.created_at }}</td>
                                            <td class="py-4 px-6 text-right">
                                                <div v-if="contribution.status === 'pending'" class="flex items-center justify-end gap-2">
                                                    <Button size="sm" class="h-8 rounded-lg text-xs"
                                                        @click="approve(contribution)">
                                                        <CheckCircle class="h-3 w-3 mr-1" />
                                                        Approve
                                                    </Button>
                                                    <Button size="sm" variant="ghost"
                                                        class="h-8 rounded-lg text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        @click="startReject(contribution)">
                                                        <XCircle class="h-3 w-3 mr-1" />
                                                        Reject
                                                    </Button>
                                                </div>
                                                <span v-else class="text-xs text-muted-foreground px-4">—</span>
                                            </td>
                                        </tr>

                                        <tr v-if="rejectingId === contribution.id" class="bg-destructive/5">
                                            <td colspan="7" class="px-6 py-3">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                                    <textarea
                                                        v-model="rejectForm.admin_note"
                                                        rows="2"
                                                        placeholder="Reason for rejection (required)..."
                                                        class="flex-1 border border-destructive/50 rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-destructive/30 resize-none"
                                                    />
                                                    <div class="flex items-center gap-2">
                                                        <Button size="sm" variant="destructive" class="h-8 rounded-lg text-xs"
                                                            :disabled="rejectForm.processing"
                                                            @click="submitReject(contribution)">
                                                            {{ rejectForm.processing ? 'Rejecting...' : 'Confirm' }}
                                                        </Button>
                                                        <Button size="sm" variant="outline" class="h-8 rounded-lg text-xs"
                                                            @click="cancelReject">
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                </div>
                                                <p v-if="rejectForm.errors.admin_note" class="text-xs text-destructive mt-2">
                                                    {{ rejectForm.errors.admin_note }}
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="savings.last_page > 1" class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
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
        </div>
    </AppLayout>
</template>
