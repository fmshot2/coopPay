<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ref } from 'vue'
import { PiggyBank, CheckCircle, XCircle, ExternalLink } from 'lucide-vue-next'

const props = defineProps({
    pending: Array,
    recent:  Array,
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

const approve = (contribution) => {
    if (confirm(`Approve ₦${contribution.amount} payment from ${contribution.member_name}?`)) {
        router.patch(route('admin.contributions.approve', contribution.id))
    }
}

const rejectingId  = ref(null)
const rejectForm   = useForm({ admin_note: '' })

const startReject = (contribution) => {
    rejectingId.value     = contribution.id
    rejectForm.admin_note = ''
}

const cancelReject = () => {
    rejectingId.value = null
    rejectForm.reset()
}

const submitReject = (contribution) => {
    rejectForm.patch(route('admin.contributions.reject', contribution.id), {
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
                <h2 class="text-2xl font-bold text-foreground">Extra Payments</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Review and approve member extra payment contributions
                </p>
            </div>

            <!-- Pending -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Pending Contributions</CardTitle>
                    <CardDescription>
                        {{ pending.length }} contribution{{ pending.length !== 1 ? 's' : '' }} awaiting approval
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="pending.length === 0" class="text-center py-10">
                        <PiggyBank class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No pending contributions</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="c in pending"
                            :key="c.id"
                            class="border rounded-md p-4 space-y-3"
                        >
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-foreground">{{ c.member_name }}</p>
                                        <span class="text-xs text-muted-foreground font-mono">{{ c.member_id }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="text-xs">{{ c.loan_type }}</Badge>
                                        <span class="text-lg font-bold text-foreground">
                                            {{ formatCurrency(c.amount) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground italic">{{ c.narration }}</p>
                                    <p class="text-xs text-muted-foreground">Submitted: {{ c.created_at }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <Badge variant="outline" class="text-xs">pending</Badge>
                                    <a

                                        v-if="c.screenshot_path"
                                        :href="`/storage/${c.screenshot_path}`"
                                        target="_blank"
                                        class="text-xs text-primary flex items-center gap-1 hover:underline"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        View Receipt
                                    </a>
                                </div>
                            </div>

                            <!-- Reject form -->
                            <div v-if="rejectingId === c.id" class="space-y-2 border-t pt-3">
                                <textarea
                                    v-model="rejectForm.admin_note"
                                    rows="2"
                                    placeholder="Reason for rejection (required)..."
                                    class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                    :class="rejectForm.errors.admin_note ? 'border-destructive' : ''"
                                />
                                <p v-if="rejectForm.errors.admin_note" class="text-xs text-destructive">
                                    {{ rejectForm.errors.admin_note }}
                                </p>
                                <div class="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        :disabled="rejectForm.processing"
                                        @click="submitReject(c)"
                                    >
                                        {{ rejectForm.processing ? 'Rejecting...' : 'Confirm Reject' }}
                                    </Button>
                                    <Button size="sm" variant="outline" @click="cancelReject">
                                        Cancel
                                    </Button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div v-else class="flex items-center gap-2 border-t pt-3">
                                <Button size="sm" @click="approve(c)">
                                    <CheckCircle class="h-4 w-4 mr-2" />
                                    Approve
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:text-destructive"
                                    @click="startReject(c)"
                                >
                                    <XCircle class="h-4 w-4 mr-2" />
                                    Reject
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recently Processed</CardTitle>
                    <CardDescription>Last 20 approved or rejected contributions</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recent.length === 0" class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No processed contributions yet</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Member</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Loan Type</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Amount</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Narration</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Receipt</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="c in recent"
                                    :key="c.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors"
                                >
                                    <td class="py-3 pr-4">
                                        <p class="font-medium">{{ c.member_name }}</p>
                                        <p class="text-xs text-muted-foreground font-mono">{{ c.member_id }}</p>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline" class="text-xs">{{ c.loan_type }}</Badge>
                                    </td>
                                    <td class="py-3 pr-4 font-medium">{{ formatCurrency(c.amount) }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground text-xs max-w-32 truncate">
                                        {{ c.narration }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="statusVariant(c.status)" class="text-xs">
                                            {{ c.status }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <a
                                            v-if="c.screenshot_path"
                                            :href="`/storage/${c.screenshot_path}`"
                                            target="_blank"
                                            class="text-xs text-primary flex items-center gap-1 hover:underline"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            View
                                        </a><span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                    <td class="py-3 text-muted-foreground text-xs">{{ c.approved_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
