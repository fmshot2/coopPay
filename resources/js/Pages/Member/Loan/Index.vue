<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { CreditCard } from 'lucide-vue-next'

const props = defineProps({
    loans: Array,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const progressPercent = (loan) => {
    const paid = loan.total_months - loan.months_remaining
    return Math.round((paid / loan.total_months) * 100)
}

const statusVariant = (status) => {
    if (status === 'active') return 'default'
    if (status === 'completed') return 'secondary'
    if (status === 'suspended') return 'destructive'
    return 'outline'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">My Loans</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    View all your loan plans and repayment schedules
                </p>
            </div>

            <!-- No loans -->
            <Card v-if="loans.length === 0">
                <CardContent class="py-10 text-center">
                    <CreditCard class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                    <p class="text-sm text-muted-foreground">
                        You have no loan plans. Contact the administrator.
                    </p>
                </CardContent>
            </Card>

            <!-- Loan Cards -->
                                 <div class="grid grid-cols-2 lg:grid-cols-2 gap-3">

            <Card v-for="loan in loans" :key="loan.id">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">{{ loan.loan_type }}</Badge>
                                <Badge :variant="statusVariant(loan.status)">{{ loan.status }}</Badge>
                            </div>
                            <CardTitle class="text-base">
                                {{ formatCurrency(loan.loan_amount) }} Loan
                            </CardTitle>
                            <CardDescription>Started {{ loan.start_date }}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">

                    <!-- Stats Grid -->
                    <!-- <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Loan Amount</p>
                            <p class="text-sm font-semibold">{{ formatCurrency(loan.loan_amount) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Monthly</p>
                            <p class="text-sm font-semibold">{{ formatCurrency(loan.repayment_per_month) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Remaining</p>
                            <p class="text-sm font-semibold">{{ formatCurrency(loan.amount_remaining) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Next Due</p>
                            <p class="text-sm font-semibold">{{ loan.next_due_date ?? '—' }}</p>
                        </div>
                    </div> -->

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 lg:grid-cols-2 gap-3">
                        <div class="bg-muted/40 rounded-md p-3 space-y-1 bg-green-200">
                            <p class="text-xs text-muted-foreground">Loan Amount</p>
                            <p class="text-sm font-bold text-foreground">{{ formatCurrency(loan.loan_amount) }}</p>
                        </div>
                        <div class="bg-muted/40 rounded-md p-3 space-y-1">
                            <p class="text-xs text-muted-foreground">Monthly Deduction</p>
                            <p class="text-sm font-bold text-foreground">{{ formatCurrency(loan.repayment_per_month) }}
                            </p>
                        </div>
                        <div class="bg-muted/40 rounded-md p-3 space-y-1">
                            <p class="text-xs text-muted-foreground">Balance Remaining</p>
                            <p class="text-sm font-bold text-foreground">{{ formatCurrency(loan.amount_remaining) }}</p>
                        </div>
                        <div class="bg-muted/40 rounded-md p-3 space-y-1">
                            <p class="text-xs text-muted-foreground">Next Due Date</p>
                            <p class="text-sm font-bold text-foreground">{{ loan.next_due_date ?? '—' }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="loan.status === 'active'" class="space-y-2">
                        <div class="flex justify-between text-xs text-muted-foreground">
                            <span>Repayment Progress</span>
                            <span>{{ progressPercent(loan) }}% complete</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full transition-all duration-500"
                                :style="{ width: progressPercent(loan) + '%' }" />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ loan.total_months - loan.months_remaining }} of {{ loan.total_months }} months completed
                            · {{ loan.months_remaining }} months remaining
                        </p>
                    </div>

                    <!-- Upcoming Schedule -->
                    <div v-if="loan.upcoming.length > 0">
                        <p class="text-sm font-medium text-foreground mb-3">Upcoming Schedule</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left">
                                        <th class="pb-2 pr-4 font-medium text-muted-foreground">Month</th>
                                        <th class="pb-2 font-medium text-muted-foreground">Expected Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in loan.upcoming" :key="index"
                                        class="border-b last:border-0 hover:bg-muted/40">
                                        <td class="py-2 pr-4 text-foreground">{{ item.month_label }}</td>
                                        <td class="py-2 font-medium">{{ formatCurrency(item.expected_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="loan.notes" class="bg-muted/40 rounded-md p-3">
                        <p class="text-xs text-muted-foreground">Notes</p>
                        <p class="text-sm text-foreground mt-1">{{ loan.notes }}</p>
                    </div>

                </CardContent>
            </Card>
            </div>

        </div>
    </AppLayout>
</template>
