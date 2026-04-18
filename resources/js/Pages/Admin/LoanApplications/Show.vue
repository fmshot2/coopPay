<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ArrowLeft, CheckCircle, XCircle } from 'lucide-vue-next'
import { ref } from 'vue'

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

const approve = () => {
    if (confirm(`Approve ${formatCurrency(props.application.amount)} loan for ${props.application.member_name}?`)) {
        router.patch(route('admin.loan-applications.approve', props.application.id))
    }
}

const showRejectForm = ref(false)
const rejectForm     = useForm({ rejection_reason: '' })

const submitReject = () => {
    rejectForm.patch(route('admin.loan-applications.reject', props.application.id), {
        onSuccess: () => {
            showRejectForm.value = false
            rejectForm.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-3xl">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="route('admin.loan-applications.index')">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">Loan Application</h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Application #{{ application.id }} — {{ application.created_at }}
                        </p>
                    </div>
                </div>
                <Badge :variant="statusVariant(application.status)" class="text-sm px-3 py-1">
                    {{ application.status }}
                </Badge>
            </div>

            <!-- Member Info -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Member Information</CardTitle>
                </CardHeader>
                <CardContent class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Full Name</p>
                        <p class="text-sm font-medium">{{ application.member_name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Member ID</p>
                        <p class="text-sm font-mono">{{ application.member_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Email</p>
                        <p class="text-sm">{{ application.member_email ?? '—' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Phone</p>
                        <p class="text-sm">{{ application.member_phone ?? '—' }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Loan Details -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Loan Details</CardTitle>
                    <CardDescription>Calculation based on 10% fixed interest rate</CardDescription>
                </CardHeader>
                <CardContent class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Loan Type</p>
                        <Badge variant="outline">{{ application.loan_type }}</Badge>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Loan Amount</p>
                        <p class="text-sm font-bold text-primary">{{ formatCurrency(application.amount) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Duration</p>
                        <p class="text-sm font-medium">{{ application.duration_months }} months</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Interest Rate</p>
                        <p class="text-sm font-medium">{{ application.interest_rate }}%</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Monthly Repayment</p>
                        <p class="text-sm font-bold">{{ formatCurrency(application.monthly_payment) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs text-muted-foreground">Total Repayable</p>
                        <p class="text-sm font-bold">{{ formatCurrency(application.total_payment) }}</p>
                    </div>
                    <div v-if="application.purpose" class="col-span-2 space-y-1">
                        <p class="text-xs text-muted-foreground">Purpose</p>
                        <p class="text-sm text-foreground">{{ application.purpose }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Status Info -->
            <Card v-if="application.status !== 'pending'">
                <CardHeader>
                    <CardTitle class="text-base">
                        {{ application.status === 'approved' ? 'Approval Details' : 'Rejection Details' }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Processed By</p>
                            <p class="text-sm font-medium">{{ application.approved_by ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Processed At</p>
                            <p class="text-sm font-medium">{{ application.approved_at ?? '—' }}</p>
                        </div>
                    </div>
                    <div v-if="application.rejection_reason" class="space-y-1">
                        <p class="text-xs text-muted-foreground">Rejection Reason</p>
                        <div class="bg-destructive/10 border border-destructive/20 rounded-md p-3">
                            <p class="text-sm text-destructive">{{ application.rejection_reason }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <Card v-if="application.status === 'pending'">
                <CardHeader>
                    <CardTitle class="text-base">Actions</CardTitle>
                    <CardDescription>
                        Approving will automatically create a loan plan for this member.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="!showRejectForm" class="flex items-center gap-3">
                        <Button @click="approve">
                            <CheckCircle class="h-4 w-4 mr-2" />
                            Approve Application
                        </Button>
                        <Button
                            variant="outline"
                            class="text-destructive hover:text-destructive"
                            @click="showRejectForm = true"
                        >
                            <XCircle class="h-4 w-4 mr-2" />
                            Reject Application
                        </Button>
                    </div>

                    <!-- Reject form -->
                    <div v-else class="space-y-3">
                        <textarea
                            v-model="rejectForm.rejection_reason"
                            rows="3"
                            placeholder="Reason for rejection (required)..."
                            class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                            :class="rejectForm.errors.rejection_reason ? 'border-destructive' : ''"
                        />
                        <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-destructive">
                            {{ rejectForm.errors.rejection_reason }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="destructive"
                                :disabled="rejectForm.processing"
                                @click="submitReject"
                            >
                                {{ rejectForm.processing ? 'Rejecting...' : 'Confirm Rejection' }}
                            </Button>
                            <Button
                                variant="outline"
                                @click="showRejectForm = false; rejectForm.reset()"
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
