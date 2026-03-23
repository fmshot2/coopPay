<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { CreditCard, Plus, Search } from 'lucide-vue-next'

const props = defineProps({
    loans: Array,
})

const search = ref('')

const filtered = computed(() => {
    if (!search.value) return props.loans
    const q = search.value.toLowerCase()
    return props.loans.filter(l =>
        l.member_name.toLowerCase().includes(q) ||
        l.member_id?.toLowerCase().includes(q) ||
        l.loan_type?.toLowerCase().includes(q)
    )
})

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
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Loan Plans</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage member loan repayment plans
                    </p>
                </div>
                <Button as-child>
                    <Link :href="route('admin.loans.create')">
                        <Plus class="h-4 w-4 mr-2" />
                        Assign Loan
                    </Link>
                </Button>
            </div>

            <!-- Table Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">All Loan Plans</CardTitle>
                            <CardDescription>{{ loans.length }} total loan plans</CardDescription>
                        </div>
                        <div class="relative w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input v-model="search" placeholder="Search member or type..." class="pl-9" />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="filtered.length === 0" class="text-center py-10">
                        <CreditCard class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No loan plans found</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Member</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Type</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Loan Amount</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Monthly</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Remaining</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Months Left</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Next Due</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="loan in filtered" :key="loan.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors">
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-foreground">{{ loan.member_name }}</p>
                                        <p class="text-xs text-muted-foreground font-mono">{{ loan.member_id }}</p>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline" class="text-xs">{{ loan.loan_type }}</Badge>
                                    </td>
                                    <td class="py-3 pr-4">{{ formatCurrency(loan.loan_amount) }}</td>
                                    <td class="py-3 pr-4">{{ formatCurrency(loan.repayment_per_month) }}</td>
                                    <td class="py-3 pr-4">{{ formatCurrency(loan.amount_remaining) }}</td>
                                    <td class="py-3 pr-4">{{ loan.months_remaining }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ loan.next_due_date ?? '—' }}</td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="statusVariant(loan.status)" class="text-xs">
                                            {{ loan.status }}
                                        </Badge>
                                    </td>
                                    <td class="py-3">
                                        <div v-if="loan.status === 'active'" class="flex items-center gap-1">
                                            <Button variant="ghost" size="sm" class="text-xs"
                                                @click="markComplete(loan)">
                                                Complete
                                            </Button>
                                            <Button variant="ghost" size="sm"
                                                class="text-xs text-destructive hover:text-destructive"
                                                @click="cancelLoan(loan)">
                                                Cancel
                                            </Button>
                                        </div>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
