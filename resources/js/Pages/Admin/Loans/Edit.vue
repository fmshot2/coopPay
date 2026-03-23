<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { ArrowLeft } from 'lucide-vue-next'

const props = defineProps({
    loan: Object,
})

const form = useForm({
    loan_amount:          props.loan.loan_amount,
    repayment_per_month:  props.loan.repayment_per_month,
    total_months:         props.loan.total_months,
    months_remaining:     props.loan.months_remaining,
    interest_rate:        props.loan.interest_rate,
    start_date:           props.loan.start_date,
    next_due_date:        props.loan.next_due_date,
    status:               props.loan.status,
    notes:                props.loan.notes ?? '',
})

const submit = () => {
    form.patch(route('admin.loans.update', props.loan.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-xl">

            <!-- Page Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('admin.loans.index')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Edit Loan Plan</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ loan.member_name }} ({{ loan.member_id }})
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Update Loan Details</CardTitle>
                    <CardDescription>
                        Updating months remaining will automatically recalculate the amount remaining.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Loan Amount -->
                        <div class="space-y-2">
                            <Label for="loan_amount">Loan Amount (₦) <span class="text-destructive">*</span></Label>
                            <Input
                                id="loan_amount"
                                v-model="form.loan_amount"
                                type="number"
                                :class="form.errors.loan_amount ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.loan_amount" class="text-xs text-destructive">
                                {{ form.errors.loan_amount }}
                            </p>
                        </div>

                        <!-- Repayment Per Month -->
                        <div class="space-y-2">
                            <Label for="repayment_per_month">Monthly Repayment (₦) <span class="text-destructive">*</span></Label>
                            <Input
                                id="repayment_per_month"
                                v-model="form.repayment_per_month"
                                type="number"
                                :class="form.errors.repayment_per_month ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.repayment_per_month" class="text-xs text-destructive">
                                {{ form.errors.repayment_per_month }}
                            </p>
                        </div>

                        <!-- Total Months -->
                        <div class="space-y-2">
                            <Label for="total_months">Total Months <span class="text-destructive">*</span></Label>
                            <Input
                                id="total_months"
                                v-model="form.total_months"
                                type="number"
                                :class="form.errors.total_months ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.total_months" class="text-xs text-destructive">
                                {{ form.errors.total_months }}
                            </p>
                        </div>

                        <!-- Months Remaining -->
                        <div class="space-y-2">
                            <Label for="months_remaining">Months Remaining <span class="text-destructive">*</span></Label>
                            <Input
                                id="months_remaining"
                                v-model="form.months_remaining"
                                type="number"
                                :class="form.errors.months_remaining ? 'border-destructive' : ''"
                            />
                            <p v-if="form.errors.months_remaining" class="text-xs text-destructive">
                                {{ form.errors.months_remaining }}
                            </p>
                        </div>

                        <!-- Interest Rate -->
                        <div class="space-y-2">
                            <Label for="interest_rate">Interest Rate (%)</Label>
                            <Input
                                id="interest_rate"
                                v-model="form.interest_rate"
                                type="number"
                            />
                        </div>

                        <!-- Start Date -->
                        <div class="space-y-2">
                            <Label for="start_date">Start Date <span class="text-destructive">*</span></Label>
                            <Input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                :class="form.errors.start_date ? 'border-destructive' : ''"
                            />
                        </div>

                        <!-- Next Due Date -->
                        <div class="space-y-2">
                            <Label for="next_due_date">Next Due Date</Label>
                            <Input
                                id="next_due_date"
                                v-model="form.next_due_date"
                                type="date"
                            />
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <Label for="status">Status <span class="text-destructive">*</span></Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                            >
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Notes</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Reason for update, restructure notes, etc..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                            />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Update Loan Plan' }}
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('admin.loans.index')">Cancel</Link>
                            </Button>
                        </div>

                    </form>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
