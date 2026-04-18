<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ArrowLeft, Info } from 'lucide-vue-next'

// import { Toaster } from '@/components/ui/sonner'
// import { toast } from 'vue-sonner'

const props = defineProps({
    loanTypes: Array,
    maxLoanAmount: { type: Number, default: 0 },
})

const form = useForm({
    loan_type_id: '',
    amount: '',
    duration_months: 22,
    purpose: '',
})

const divisionName = 'Main Division'

const interestRate = 0.10
const totalRepayable = computed(() => {
    if (!form.amount) return 0
    return parseFloat(form.amount) * (1 + interestRate)
})
const monthlyPayment = computed(() => {
    if (!form.amount || !form.duration_months) return 0
    return totalRepayable.value / parseInt(form.duration_months)
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}

const submit = () => {
    // if (form.amount > maxLoanAmount) {
    //     toast.error(flash.error)
    //     return
    // }
    form.post(route('member.loan-applications.store'))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-xl">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('member.loan-applications.index')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Apply for Loan</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Submit a new loan application for review
                    </p>
                </div>
            </div>

            <!-- Max Eligibility Banner -->
            <div v-if="maxLoanAmount > 0"
                class="flex items-center gap-3 bg-primary/10 border border-primary/20 rounded-xl px-4 py-3">
                <Info class="h-5 w-5 text-primary shrink-0" />
                <div>
                    <p class="text-sm font-semibold text-primary">Your Maximum Loan Eligibility</p>
                    <p class="text-xl font-bold text-primary">{{ formatCurrency(maxLoanAmount) }}</p>
                </div>
            </div>
            <div v-else class="flex items-center gap-3 bg-muted border border-border rounded-xl px-4 py-3">
                <Info class="h-5 w-5 text-muted-foreground shrink-0" />
                <p class="text-sm text-muted-foreground">
                    Your maximum loan eligibility has not been set. Contact the administrator.
                </p>
            </div>

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Loan Application Details</CardTitle>
                    <CardDescription>
                        Interest rate is fixed at 10%. Monthly repayment is calculated automatically.
                        Your application will be reviewed by the administrator.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-4">

                        <!-- Division (Hardcoded) -->
                        <div class="space-y-2">
                            <Label for="division">Division</Label>
                            <Input id="division" :value="divisionName" disabled class="bg-muted" />
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

                        <!-- Amount -->
                        <div class="space-y-2">
                            <Label for="amount">
                                Loan Amount (₦)
                                <span class="text-destructive">*</span>
                                <span v-if="maxLoanAmount > 0" class="text-xs text-muted-foreground font-normal ml-1">
                                    (max {{ formatCurrency(maxLoanAmount) }})
                                </span>
                            </Label>
                            <Input id="amount" v-model="form.amount" type="number" placeholder="e.g. 200000"
                                :max="maxLoanAmount || undefined"
                                :class="form.errors.amount ? 'border-destructive' : ''" />
                            <p v-if="form.errors.amount" class="text-xs text-destructive">
                                {{ form.errors.amount }}
                            </p>
                        </div>

                        <!-- Duration -->
                        <div class="space-y-2">
                            <Label for="duration_months">Duration (Months) <span
                                    class="text-destructive">*</span></Label>
                            <Input id="duration_months" v-model="form.duration_months" type="number"
                                placeholder="e.g. 12" min="1" max="60"
                                :class="form.errors.duration_months ? 'border-destructive' : ''" />
                            <p v-if="form.errors.duration_months" class="text-xs text-destructive">
                                {{ form.errors.duration_months }}
                            </p>
                        </div>

                        <!-- Purpose -->
                        <div class="space-y-2">
                            <Label for="purpose">Purpose / Reason</Label>
                            <textarea id="purpose" v-model="form.purpose" rows="3"
                                placeholder="Brief description of what the loan is for..."
                                class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                        </div>

                        <!-- Calculation Preview -->
                        <div v-if="form.amount && form.duration_months"
                            class="rounded-xl bg-muted p-4 space-y-2 text-sm">
                            <p class="font-medium text-foreground">Repayment Preview</p>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Loan Amount</span>
                                <span>{{ formatCurrency(form.amount) }}</span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Interest (10%)</span>
                                <span>{{ formatCurrency(form.amount * 0.10) }}</span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Total Repayable</span>
                                <span>{{ formatCurrency(totalRepayable) }}</span>
                            </div>
                            <div class="flex justify-between font-semibold text-foreground border-t pt-2">
                                <span>Monthly Repayment</span>
                                <span class="text-primary">{{ formatCurrency(monthlyPayment) }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('member.loan-applications.index')">Cancel</Link>
                            </Button>
                        </div>

                    </form>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
