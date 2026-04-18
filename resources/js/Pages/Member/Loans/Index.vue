<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { CreditCard, Plus, Search, PiggyBank, CheckCircle, Wallet } from 'lucide-vue-next'

const props = defineProps({
    loans:              { type: Array,  default: () => [] },
    loanTypes:          { type: Array,  default: () => [] },
    stats:              { type: Object, default: () => ({}) },
    filters:            { type: Object, default: () => ({}) },
    cooperativeAccount: { type: String, default: null },
})

const search   = ref(props.filters?.search || '')
const status   = ref(props.filters?.status || 'all')
const loanType = ref(props.filters?.loan_type || 'all')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}

const statusVariant = (s) => {
    if (s === 'active') return 'default'
    if (s === 'completed') return 'secondary'
    if (s === 'suspended') return 'destructive'
    return 'outline'
}

const updateFilters = () => {
    router.get(route('member.loans.index'), {
        search:    search.value || undefined,
        status:    status.value !== 'all' ? status.value : undefined,
        loan_type: loanType.value !== 'all' ? loanType.value : undefined,
    }, {
        preserveState:  true,
        preserveScroll: true,
        replace:        true,
    })
}

watchDebounced(
    [search, status, loanType],
    updateFilters,
    { debounce: 400, maxWait: 800 }
)
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">My Loans</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        View all your loan plans and repayment schedules
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Cooperative Account -->
                    <div v-if="cooperativeAccount" class="bg-primary/10 border border-primary/20 rounded-lg px-4 py-2">
                        <p class="text-xs text-muted-foreground">Cooperative Account</p>
                        <p class="text-sm font-semibold text-primary">{{ cooperativeAccount }}</p>
                    </div>
                    <Button as-child>
                        <Link :href="route('member.loan-applications.create')">
                            <Plus class="h-4 w-4 mr-2" />
                            Request Loan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Active Loans</CardTitle>
                        <CreditCard class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ stats.active_count ?? 0 }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Current active loans</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Collected</CardTitle>
                        <Wallet class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-sm">{{ formatCurrency(stats.total_collected) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">All loans collected</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Outstanding Balance</CardTitle>
                        <PiggyBank class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-sm">{{ formatCurrency(stats.total_outstanding) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Total to repay</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Monthly Deductions</CardTitle>
                        <CheckCircle class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-sm">{{ formatCurrency(stats.total_monthly) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Total via IPPIS monthly</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Table Card -->
            <Card class="border-none shadow-none bg-transparent">
                <CardContent class="px-0">

                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                        <div class="flex-1 max-w-sm">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search loan type..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl"
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <Select v-model="status">
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
                            <Select v-model="loanType">
                                <SelectTrigger class="w-[140px] h-10 bg-background border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem
                                        v-for="t in loanTypes"
                                        :key="t.id"
                                        :value="t.id.toString()"
                                    >
                                        {{ t.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <div v-if="loans.length === 0" class="text-center py-20">
                            <CreditCard class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No loans found</p>
                            <Button as-child class="mt-4">
                                <Link :href="route('member.loan-applications.create')">
                                    Request your first loan
                                </Link>
                            </Button>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Loan Type</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Loan Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Monthly</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Total Months</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Months Paid</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Months Left</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Balance</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Next Due</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="loan in loans"
                                        :key="loan.id"
                                        class="hover:bg-muted/20 transition-colors"
                                    >
                                        <td class="py-4 px-6 font-medium text-foreground">
                                            {{ loan.loan_type }}
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            {{ formatCurrency(loan.loan_amount) }}
                                        </td>
                                        <td class="py-4 px-6 text-right text-muted-foreground">
                                            {{ formatCurrency(loan.repayment_per_month) }}
                                        </td>
                                        <td class="py-4 px-6 text-center text-muted-foreground">
                                            {{ loan.total_months }}
                                        </td>
                                        <td class="py-4 px-6 text-center text-muted-foreground">
                                            {{ loan.total_months - loan.months_remaining }}
                                        </td>
                                        <td class="py-4 px-6 text-center text-muted-foreground">
                                            {{ loan.months_remaining }}
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium text-primary">
                                            {{ formatCurrency(loan.amount_remaining) }}
                                        </td>
                                        <td class="py-4 px-6 text-muted-foreground text-xs">
                                            {{ loan.next_due_date ?? '—' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge
                                                :variant="statusVariant(loan.status)"
                                                class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold"
                                            >
                                                {{ loan.status }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
