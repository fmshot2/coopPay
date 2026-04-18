<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, Deferred } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Pagination } from '@/components/ui/pagination'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { CreditCard, Plus, Search, Calendar, Users, TrendingUp, CheckCircle } from 'lucide-vue-next'

const props = defineProps({
    loans: Object,
    loanTypes: Array,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || 'all')
const loanTypeFilter = ref(props.filters?.loan_type || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')
const perPage = ref(props.filters?.per_page?.toString() || '5')

const statCards = computed(() => [
    {
        label: 'Total Loans',
        value: props.stats.total_loans,
        icon: CreditCard,
        description: 'Total loan plans created',
    },
    {
        label: 'Active Loans',
        value: props.stats.active_loans,
        icon: TrendingUp,
        description: 'Currently repaying loans',
    },
    {
        label: 'Completed',
        value: props.stats.completed_loans,
        icon: CheckCircle,
        description: 'Fully repaid loans',
    },
])

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const statusVariant = (status) => {
    if (status === 'active') return 'default'
    if (status === 'completed') return 'secondary'
    if (status === 'suspended') return 'destructive'
    return 'outline'
}

const updateFilters = () => {
    router.get(route('admin.loans.index'), {
        search: search.value,
        status: statusFilter.value,
        loan_type: loanTypeFilter.value,
        date_filter: dateFilter.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

// Watch with 500ms debounce for smoother searching
watchDebounced(
    [search, statusFilter, loanTypeFilter, dateFilter, fromDate, toDate, perPage],
    () => {
        updateFilters()
    },
    { debounce: 500, maxWait: 1000 }
)

const markComplete = (loan) => {
    if (confirm(`Mark ${loan.member_name}'s ${loan.loan_type} loan as completed?`)) {
        router.patch(route('admin.loans.complete', loan.id))
    }
}

const cancelLoan = (loan) => {
    if (confirm(`Cancel ${loan.member_name}'s ${loan.loan_type} loan?`)) {
        router.patch(route('admin.loans.cancel', loan.id))
    }
}

const changePerPage = (value) => {
    perPage.value = value
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Loan Plans</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage member loan repayment plans
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Date Filter in Header -->
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

                    <!-- Custom Date Inputs -->
                    <div v-if="dateFilter === 'custom'" class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-300">
                        <Input v-model="fromDate" type="date" class="w-[130px] h-9 text-xs" />
                        <span class="text-muted-foreground">-</span>
                        <Input v-model="toDate" type="date" class="w-[130px] h-9 text-xs" />
                    </div>

                    <div class="h-8 w-px bg-border mx-1 hidden md:block"></div>

                    <Button size="sm" as-child class="rounded-xl">
                        <Link :href="route('admin.loans.create')">
                            <Plus class="h-4 w-4 mr-2" />
                            Assign Loan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stat Cards -->
            <Deferred data="stats">
                <template #fallback>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card v-for="i in 3" :key="i" class="animate-pulse">
                            <CardHeader class="flex flex-row items-center justify-between pb-2">
                                <div class="h-4 w-24 bg-muted rounded"></div>
                                <div class="h-4 w-4 bg-muted rounded"></div>
                            </CardHeader>
                            <CardContent>
                                <div class="h-8 w-16 bg-muted rounded mb-2"></div>
                                <div class="h-3 w-32 bg-muted rounded"></div>
                            </CardContent>
                        </Card>
                    </div>
                </template>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card v-for="card in statCards" :key="card.label">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-md font-medium text-muted-foreground">
                                {{ card.label }}
                            </CardTitle>
                            <component
                                :is="card.icon"
                                class="h-4 w-4 text-primary"
                            />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-foreground">
                                {{ typeof card.value === 'number' && card.label === 'Total Disbursed' ? formatCurrency(card.value) : card.value }}
                            </p>
                            <p class="text-xs text-muted-foreground mt-1">{{ card.description }}</p>
                        </CardContent>
                    </Card>
                </div>
            </Deferred>

            <!-- Table Card -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search members or ID..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl"
                                />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Status Filter -->
                            <Select v-model="statusFilter">
                                <SelectTrigger class="w-[130px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                    <SelectItem value="suspended">Suspended</SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Loan Type Filter -->
                            <Select v-model="loanTypeFilter">
                                <SelectTrigger class="w-[150px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem v-for="type in loanTypes" :key="type.id" :value="type.id.toString()">
                                        {{ type.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <!-- Empty state -->
                        <div v-if="loans.data.length === 0" class="text-center py-20">
                            <CreditCard class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No loan plans found</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Type</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Monthly</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Remaining</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Months</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Next Due</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="loan in loans.data"
                                        :key="loan.id"
                                        class="hover:bg-muted/20 transition-colors"
                                    >
                                        <td class="py-4 px-6">
                                            <p class="font-medium text-foreground">{{ loan.member_name }}</p>
                                            <p class="text-xs text-muted-foreground font-mono">{{ loan.member_id }}</p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge variant="outline" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ loan.loan_type }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium">
                                            {{ formatCurrency(loan.loan_amount) }}
                                        </td>
                                        <td class="py-4 px-6 text-right text-muted-foreground">
                                            {{ formatCurrency(loan.repayment_per_month) }}
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium text-primary">
                                            {{ formatCurrency(loan.amount_remaining) }}
                                        </td>
                                        <td class="py-4 px-6 text-center text-muted-foreground">
                                            {{ loan.months_remaining }}
                                        </td>
                                        <td class="py-4 px-6 text-muted-foreground">
                                            {{ loan.next_due_date ?? '—' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge :variant="statusVariant(loan.status)" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ loan.status }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <div v-if="loan.status === 'active'" class="flex items-center justify-end gap-1">
                                                <Button variant="ghost" size="sm" class="h-8 rounded-lg hover:bg-muted text-xs"
                                                    @click="markComplete(loan)">
                                                    Complete
                                                </Button>
                                                <Button variant="ghost" size="sm"
                                                    class="h-8 rounded-lg text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    @click="cancelLoan(loan)">
                                                    Cancel
                                                </Button>
                                            </div>
                                            <span v-else class="text-xs text-muted-foreground px-4">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="loans.last_page > 1" class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-muted-foreground">Rows per page</span>
                                <Select v-model="perPage" @update:modelValue="changePerPage">
                                    <SelectTrigger class="w-[70px] h-8 bg-background border-none shadow-sm rounded-lg text-xs">
                                        <SelectValue :placeholder="perPage" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="5">5</SelectItem>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="15">15</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <p class="text-xs font-medium text-muted-foreground">
                                Showing {{ loans.from }}-{{ loans.to }} of {{ loans.total }}
                            </p>
                        </div>
                        <Pagination :links="loans.links" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
