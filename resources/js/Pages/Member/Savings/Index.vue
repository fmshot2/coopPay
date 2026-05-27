<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Pagination } from '@/components/ui/pagination'

const props = defineProps({
    contributions: { type: Object, required: true },
    totalSavings: { type: Number, required: true },
    monthlyTarget: { type: Number, required: true },
    minimumSavings: { type: Number, required: true },
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}

const formatDate = (value) => {
    if (!value) return '—'

    return new Date(value).toLocaleDateString('en-NG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

const statusVariant = (status) => {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-800'
    if (status === 'pending') return 'bg-amber-100 text-amber-800'
    if (status === 'rejected') return 'bg-red-100 text-red-800'
    return 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">My Savings</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Track your savings contributions, approved balance, and monthly goal.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="route('member.savings.create')">
                        Make Contribution
                    </Link>
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="flex items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Savings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold text-foreground">{{ formatCurrency(totalSavings) }}</p>
                        <p class="text-sm text-muted-foreground mt-2">Approved savings contributions</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Monthly Target</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold text-foreground">{{ formatCurrency(monthlyTarget) }}</p>
                        <p class="text-sm text-muted-foreground mt-2">Your current savings goal</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Minimum Deposit</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold text-foreground">{{ formatCurrency(minimumSavings) }}</p>
                        <p class="text-sm text-muted-foreground mt-2">Minimum amount per contribution</p>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <CardTitle class="text-lg font-semibold text-foreground">Contribution History</CardTitle>
                            <p class="text-sm text-muted-foreground mt-1">Your latest savings contributions appear below.</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <div v-if="!contributions.data || contributions.data.length === 0" class="text-center py-16 text-muted-foreground">
                            <p class="text-base">No savings contributions found.</p>
                            <p class="mt-2 text-sm">Start by making a new contribution today.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Date</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Narration</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="contribution in contributions.data"
                                        :key="contribution.id"
                                        class="hover:bg-muted/10 transition-colors"
                                    >
                                        <td class="py-4 px-6 text-sm text-foreground">{{ formatDate(contribution.created_at) }}</td>
                                        <td class="py-4 px-6 text-sm text-right font-medium text-foreground">{{ formatCurrency(contribution.amount) }}</td>
                                        <td class="py-4 px-6 text-sm">
                                            <span :class="statusVariant(contribution.status)" class="px-2 py-1 rounded-full text-xs font-semibold uppercase">
                                                {{ contribution.status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-muted-foreground">{{ contribution.narration || '—' }}</td>
                                        <td class="py-4 px-6 text-right text-sm font-medium">
                                            <Link :href="route('member.savings.show', contribution.id)" class="text-primary hover:text-primary/80">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="contributions.last_page > 1" class="mt-4">
                        <Pagination :links="contributions.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
