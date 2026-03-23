<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CheckCircle, Clock } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps({
    confirmable: Array,
    upcoming: Array,
    history: Array,
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

const formatMonth = (month) => {
    const [year, m] = month.split('-')
    return new Date(year, m - 1).toLocaleString('en-NG', { month: 'long', year: 'numeric' })
}

// Confirm form
const confirmingId = ref(null)
const confirmForm = useForm({
    loan_plan_id: '',
    member_note: '',
})

const startConfirm = (item) => {
    confirmingId.value = item.loan_id
    confirmForm.loan_plan_id = item.loan_id
    confirmForm.member_note = ''
}

const cancelConfirm = () => {
    confirmingId.value = null
    confirmForm.reset()
}

const submitConfirm = () => {
    confirmForm.post(route('member.deductions.confirm'), {
        onSuccess: () => {
            confirmingId.value = null
            confirmForm.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">My Deductions</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Confirm your monthly salary deductions and view your history
                </p>
            </div>

            <!-- Confirmable This Month -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">This Month's Deductions</CardTitle>
                    <CardDescription>
                        Confirm deductions that have been made from your salary this month
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="confirmable.length === 0" class="text-center py-10">
                        <CheckCircle class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No active loans to confirm</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="item in confirmable" :key="item.loan_id" class="border rounded-md p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <Badge variant="outline" class="text-xs">
                                            {{ item.loan_type }}
                                        </Badge>
                                        <span class="text-sm text-muted-foreground">
                                            {{ formatMonth(item.month) }}
                                        </span>
                                    </div>
                                    <p class="text-lg font-bold text-foreground">
                                        {{ formatCurrency(item.expected_amount) }}
                                    </p>
                                </div>

                                <!-- Already confirmed -->
                                <div v-if="item.already_confirmed" class="text-right">
                                    <Badge :variant="statusVariant(item.confirmation_status)" class="text-xs">
                                        {{ item.confirmation_status }}
                                    </Badge>
                                    <p class="text-xs text-muted-foreground mt-1">Already confirmed</p>
                                </div>

                                <!-- Not yet confirmed -->
                                <Button v-else-if="confirmingId !== item.loan_id" @click="startConfirm(item)">
                                    <CheckCircle class="h-4 w-4 mr-2" />
                                    Confirm Deduction
                                </Button>
                            </div>

                            <!-- Confirm form -->
                            <div v-if="confirmingId === item.loan_id" class="space-y-3 border-t pt-3">
                                <p class="text-sm text-muted-foreground">
                                    By confirming, you are stating that
                                    <strong>{{ formatCurrency(item.expected_amount) }}</strong>
                                    has been deducted from your salary for
                                    <strong>{{ formatMonth(item.month) }}</strong>.
                                </p>
                                <div class="space-y-2">
                                    <textarea v-model="confirmForm.member_note" rows="2"
                                        placeholder="Optional note (e.g. salary paid on March 25)..."
                                        class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button size="sm" :disabled="confirmForm.processing" @click="submitConfirm">
                                        {{ confirmForm.processing ? 'Confirming...' : 'Yes, Confirm' }}
                                    </Button>
                                    <Button size="sm" variant="outline" @click="cancelConfirm">
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Upcoming Deductions -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Upcoming Deductions</CardTitle>
                    <CardDescription>
                        Your expected deductions for the remaining loan period
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="upcoming.length === 0" class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No upcoming deductions</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Month</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Loan Type</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Expected Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in upcoming" :key="index"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors">
                                    <td class="py-3 pr-4 font-medium text-foreground">
                                        {{ item.month_label }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline" class="text-xs">{{ item.loan_type }}</Badge>
                                    </td>
                                    <td class="py-3 font-medium text-foreground">
                                        {{ formatCurrency(item.expected_amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Deduction History -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Deduction History</CardTitle>
                    <CardDescription>All your past deduction confirmations</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="history.length === 0" class="text-center py-6">
                        <Clock class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No deduction history yet</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Loan Type</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Month</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Amount</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Confirmed</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Admin Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in history" :key="d.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors">
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline" class="text-xs">{{ d.loan_type }}</Badge>
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ formatMonth(d.month) }}
                                    </td>
                                    <td class="py-3 pr-4 font-medium">
                                        {{ formatCurrency(d.expected_amount) }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="statusVariant(d.status)" class="text-xs">
                                            {{ d.status }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground text-xs">
                                        {{ d.confirmed_at ?? '—' }}
                                    </td>
                                    <td class="py-3 text-muted-foreground text-xs">
                                        {{ d.admin_note ?? '—' }}
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
