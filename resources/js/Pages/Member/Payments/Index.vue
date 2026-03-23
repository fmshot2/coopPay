<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ref } from 'vue'
import { PiggyBank, Plus, ExternalLink } from 'lucide-vue-next'

const props = defineProps({
    loans:   { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
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

// Upload form
const showForm  = ref(false)
const form      = useForm({
    loan_plan_id: '',
    amount:       '',
    narration:    '',
    screenshot:   null,
})

const handleFile = (e) => {
    form.screenshot = e.target.files[0]
}

const submit = () => {
    form.post(route('member.payments.store'), {
        onSuccess: () => {
            showForm.value = false
            form.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Extra Payments</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Submit proof of extra payments made to the cooperative account
                    </p>
                </div>
                <Button @click="showForm = !showForm">
                    <Plus class="h-4 w-4 mr-2" />
                    Submit Payment
                </Button>
            </div>

            <!-- Upload Form -->
            <Card v-if="showForm">
                <CardHeader>
                    <CardTitle class="text-base">Submit Extra Payment</CardTitle>
                    <CardDescription>
                        Upload proof of payment made directly to the cooperative account.
                        Admin will verify and update your loan plan accordingly.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Loan -->
                        <div class="space-y-2">
                            <Label for="loan_plan_id">Loan <span class="text-destructive">*</span></Label>
                            <select
                                id="loan_plan_id"
                                v-model="form.loan_plan_id"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                :class="form.errors.loan_plan_id ? 'border-destructive' : ''"
                            >
                                <option value="">Select loan...</option>
                                <option
                                    v-for="loan in loans"
                                    :key="loan.id"
                                    :value="loan.id"
                                >
                                    {{ loan.loan_type }} — {{ formatCurrency(loan.amount_remaining) }} remaining
                                </option>
                            </select>
                            <p v-if="form.errors.loan_plan_id" class="text-xs text-destructive">
                                {{ form.errors.loan_plan_id }}
                            </p>
                        </div>

                        <!-- Amount -->
                        <div class="space-y-2">
                            <Label for="amount">Amount Paid (₦) <span class="text-destructive">*</span></Label>
                            <Input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                placeholder="e.g. 50000"
                                :class="form.errors.amount ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.amount" class="text-xs text-destructive">
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <!-- Narration -->
                        <div class="space-y-2">
                            <Label for="narration">Narration <span class="text-destructive">*</span></Label>
                            <textarea
                                id="narration"
                                v-model="form.narration"
                                rows="2"
                                placeholder="e.g. Bank transfer to cooperative account on March 20, 2026"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                :class="form.errors.narration ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.narration" class="text-xs text-destructive">
                                {{ form.errors.narration }}
                            </p>
                        </div>

                        <!-- Screenshot -->
                        <div class="space-y-2">
                            <Label for="screenshot">
                                Payment Receipt
                                <span class="text-destructive">*</span>
                                <span class="text-muted-foreground text-xs font-normal ml-1">
                                    (JPG, PNG or PDF, max 2MB)
                                </span>
                            </Label>
                            <Input
                                id="screenshot"
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="handleFile"
                                :class="form.errors.screenshot ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.screenshot" class="text-xs text-destructive">
                                {{ form.errors.screenshot }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Submitting...' : 'Submit Payment' }}
                            </Button>
                            <Button variant="outline" type="button" @click="showForm = false">
                                Cancel
                            </Button>
                        </div>

                    </form>
                </CardContent>
            </Card>

            <!-- No active loans -->
            <Card v-if="loans.length === 0">
                <CardContent class="py-10 text-center">
                    <PiggyBank class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                    <p class="text-sm text-muted-foreground">
                        You have no active loans to make extra payments for.
                    </p>
                </CardContent>
            </Card>

            <!-- Payment History -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Payment History</CardTitle>
                    <CardDescription>All your extra payment submissions</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="history.length === 0" class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No extra payments submitted yet</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="c in history"
                            :key="c.id"
                            class="flex items-start justify-between py-3 border-b last:border-0"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="text-xs">{{ c.loan_type }}</Badge>
                                    <span class="text-sm font-bold text-foreground">
                                        {{ formatCurrency(c.amount) }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground italic">{{ c.narration }}</p>
                                <p class="text-xs text-muted-foreground">Submitted: {{ c.created_at }}</p>
                                <p v-if="c.admin_note" class="text-xs text-destructive">
                                    Admin note: {{ c.admin_note }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <Badge :variant="statusVariant(c.status)" class="text-xs">
                                    {{ c.status }}
                                </Badge>
                                <template v-if="c.screenshot_path">
                                    <a
                                        :href="`/storage/${c.screenshot_path}`"
                                        target="_blank"
                                        class="text-xs text-primary flex items-center gap-1 hover:underline"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        View Receipt
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
