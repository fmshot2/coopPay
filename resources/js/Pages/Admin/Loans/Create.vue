<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { ArrowLeft } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps({
    members: Array,
    loanTypes: Array,
})

const form = useForm({
    user_id: '',
    loan_type_id: '',
    loan_amount: '',
    total_months: '',
    start_date: '',
    notes: '',
})

const interestRate = 0.10
const totalRepayable = computed(() => {
    if (!form.loan_amount) return 0
    return parseFloat(form.loan_amount) * (1 + interestRate)
})
const monthlyPayment = computed(() => {
    if (!form.loan_amount || !form.total_months) return 0
    return totalRepayable.value / parseInt(form.total_months)
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const submit = () => {
    form.post(route('admin.loans.store'))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-xl">

            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('admin.loans.index')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Assign Loan Plan</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Create a new loan repayment plan for a member
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Loan Details</CardTitle>
                    <CardDescription>
                        Interest rate is fixed at 10%. Monthly repayment is calculated automatically.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Member -->
                        <div class="space-y-2">
                            <Label for="user_id">Member <span class="text-destructive">*</span></Label>
                            <select id="user_id" v-model="form.user_id"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                :class="form.errors.user_id ? 'border-destructive' : ''">
                                <option value="">Select a member...</option>
                                <option v-for="member in members" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.member_id }})
                                </option>
                            </select>
                            <p v-if="form.errors.user_id" class="text-xs text-destructive">
                                {{ form.errors.user_id }}
                            </p>
                        </div>

                        <!-- Loan Type -->
                        <div class="space-y-2">
                            <Label for="loan_type_id">Loan Type <span class="text-destructive">*</span></Label>
                            <select id="loan_type_id" v-model="form.loan_type_id"
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                                :class="form.errors.loan_type_id ? 'border-destructive' : ''">
                                <option value="">Select loan type...</option>
                                <option v-for="type in loanTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.loan_type_id" class="text-xs text-destructive">
                                {{ form.errors.loan_type_id }}
                            </p>
                        </div>

                        <!-- Loan Amount -->
                        <div class="space-y-2">
                            <Label for="loan_amount">Loan Amount (₦) <span class="text-destructive">*</span></Label>
                            <Input id="loan_amount" v-model="form.loan_amount" type="number" placeholder="e.g. 500000"
                                :class="form.errors.loan_amount ? 'border-destructive' : ''" />
                            <p v-if="form.errors.loan_amount" class="text-xs text-destructive">
                                {{ form.errors.loan_amount }}
                            </p>
                        </div>

                        <!-- Total Months -->
                        <div class="space-y-2">
                            <Label for="total_months">Total Months <span class="text-destructive">*</span></Label>
                            <Input id="total_months" v-model="form.total_months" type="number" placeholder="e.g. 24"
                                :class="form.errors.total_months ? 'border-destructive' : ''" />
                            <p v-if="form.errors.total_months" class="text-xs text-destructive">
                                {{ form.errors.total_months }}
                            </p>
                        </div>

                        <!-- Start Date -->
                        <div class="space-y-2">
                            <Label for="start_date">Start Date <span class="text-destructive">*</span></Label>
                            <Input id="start_date" v-model="form.start_date" type="date"
                                :class="form.errors.start_date ? 'border-destructive' : ''" />
                            <p v-if="form.errors.start_date" class="text-xs text-destructive">
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Notes</Label>
                            <textarea id="notes" v-model="form.notes" rows="3"
                                placeholder="Any notes about this loan plan..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                        </div>

                        <!-- Live Calculation Preview -->
                        <div v-if="form.loan_amount && form.total_months"
                            class="rounded-md bg-muted p-4 space-y-2 text-sm">
                            <p class="font-medium text-foreground">Calculation Preview</p>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Loan Amount</span>
                                <span>{{ formatCurrency(form.loan_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Interest (10%)</span>
                                <span>{{ formatCurrency(form.loan_amount * 0.10) }}</span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Total Repayable</span>
                                <span>{{ formatCurrency(totalRepayable) }}</span>
                            </div>
                            <div class="flex justify-between font-semibold text-foreground border-t pt-2 mt-2">
                                <span>Monthly Repayment</span>
                                <span>{{ formatCurrency(monthlyPayment) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Assign Loan Plan' }}
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
