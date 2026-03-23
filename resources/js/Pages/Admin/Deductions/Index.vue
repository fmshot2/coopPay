<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ref } from 'vue'
import { CheckCircle, XCircle } from 'lucide-vue-next'

const props = defineProps({
    pending: Array,
    recent: Array,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const statusVariant = (status) => {
    if (status === 'approved') return 'default'
    if (status === 'rejected') return 'destructive'
    return 'outline'
}

// Approve
const approve = (deduction) => {
    if (confirm(`Approve deduction for ${deduction.member_name} — ${deduction.month}?`)) {
        router.patch(route('admin.deductions.approve', deduction.id))
    }
}

// Reject
const rejectingId = ref(null)
const rejectForm = useForm({ admin_note: '' })

const startReject = (deduction) => {
    rejectingId.value = deduction.id
    rejectForm.admin_note = ''
}

const cancelReject = () => {
    rejectingId.value = null
    rejectForm.reset()
}

const submitReject = (deduction) => {
    rejectForm.patch(route('admin.deductions.reject', deduction.id), {
        onSuccess: () => {
            rejectingId.value = null
            rejectForm.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">Deductions</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Review and approve member monthly deduction confirmations
                </p>
            </div>

            <!-- Pending Deductions -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Pending Confirmations</CardTitle>
                    <CardDescription>
                        {{ pending.length }} deduction{{ pending.length !== 1 ? 's' : '' }} awaiting approval
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="pending.length === 0" class="text-center py-10">
                        <CheckCircle class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No pending deductions</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="d in pending" :key="d.id" class="border rounded-md p-4 space-y-3">
                            <!-- Deduction Info -->
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-foreground">{{ d.member_name }}</p>
                                        <span class="text-xs text-muted-foreground font-mono">{{ d.member_id }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="text-xs">{{ d.loan_type }}</Badge>
                                        <span class="text-xs text-muted-foreground">{{ d.month }}</span>
                                        <span class="text-xs font-medium text-foreground">
                                            {{ formatCurrency(d.expected_amount) }}
                                        </span>
                                    </div>
                                    <p v-if="d.member_note" class="text-xs text-muted-foreground italic">
                                        Member note: {{ d.member_note }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Confirmed: {{ d.confirmed_at }}
                                    </p>
                                </div>
                                <Badge variant="outline" class="text-xs">pending</Badge>
                            </div>

                            <!-- Reject form -->
                            <div v-if="rejectingId === d.id" class="space-y-2">
                                <textarea v-model="rejectForm.admin_note" rows="2"
                                    placeholder="Reason for rejection (required)..."
                                    class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                    :class="rejectForm.errors.admin_note ? 'border-destructive' : ''" />
                                <p v-if="rejectForm.errors.admin_note" class="text-xs text-destructive">
                                    {{ rejectForm.errors.admin_note }}
                                </p>
                                <div class="flex gap-2">
                                    <Button size="sm" variant="destructive" :disabled="rejectForm.processing"
                                        @click="submitReject(d)">
                                        {{ rejectForm.processing ? 'Rejecting...' : 'Confirm Reject' }}
                                    </Button>
                                    <Button size="sm" variant="outline" @click="cancelReject">
                                        Cancel
                                    </Button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div v-else class="flex items-center gap-2">
                                <Button size="sm" @click="approve(d)">
                                    <CheckCircle class="h-4 w-4 mr-2" />
                                    Approve
                                </Button>
                                <Button size="sm" variant="outline" class="text-destructive hover:text-destructive"
                                    @click="startReject(d)">
                                    <XCircle class="h-4 w-4 mr-2" />
                                    Reject
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Processed -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recently Processed</CardTitle>
                    <CardDescription>Last 20 approved or rejected deductions</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recent.length === 0" class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No processed deductions yet</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Member</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Loan Type</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Month</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Amount</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in recent" :key="d.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors">
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-foreground">{{ d.member_name }}</p>
                                        <p class="text-xs text-muted-foreground font-mono">{{ d.member_id }}</p>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline" class="text-xs">{{ d.loan_type }}</Badge>
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ d.month }}</td>
                                    <td class="py-3 pr-4">{{ formatCurrency(d.expected_amount) }}</td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="statusVariant(d.status)" class="text-xs">
                                            {{ d.status }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 text-muted-foreground text-xs">{{ d.approved_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
