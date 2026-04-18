<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter,
} from '@/components/ui/dialog'
import { ArrowLeft, UserCheck, UserX, DollarSign } from 'lucide-vue-next'

const props = defineProps({
    member: Object,
    upcoming: Array,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const toggleActive = () => {
    router.patch(route('admin.members.toggle-active', props.member.id))
}

const maxLoanForm = useForm({
    max_loan_amount: props.member.max_loan_amount || '',
})

const updateMaxLoan = () => {
    maxLoanForm.patch(route('admin.members.update-max-loan', props.member.id), {
        onSuccess: () => {
            document.getElementById('maxLoanDialog-close')?.click()
        }
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-3xl m-auto">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="route('admin.members.index')">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">{{ member.name }}</h2>
                        <p class="text-sm text-muted-foreground mt-1">{{ member.member_id }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="outline" size="sm">
                                <DollarSign class="h-4 w-4 mr-2" />
                                Max Loan
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Set Maximum Loan Amount</DialogTitle>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label for="max_loan_amount">Maximum Loan Amount (₦)</Label>
                                    <Input 
                                        id="max_loan_amount" 
                                        v-model="maxLoanForm.max_loan_amount" 
                                        type="number"
                                        placeholder="e.g. 500000"
                                        :class="maxLoanForm.errors.max_loan_amount ? 'border-destructive' : ''"
                                    />
                                    <p v-if="maxLoanForm.errors.max_loan_amount" class="text-xs text-destructive">
                                        {{ maxLoanForm.errors.max_loan_amount }}
                                    </p>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Current: {{ formatCurrency(member.max_loan_amount) || 'Not set' }}
                                </p>
                            </div>
                            <DialogFooter>
                                <Button @click="updateMaxLoan" :disabled="maxLoanForm.processing">
                                    {{ maxLoanForm.processing ? 'Saving...' : 'Save' }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                    <Button :variant="member.is_active ? 'destructive' : 'default'" size="sm" @click="toggleActive">
                        <UserX v-if="member.is_active" class="h-4 w-4 mr-2" />
                        <UserCheck v-else class="h-4 w-4 mr-2" />
                        {{ member.is_active ? 'Deactivate' : 'Activate' }}
                    </Button>
                </div>
            </div>

            <!-- Member Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Member Information</CardTitle>
                    <CardDescription>Personal and account details</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Full Name</p>
                            <p class="text-sm font-medium">{{ member.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Member ID</p>
                            <p class="text-sm font-mono">{{ member.member_id ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Email</p>
                            <p class="text-sm font-medium">{{ member.email }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Phone</p>
                            <p class="text-sm font-medium">{{ member.phone ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Division</p>
                            <p class="text-sm font-medium">{{ member.division_name ?? '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Account Status</p>
                            <Badge :variant="member.is_active ? 'default' : 'destructive'">
                                {{ member.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Password Status</p>
                            <Badge :variant="member.must_change_password ? 'outline' : 'default'">
                                {{ member.must_change_password ? 'Must change password' : 'Password set' }}
                            </Badge>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs text-muted-foreground">Member Since</p>
                            <p class="text-sm font-medium">{{ member.created_at }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Loan Plan Card -->
            <!-- Loan Plans Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Loan Plans</CardTitle>
                    <CardDescription>All loan plans for this member</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!member.loan_plans || member.loan_plans.length === 0" class="text-center py-6">
                        <p class="text-sm text-muted-foreground">No loan plans assigned yet.</p>
                        <Button class="mt-4" size="sm" as-child>
                            <Link :href="route('admin.loans.create')">
                                Assign Loan Plan
                            </Link>
                        </Button>
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="loan in member.loan_plans" :key="loan.id" class="border rounded-md p-4">
                            <div class="flex items-center justify-between mb-3">
                                <Badge variant="outline">{{ loan.loan_type }}</Badge>
                                <Badge
                                    :variant="loan.status === 'active' ? 'default' : loan.status === 'completed' ? 'secondary' : 'destructive'">
                                    {{ loan.status }}
                                </Badge>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Loan Amount</p>
                                    <p class="text-sm font-medium">{{ formatCurrency(loan.loan_amount) }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Monthly Deduction</p>
                                    <p class="text-sm font-medium">{{ formatCurrency(loan.repayment_per_month) }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Total Months</p>
                                    <p class="text-sm font-medium">{{ loan.total_months }} months</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Months Remaining</p>
                                    <p class="text-sm font-medium">{{ loan.months_remaining }} months</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Amount Remaining</p>
                                    <p class="text-sm font-medium">{{ formatCurrency(loan.amount_remaining) }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Next Due Date</p>
                                    <p class="text-sm font-medium">{{ loan.next_due_date ?? '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Start Date</p>
                                    <p class="text-sm font-medium">{{ loan.start_date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Upcoming Deductions Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Upcoming Deductions</CardTitle>
                    <CardDescription>Expected future deductions for this member</CardDescription>
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

        </div>
    </AppLayout>
</template>
