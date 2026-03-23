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
    Users,
    CreditCard,
    CheckCircle,
    PiggyBank,
    TrendingDown,
    BadgeCheck,
} from 'lucide-vue-next'

const props = defineProps({
    stats: Object,
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

const statCards = computed(() => [
    {
        label: 'Total Members',
        value: props.stats.total_members,
        icon: Users,
        description: 'Registered cooperative members',
    },
    {
        label: 'Active Loans',
        value: props.stats.active_loans,
        icon: CreditCard,
        description: `${props.stats.completed_loans} completed`,
    },
    {
        label: 'Pending Deductions',
        value: props.stats.pending_deductions,
        icon: CheckCircle,
        description: 'Awaiting admin approval',
    },
    {
        label: 'Pending Contributions',
        value: props.stats.pending_contributions,
        icon: PiggyBank,
        description: 'Extra payments to review',
    },
    {
        label: 'Total Loan Portfolio',
        value: formatCurrency(props.stats.total_loan_amount),
        icon: TrendingDown,
        description: 'Total active loan amounts',
    },
    {
        label: 'Total Outstanding',
        value: formatCurrency(props.stats.total_amount_remaining),
        icon: BadgeCheck,
        description: 'Remaining across all loans',
    },
])
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">Dashboard</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Welcome back! Here's an overview of the cooperative.
                </p>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Card v-for="card in statCards" :key="card.label">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            {{ card.label }}
                        </CardTitle>
                        <component
                            :is="card.icon"
                            class="h-4 w-4 text-primary"
                        />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-foreground">{{ card.value }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ card.description }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Bottom Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Pending Deductions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Pending Deductions</CardTitle>
                        <CardDescription>Members awaiting deduction approval</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentDeductions.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                            No pending deductions
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="d in recentDeductions"
                                :key="d.id"
                                class="flex items-center justify-between py-2 border-b last:border-0"
                            >
                                <div>
                                    <p class="text-sm font-medium text-foreground">{{ d.member_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ d.member_id }} • {{ d.month }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-foreground">
                                        {{ formatCurrency(d.expected_amount) }}
                                    </p>
                                    <Badge variant="outline" class="text-xs">{{ d.status }}</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Contributions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Pending Contributions</CardTitle>
                        <CardDescription>Extra payments awaiting approval</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentContributions.length === 0" class="text-sm text-muted-foreground py-4 text-center">
                            No pending contributions
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="c in recentContributions"
                                :key="c.id"
                                class="flex items-center justify-between py-2 border-b last:border-0"
                            >
                                <div>
                                    <p class="text-sm font-medium text-foreground">{{ c.member_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ c.member_id }}</p>
                                    <p class="text-xs text-muted-foreground italic">{{ c.narration }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-foreground">
                                        {{ formatCurrency(c.amount) }}
                                    </p>
                                    <Badge variant="outline" class="text-xs">{{ c.status }}</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
