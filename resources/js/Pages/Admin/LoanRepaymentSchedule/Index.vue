<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, Deferred } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Pagination } from '@/components/ui/pagination'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Search, Calendar, CreditCard, Clock, CheckSquare, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
    repayments: Object,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')
const perPage = ref(props.filters?.per_page?.toString() || '15')

const updateFilters = () => {
    router.get(route('admin.repayments.schedule.index'), {
        search: search.value,
        status: status.value,
        date_filter: dateFilter.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        per_page: perPage.value,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

watchDebounced(
    [search, status, dateFilter, fromDate, toDate, perPage],
    updateFilters,
    { debounce: 500, maxWait: 1000 }
)

const formatCurrency = (amount) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency', currency: 'NGN', minimumFractionDigits: 2,
    }).format(amount ?? 0)

const statusVariant = (s) => ({
    fully_paid: 'default',
    partly_paid: 'secondary',
    unpaid: 'destructive',
})[s] ?? 'outline'

const statusLabel = (s) => ({
    fully_paid: 'Fully Paid',
    partly_paid: 'Partly Paid',
    unpaid: 'Unpaid',
})[s] ?? s
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Loan Repayment Schedule</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Monthly repayment records for all active loan plans
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
                        <span class="text-muted-foreground text-xs">to</span>
                        <Input v-model="toDate" type="date" class="w-[130px] h-9 text-xs" />
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <Deferred data="stats">
                <template #fallback>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <Card v-for="i in 4" :key="i" class="animate-pulse">
                            <CardHeader class="pb-2">
                                <div class="h-4 w-24 bg-muted rounded" />
                            </CardHeader>
                            <CardContent>
                                <div class="h-8 w-20 bg-muted rounded" />
                            </CardContent>
                        </Card>
                    </div>
                </template>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Due (Unpaid)</CardTitle>
                            <Clock class="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ formatCurrency(stats?.total_due) }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Total Collected</CardTitle>
                            <CheckSquare class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ formatCurrency(stats?.total_paid) }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Unpaid Rows</CardTitle>
                            <AlertCircle class="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ stats?.total_unpaid ?? 0 }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Partly Paid</CardTitle>
                            <CreditCard class="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-xl font-bold">{{ stats?.total_partly ?? 0 }}</p>
                        </CardContent>
                    </Card>
                </div>
            </Deferred>

            <!-- Table -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input v-model="search" placeholder="Search member name or ID..."
                                class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl" />
                        </div>
                        <div class="flex items-center gap-3">
                            <Select v-model="status">
                                <SelectTrigger class="w-[160px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="unpaid">Unpaid</SelectItem>
                                    <SelectItem value="partly_paid">Partly Paid</SelectItem>
                                    <SelectItem value="fully_paid">Fully Paid</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="perPage">
                                <SelectTrigger
                                    class="w-[80px] h-10 bg-background border-none shadow-sm rounded-xl text-xs">
                                    <SelectValue :placeholder="perPage" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10</SelectItem>
                                    <SelectItem value="15">15</SelectItem>
                                    <SelectItem value="25">25</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">

                        <!-- Empty state -->
                        <div v-if="repayments.data.length === 0" class="text-center py-20">
                            <CheckSquare class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No repayment records found</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Period</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount Due
                                        </th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount Paid
                                        </th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr v-for="r in repayments.data" :key="r.id"
                                        class="hover:bg-muted/20 transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-foreground">{{ r.member_name }}</span>
                                                <span class="text-xs text-muted-foreground font-mono">{{ r.member_id
                                                }}</span>
                                                <Badge variant="outline"
                                                    class="w-fit mt-1 rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ r.loan_type }}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-muted-foreground text-sm">
                                            {{ r.month }}
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium">
                                            {{ formatCurrency(r.amount_due) }}
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <span v-if="r.amount_paid" class="font-medium text-primary">
                                                {{ formatCurrency(r.amount_paid) }}
                                            </span>
                                            <span v-else class="text-muted-foreground text-xs">—</span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge :variant="statusVariant(r.status)"
                                                class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ statusLabel(r.status) }}
                                            </Badge>
                                            <p v-if="r.approved_at" class="text-[10px] text-muted-foreground mt-1">
                                                {{ r.approved_at }}
                                            </p>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-muted-foreground max-w-[180px]">
                                            <span v-if="r.admin_note">{{ r.admin_note }}</span>
                                            <span v-else-if="r.narration">{{ r.narration }}</span>
                                            <span v-else>—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="repayments.last_page > 1" class="mt-6 flex items-center justify-between gap-4 px-2">
                        <p class="text-xs text-muted-foreground">
                            Showing {{ repayments.from }}–{{ repayments.to }} of {{ repayments.total }}
                        </p>
                        <Pagination :links="repayments.links" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
