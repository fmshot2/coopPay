<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    CreditCard,
    CheckCircle,
    PiggyBank,
    CalendarClock,
    Plus
} from 'lucide-vue-next'
import Button from '@/components/ui/button/Button.vue'

const props = defineProps({
    loan: Object,
    activeLoansCount: Number,
    activeLoans: {
        type: Array,
        default: () => [],
    },
    loanRequests: {
        type: Array,
        default: () => [],
    },
    recentDeductions: Array,
    recentContributions: Array,
    cooperativeAccount: String,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const progressPercent = computed(() => {
    if (!props.loan) return 0
    const paid = props.loan.total_months - props.loan.months_remaining
    return Math.round((paid / props.loan.total_months) * 100)
})

const statusVariant = (status) => {
    if (status === 'approved') return 'default'
    if (status === 'rejected') return 'destructive'
    return 'outline'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">My Dashboard</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Track your loan repayment and contributions.
                    </p>
                </div>

                <!-- Cooperative Account -->
                <div v-if="cooperativeAccount" class="bg-primary/10 border border-primary/20 rounded-lg px-4 py-2">
                    <p class="text-xs text-muted-foreground">Cooperative Account</p>
                    <p class="text-sm font-semibold text-primary">{{ cooperativeAccount }}</p>
                </div>

                <!-- <Button as-child>
                    <Link :href="route('member.loans.index')">
                    <CreditCard class="h-4 w-4 mr-2" />
                    See All My Loans
                    </Link>
                </Button> -->

                <Button as-child>
                    <Link :href="route('member.loan-applications.create')">
                        <Plus class="h-4 w-4 mr-2" />
                        Request for A New Loan
                    </Link>
                </Button>

            </div>


            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xl font-medium text-muted-foreground">
                            No of Active Loans
                        </CardTitle>
                        <CreditCard class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ activeLoansCount }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Current loans</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xl font-medium text-muted-foreground">
                            Total Amount Collected
                        </CardTitle>
                        <CreditCard class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatCurrency(loan?.loan_amount ?? 0) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Total loans collected</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xl font-medium text-muted-foreground">
                            Total Outstanding Balance
                        </CardTitle>
                        <PiggyBank class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatCurrency(loan?.amount_remaining ?? 0) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Outstanding balance</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-xl font-medium text-muted-foreground">
                            Total Monthly Deductions
                        </CardTitle>
                        <CheckCircle class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold">{{ formatCurrency(loan?.repayment_per_month ?? 0) }}</p>
                        <p class="text-xs text-muted-foreground mt-1">Deducted via IPPIS</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Repayment Progress</CardTitle>
                        <CardDescription>
                            <span v-if="loan">{{ loan.total_months - loan.months_remaining }} of {{ loan.total_months }} months completed</span>
                            <span v-else>Start a loan request to track your repayment progress.</span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="w-full bg-muted rounded-full h-3">
                            <div class="bg-primary h-3 rounded-full transition-all duration-500"
                                :style="{ width: loan ? progressPercent + '%' : '0%' }" />
                        </div>
                        <p class="text-xs text-muted-foreground mt-2">
                            <span v-if="loan">{{ progressPercent }}% completed</span>
                            <span v-else>No active loan</span>
                        </p>
                    </CardContent>
                </Card> -->

                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl">My Active Loans</CardTitle>
                        <!-- <CardDescription>Approved loan plans currently underway.</CardDescription> -->
                    </CardHeader>
                    <CardContent>
                        <div v-if="activeLoans.length === 0" class="py-8 text-center text-lg text-muted-foreground">
                            No active loans yet.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                        <th class="pb-3 pr-4">Loan Type</th>
                                        <th class="pb-3 pr-4">Amount</th>
                                        <th class="pb-3 pr-4">Monthly</th>
                                        <th class="pb-3 pr-4">Remaining</th>
                                        <th class="pb-3 pr-4">Start Date</th>
                                        <th class="pb-3 pr-4">Next Due</th>
                                        <th class="pb-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="loanItem in activeLoans" :key="loanItem.id"
                                        class="border-b last:border-0 hover:bg-muted/30">
                                        <td class="py-3 pr-4">{{ loanItem.loan_type }}</td>
                                        <td class="py-3 pr-4">{{ formatCurrency(loanItem.loan_amount) }}</td>
                                        <td class="py-3 pr-4">{{ formatCurrency(loanItem.monthly_payment) }}</td>
                                        <td class="py-3 pr-4">{{ formatCurrency(loanItem.amount_remaining) }}</td>
                                        <td class="py-3 pr-4">{{ loanItem.start_date }}</td>
                                        <td class="py-3 pr-4">{{ loanItem.next_due_date ?? '—' }}</td>
                                        <td class="py-3">
                                            <Badge :variant="statusVariant(loanItem.status)">{{ loanItem.status }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl">My Loan Requests</CardTitle>
                        <CardDescription>Pending or processed loan applications.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loanRequests.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            You have not submitted any loan requests yet.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                        <th class="pb-3 pr-4">Requested On</th>
                                        <th class="pb-3 pr-4">Loan Type</th>
                                        <th class="pb-3 pr-4">Amount</th>
                                        <th class="pb-3 pr-4">Duration</th>
                                        <th class="pb-3 pr-4">Monthly</th>
                                        <th class="pb-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="request in loanRequests" :key="request.id"
                                        class="border-b last:border-0 hover:bg-muted/30">
                                        <td class="py-3 pr-4">{{ request.created_at }}</td>
                                        <td class="py-3 pr-4">{{ request.loan_type }}</td>
                                        <td class="py-3 pr-4">{{ formatCurrency(request.amount) }}</td>
                                        <td class="py-3 pr-4">{{ request.duration_months }} months</td>
                                        <td class="py-3 pr-4">{{ formatCurrency(request.monthly_payment) }}</td>
                                        <td class="py-3">
                                            <Badge :variant="statusVariant(request.status)">{{ request.status }}</Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
