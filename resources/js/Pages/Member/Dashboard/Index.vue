<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
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
} from 'lucide-vue-next'

const props = defineProps({
    loan: Object,
    recentDeductions: Array,
    recentContributions: Array,
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
            <div>
                <h2 class="text-2xl font-bold text-foreground">My Dashboard</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Track your loan repayment and contributions.
                </p>
            </div>

            <!-- No Loan State -->
            <Card v-if="!loan">
                <CardContent class="py-10 text-center">
                    <p class="text-muted-foreground text-sm">
                        You have no active loan plan. Please contact the administrator.
                    </p>
                </CardContent>
            </Card>

            <template v-else>

                <!-- Loan Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Loan Amount
                            </CardTitle>
                            <CreditCard class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold">{{ formatCurrency(loan.loan_amount) }}</p>
                            <p class="text-xs text-muted-foreground mt-1">Total loan collected</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Amount Remaining
                            </CardTitle>
                            <PiggyBank class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold">{{ formatCurrency(loan.amount_remaining) }}</p>
                            <p class="text-xs text-muted-foreground mt-1">Outstanding balance</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Monthly Deduction
                            </CardTitle>
                            <CheckCircle class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold">{{ formatCurrency(loan.repayment_per_month) }}</p>
                            <p class="text-xs text-muted-foreground mt-1">Deducted via IPPIS</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Next Due Date
                            </CardTitle>
                            <CalendarClock class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-sm mt-1">{{ loan.next_due_date }}</p>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ loan.months_remaining }} months remaining
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Repayment Progress -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Repayment Progress</CardTitle>
                        <CardDescription>
                            {{ loan.total_months - loan.months_remaining }} of {{ loan.total_months }} months completed
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="w-full bg-muted rounded-full h-3">
                            <div
                                class="bg-primary h-3 rounded-full transition-all duration-500"
                                :style="{ width: progressPercent + '%' }"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground mt-2">{{ progressPercent }}% completed</p>
                    </CardContent>
                </Card>

                <!-- Bottom Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Recent Deductions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Recent Deductions</CardTitle>
                            <CardDescription>Your last 5 monthly deductions</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="recentDeductions.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                                No deductions yet
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="d in recentDeductions"
                                    :key="d.id"
                                    class="flex items-center justify-between py-2 border-b last:border-0"
                                >
                                    <div>
                                        <p class="text-sm font-medium text-foreground">{{ d.month }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ d.confirmed_at ? 'Confirmed' : 'Not confirmed' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold">
                                            {{ formatCurrency(d.expected_amount) }}
                                        </p>
                                        <Badge :variant="statusVariant(d.status)" class="text-xs">
                                            {{ d.status }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Contributions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Extra Payments</CardTitle>
                            <CardDescription>Your recent extra contributions</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="recentContributions.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                                No extra payments yet
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="c in recentContributions"
                                    :key="c.id"
                                    class="flex items-center justify-between py-2 border-b last:border-0"
                                >
                                    <div>
                                        <p class="text-sm font-medium text-foreground">
                                            {{ formatCurrency(c.amount) }}
                                        </p>
                                        <p class="text-xs text-muted-foreground italic">{{ c.narration }}</p>
                                    </div>
                                    <Badge :variant="statusVariant(c.status)" class="text-xs">
                                        {{ c.status }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </template>

        </div>
    </AppLayout>
</template>
