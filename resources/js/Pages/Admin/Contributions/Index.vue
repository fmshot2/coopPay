<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Pagination } from '@/components/ui/pagination'
import { watchDebounced } from '@vueuse/core'
import { PiggyBank, Search, CheckCircle, XCircle, ExternalLink, Calendar } from 'lucide-vue-next'

const props = defineProps({
    contributions: Object,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const type = ref(props.filters?.type || 'all') // <--- MOVED THIS UP
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')
const perPage = ref(props.filters?.per_page?.toString() || '10')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const statusVariant = (s) => {
    if (s === 'approved') return 'default'
    if (s === 'rejected') return 'destructive'
    return 'outline'
}

const updateFilters = () => {
    router.get(route('admin.contributions.index'), {
        search: search.value || undefined,
        status: status.value,
        date_filter: dateFilter.value,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
        per_page: perPage.value,
        type: type.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

watchDebounced(
    [search, status, type, dateFilter, fromDate, toDate, perPage],
    updateFilters,
    { debounce: 500, maxWait: 1000 }
)

// Approve
const approve = (c) => {
    if (confirm(`Approve ${formatCurrency(c.amount)} from ${c.member_name}?`)) {
        const routeName = c.type === 'savings'
            ? 'admin.contributions.approve-savings'
            : 'admin.contributions.approve'
        router.patch(route(routeName, c.id))
    }
}

// Reject
const rejectingId = ref(null)
const rejectForm = useForm({ admin_note: '' })

const startReject = (c) => {
    rejectingId.value = c.id
    rejectForm.admin_note = ''
}

const cancelReject = () => {
    rejectingId.value = null
    rejectForm.reset()
}

const submitReject = (c) => {
    const routeName = c.type === 'savings'
        ? 'admin.contributions.reject-savings'
        : 'admin.contributions.reject'
    rejectForm.patch(route(routeName, c.id), {
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

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Extra Payments</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Review and approve member extra payment contributions
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Select v-model="dateFilter">
                        <SelectTrigger class="w-[160px] h-9">
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
                    <div v-if="dateFilter === 'custom'" class="flex items-center gap-2">
                        <Input v-model="fromDate" type="date" class="w-[130px] h-9 text-xs" />
                        <span class="text-muted-foreground">-</span>
                        <Input v-model="toDate" type="date" class="w-[130px] h-9 text-xs" />
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Pending</CardTitle>
                        <PiggyBank class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats?.total_pending ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Awaiting approval</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Approved</CardTitle>
                        <CheckCircle class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats?.total_approved ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Total approved</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Approved Amount</CardTitle>
                        <PiggyBank class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats ? formatCurrency(stats.total_amount) : '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Sum of approved payments</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Table Card -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input v-model="search" placeholder="Search member name or ID..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl" />
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Select v-model="status">
                                <SelectTrigger class="w-[140px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                            <!-- Type Filter -->
                            <Select v-model="type">
                                <SelectTrigger class="w-[140px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem value="loan">Loan Payments</SelectItem>
                                    <SelectItem value="savings">Savings</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">

                        <!-- Empty -->
                        <div v-if="contributions.data.length === 0" class="text-center py-20">
                            <PiggyBank class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No contributions found</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Loan Type</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Narration</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Receipt</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Date</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <template v-for="c in contributions.data" :key="c.id">
                                        <tr class="hover:bg-muted/20 transition-colors">
                                            <td class="py-4 px-6">
                                                <p class="font-medium text-foreground">{{ c.member_name }}</p>
                                                <p class="text-xs text-muted-foreground font-mono">{{ c.member_id }}</p>
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge variant="outline"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ c.loan_type }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-right font-medium text-primary">
                                                {{ formatCurrency(c.amount) }}
                                            </td>
                                            <td class="py-4 px-6 text-muted-foreground text-xs max-w-[180px] truncate">
                                                {{ c.narration }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <template v-if="c.screenshot_path">
                                                    <a :href="`/storage/${c.screenshot_path}`" target="_blank"
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
                                                <Badge :variant="statusVariant(c.status)"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ c.status }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">
                                                {{ c.created_at }}
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div v-if="c.status === 'pending'"
                                                    class="flex items-center justify-end gap-1">
                                                    <Button size="sm" class="h-8 rounded-lg text-xs"
                                                        @click="approve(c)">
                                                        <CheckCircle class="h-3 w-3 mr-1" />
                                                        Approve
                                                    </Button>
                                                    <Button size="sm" variant="ghost"
                                                        class="h-8 rounded-lg text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        @click="startReject(c)">
                                                        <XCircle class="h-3 w-3 mr-1" />
                                                        Reject
                                                    </Button>
                                                </div>
                                                <span v-else class="text-xs text-muted-foreground px-4">—</span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge :variant="c.type === 'savings' ? 'secondary' : 'outline'"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ c.type === 'savings' ? 'Savings' : 'Loan' }}
                                                </Badge>
                                            </td>
                                        </tr>

                                        <!-- Reject form row -->
                                        <tr v-if="rejectingId === c.id" class="bg-destructive/5">
                                            <td colspan="8" class="px-6 py-3">
                                                <div class="flex items-center gap-3">
                                                    <textarea v-model="rejectForm.admin_note" rows="1"
                                                        placeholder="Reason for rejection (required)..."
                                                        class="flex-1 border border-destructive/50 rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-destructive/30 resize-none" />
                                                    <Button size="sm" variant="destructive"
                                                        class="h-8 rounded-lg text-xs shrink-0"
                                                        :disabled="rejectForm.processing" @click="submitReject(c)">
                                                        {{ rejectForm.processing ? 'Rejecting...' : 'Confirm' }}
                                                    </Button>
                                                    <Button size="sm" variant="outline"
                                                        class="h-8 rounded-lg text-xs shrink-0" @click="cancelReject">
                                                        Cancel
                                                    </Button>
                                                </div>
                                                <p v-if="rejectForm.errors.admin_note"
                                                    class="text-xs text-destructive mt-1">
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
                    <div v-if="contributions.last_page > 1"
                        class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-muted-foreground">Rows per page</span>
                                <Select v-model="perPage" @update:modelValue="updateFilters">
                                    <SelectTrigger
                                        class="w-[70px] h-8 bg-background border-none shadow-sm rounded-lg text-xs">
                                        <SelectValue :placeholder="perPage" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <p class="text-xs font-medium text-muted-foreground">
                                Showing {{ contributions.from }}-{{ contributions.to }} of {{ contributions.total }}
                            </p>
                        </div>
                        <Pagination :links="contributions.links" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
