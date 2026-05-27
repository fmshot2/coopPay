<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { CheckCircle, Clock, Calendar, Search, FileText } from 'lucide-vue-next'

const props = defineProps({
    deductions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount)
}

const statusVariant = (s) => {
    if (s === 'approved') return 'default'
    if (s === 'rejected') return 'destructive'
    if (s === 'pending') return 'outline'
    return 'secondary'
}

const formatMonth = (month) => {
    const [year, m] = month.split('-')
    return new Date(year, m - 1).toLocaleString('en-NG', { month: 'long', year: 'numeric' })
}

const updateFilters = () => {
    router.get(route('member.deductions.index'), {
        search: search.value || undefined,
        status: status.value !== 'all' ? status.value : undefined,
        date_filter: dateFilter.value !== 'all' ? dateFilter.value : undefined,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

watchDebounced(
    [search, status, dateFilter, fromDate, toDate],
    updateFilters,
    { debounce: 500, maxWait: 1000 }
)

// Confirm form
const confirmingId = ref(null)
const confirmForm = useForm({
    monthly_deduction_id: '',
    member_note: '',
})

const startConfirm = (item) => {
    confirmingId.value = item.id
    confirmForm.monthly_deduction_id = item.id
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
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">My Deductions</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Track and confirm your monthly salary deductions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Select v-model="dateFilter">
                        <SelectTrigger class="w-[160px] h-9">
                            <Calendar class="h-4 w-4 mr-2 opacity-50" />
                            <SelectValue placeholder="Date Range" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Time</SelectItem>
                            <SelectItem value="today">Today</SelectItem>
                            <SelectItem value="last_week">Last 7 Days</SelectItem>
                            <SelectItem value="last_month">Last 30 Days</SelectItem>
                            <SelectItem value="last_year">Last 1 Year</SelectItem>
                            <SelectItem value="custom">Custom Range</SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-if="dateFilter === 'custom'" class="flex items-center gap-2">
                        <Input v-model="fromDate" type="date" class="w-[130px] h-9 text-xs" />
                        <span class="text-muted-foreground">-</span>
                        <Input v-model="toDate" type="date" class="w-[130px] h-9 text-xs" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <Card class="border-none shadow-none bg-transparent">
                <CardContent class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input v-model="search" placeholder="Search loan type..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl" />
                            </div>
                        </div>
                        <Select v-model="status">
                            <SelectTrigger class="w-[140px] h-10 bg-background border-none shadow-sm rounded-xl">
                                <SelectValue placeholder="All Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="approved">Approved</SelectItem>
                                <SelectItem value="rejected">Rejected</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <div v-if="deductions.length === 0" class="text-center py-20">
                            <FileText class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No deductions found</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Month</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Loan Type</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Expected
                                            Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Confirmed</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Admin Note</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <template v-for="d in deductions" :key="d.id">
                                        <tr class="hover:bg-muted/20 transition-colors">
                                            <td class="py-4 px-6 font-medium text-foreground">
                                                {{ formatMonth(d.month) }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge variant="outline"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ d.loan_type }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-right font-medium text-primary">
                                                {{ formatCurrency(d.expected_amount) }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <Badge :variant="statusVariant(d.status)"
                                                    class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ d.status }}
                                                </Badge>
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">
                                                {{ d.confirmed_at ?? '—' }}
                                            </td>
                                            <td class="py-4 px-6 text-xs text-muted-foreground">
                                                {{ d.admin_note ?? '—' }}
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <Button v-if="d.status === 'pending' && confirmingId !== d.id" size="sm"
                                                    class="h-8 rounded-lg text-xs" @click="startConfirm(d)">
                                                    <CheckCircle class="h-3 w-3 mr-1" />
                                                    Confirm
                                                </Button>
                                                <span v-else-if="confirmingId === d.id"
                                                    class="text-xs text-muted-foreground">
                                                    Confirming...
                                                </span>
                                                <span v-else class="text-xs text-muted-foreground">—</span>
                                            </td>
                                        </tr>

                                        <!-- Confirm form row -->
                                        <tr v-if="confirmingId === d.id" class="bg-primary/5">
                                            <td colspan="7" class="px-6 py-4">
                                                <div class="space-y-3">
                                                    <div>
                                                        <p class="text-sm font-medium text-foreground">
                                                            Confirming Deduction: <strong>{{ formatMonth(d.month)
                                                                }}</strong>
                                                        </p>
                                                        <p class="text-sm text-muted-foreground mt-1">
                                                            By confirming, you are stating that
                                                            <strong>{{ formatCurrency(d.expected_amount) }}</strong>
                                                            has been deducted from your salary.
                                                        </p>
                                                    </div>
                                                    <div class="space-y-2">
                                                        <label class="text-xs font-medium text-muted-foreground">
                                                            Optional Note
                                                        </label>
                                                        <textarea v-model="confirmForm.member_note" rows="2"
                                                            placeholder="e.g. salary paid on May 25..."
                                                            class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <Button size="sm" :disabled="confirmForm.processing"
                                                            @click="submitConfirm">
                                                            {{ confirmForm.processing ? 'Confirming...' : 'Yes, Confirm'
                                                            }}
                                                        </Button>
                                                        <Button size="sm" variant="outline" @click="cancelConfirm">
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
