<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
    application: Object,
})

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
    return 'outline'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-2xl">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('member.loan-applications.index')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-foreground">Application Details</h2>
                        <Badge :variant="statusVariant(application.status)" class="text-sm px-3 py-1">
                            {{ application.status }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground mt-1">
                        Submitted on {{ new Date(application.created_at).toLocaleDateString('en-NG') }}
                    </p>
                </div>
            </div>

            <!-- Loan Details -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Loan Details</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Loan Type</p>
                        <Badge variant="outline">{{ application.loan_type?.name ?? '—' }}</Badge>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Amount Requested</p>
                        <p class="text-sm font-bold text-primary">{{ formatCurrency(application.amount) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Duration</p>
                        <p class="text-sm font-medium">{{ application.duration_months }} months</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Monthly Repayment</p>
                        <p class="text-sm font-bold">{{ formatCurrency(application.monthly_payment) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Total Repayable</p>
                        <p class="text-sm font-medium">{{ formatCurrency(application.total_payment) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Interest Rate</p>
                        <p class="text-sm font-medium">{{ application.interest_rate }}%</p>
                    </div>
                    <div v-if="application.purpose" class="col-span-2 space-y-1">
                        <p class="text-xs text-muted-foreground">Purpose</p>
                        <p class="text-sm text-foreground">{{ application.purpose }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Approved -->
            <Card v-if="application.status === 'approved'">
                <CardHeader>
                    <CardTitle class="text-base text-primary">✅ Application Approved</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        Your loan application has been approved. A loan plan has been created for you.
                        You can view your active loans on the
                        <Link :href="route('member.loans.index')" class="text-primary underline">My Loans</Link>
                        page.
                    </p>
                    <p class="text-xs text-muted-foreground mt-2">Approved: {{ application.approved_at }}</p>
                </CardContent>
            </Card>

            <!-- Rejected -->
            <Card v-if="application.status === 'rejected'">
                <CardHeader>
                    <CardTitle class="text-base text-destructive">❌ Application Rejected</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="application.rejection_reason" class="bg-destructive/10 border border-destructive/20 rounded-md p-3">
                        <p class="text-xs text-muted-foreground mb-1">Reason</p>
                        <p class="text-sm text-destructive">{{ application.rejection_reason }}</p>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        You may apply again with a different amount or loan type.
                    </p>
                    <Button as-child size="sm">
                        <Link :href="route('member.loan-applications.create')">
                            Apply Again
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <!-- Pending -->
            <Card v-if="application.status === 'pending'">
                <CardHeader>
                    <CardTitle class="text-base">⏳ Awaiting Review</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        Your application is being reviewed by the administrator.
                        You will be notified once a decision has been made.
                    </p>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
