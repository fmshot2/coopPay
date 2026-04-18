<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Pagination } from '@/components/ui/pagination'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { CheckCircle, XCircle, Eye, Search, Calendar, FileText } from 'lucide-vue-next'

const props = defineProps({
    applications: Object,
    filters:      Object,
    stats:        Object,
})

const search     = ref(props.filters?.search || '')
const status     = ref(props.filters?.status || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate   = ref(props.filters?.from_date || '')
const toDate     = ref(props.filters?.to_date || '')
const perPage    = ref(props.filters?.per_page?.toString() || '10')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}

const statusVariant = (s) => {
    if (s === 'approved') return 'default'
    if (s === 'rejected') return 'destructive'
    if (s === 'pending')  return 'outline'
    return 'secondary'
}

const updateFilters = () => {
    router.get(route('admin.loan-applications.index'), {
        search:      search.value || undefined,
        status:      status.value !== 'all' ? status.value : undefined,
        date_filter: dateFilter.value !== 'all' ? dateFilter.value : undefined,
        from_date:   fromDate.value || undefined,
        to_date:     toDate.value || undefined,
        per_page:    perPage.value,
    }, {
        preserveState:  true,
        preserveScroll: true,
        replace:        true,
    })
}

watchDebounced(
    [search, status, dateFilter, fromDate, toDate, perPage],
    updateFilters,
    { debounce: 500, maxWait: 1000 }
)

// Approve
const approve = (a) => {
    if (confirm(`Approve ${formatCurrency(a.amount)} loan for ${a.member_name}?`)) {
        router.patch(route('admin.loan-applications.approve', a.id))
    }
}

// Reject
const rejectingId = ref(null)
const rejectForm  = useForm({ rejection_reason: '' })

const startReject = (a) => {
    rejectingId.value            = a.id
    rejectForm.rejection_reason  = ''
}

const cancelReject = () => {
    rejectingId.value = null
    rejectForm.reset()
}

const submitReject = (a) => {
    rejectForm.patch(route('admin.loan-applications.reject', a.id), {
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
                    <h2 class="text-2xl font-bold text-foreground">Loan Applications</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Review and process member loan applications
                    </p>
                </div>
                <div class="flex items-center gap-3">
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
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Pending</CardTitle>
                        <FileText class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats?.total_pending ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Awaiting review</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Approved</CardTitle>
                        <CheckCircle class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats?.total_approved ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Loans created</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Rejected</CardTitle>
                        <XCircle class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats?.total_rejected ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Declined applications</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Approved</CardTitle>
                        <CheckCircle class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-sm">{{ stats ? formatCurrency(stats.total_amount) : '—' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Sum of approved loans</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Table -->
            <Card class="border-none shadow-none bg-transparent">
                <CardContent class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
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
                    </div>

                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <div v-if="applications.data.length === 0" class="text-center py-20">
                            <FileText class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No loan applications found</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Loan Type</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Duration</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Monthly</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Date</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <template v-for="a in applications.data" :key="a.id">
                                        <tr class="hover:bg-muted/20 transition-colors">
                                            <td class="py-4 px-6">
                                                <p class="font-medium text-foreground">{{ a.member_name }}</p>
                                                <p class="text-xs text-muted-foreground font-mono">{{ a.member_id }}</p>
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge variant="outline" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ a.loan_type }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-right font-medium text-primary">
                                                {{ formatCurrency(a.amount) }}
                                            </td>
                                            <td class="py-4 px-6 text-center text-muted-foreground">
                                                {{ a.duration_months }} months
                                            </td>
                                            <td class="py-4 px-6 text-right text-muted-foreground">
                                                {{ formatCurrency(a.monthly_payment) }}
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">
                                                {{ a.created_at }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge :variant="statusVariant(a.status)" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ a.status }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg" as-child>
                                                        <Link :href="route('admin.loan-applications.show', a.id)">
                                                            <Eye class="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <template v-if="a.status === 'pending'">
                                                        <Button
                                                            size="sm"
                                                            class="h-8 rounded-lg text-xs"
                                                            @click="approve(a)"
                                                        >
                                                            <CheckCircle class="h-3 w-3 mr-1" />
                                                            Approve
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            class="h-8 rounded-lg text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                                                            @click="startReject(a)"
                                                        >
                                                            <XCircle class="h-3 w-3 mr-1" />
                                                            Reject
                                                        </Button>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Reject form row -->
                                        <tr v-if="rejectingId === a.id" class="bg-destructive/5">
                                            <td colspan="8" class="px-6 py-3">
                                                <div class="flex items-center gap-3">
                                                    <textarea
                                                        v-model="rejectForm.rejection_reason"
                                                        rows="1"
                                                        placeholder="Reason for rejection (required)..."
                                                        class="flex-1 border border-destructive/50 rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-destructive/30 resize-none"
                                                    />
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        class="h-8 rounded-lg text-xs shrink-0"
                                                        :disabled="rejectForm.processing"
                                                        @click="submitReject(a)"
                                                    >
                                                        {{ rejectForm.processing ? 'Rejecting...' : 'Confirm' }}
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        class="h-8 rounded-lg text-xs shrink-0"
                                                        @click="cancelReject"
                                                    >
                                                        Cancel
                                                    </Button>
                                                </div>
                                                <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-destructive mt-1">
                                                    {{ rejectForm.errors.rejection_reason }}
                                                </p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="applications.last_page > 1" class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
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
                                Showing {{ applications.from }}-{{ applications.to }} of {{ applications.total }}
                            </p>
                        </div>
                        <Pagination :links="applications.links" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
