<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, useForm, Link, Deferred } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Pagination } from '@/components/ui/pagination'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { CheckCircle, XCircle, Search, Calendar, CreditCard, Clock, CheckSquare } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

const props = defineProps({
    deductions: Object,
    filters: Object,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')
const perPage = ref(props.filters?.per_page?.toString() || '5')

const statCards = computed(() => [
    {
        label: 'Pending Approval',
        value: props.stats.total_pending,
        icon: Clock,
        description: 'Awaiting admin review',
    },
    {
        label: 'Total Approved',
        value: props.stats.total_approved,
        icon: CheckSquare,
        description: 'Processed deductions',
    },
    {
        label: 'Total Amount',
        value: props.stats.total_amount,
        icon: CreditCard,
        description: 'Approved deduction volume',
    },
])

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

const updateFilters = () => {
    router.get(route('admin.deductions.index'), {
        search: search.value,
        status: statusFilter.value,
        date_filter: dateFilter.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

watchDebounced(
    [search, statusFilter, dateFilter, fromDate, toDate, perPage],
    () => {
        updateFilters()
    },
    { debounce: 500, maxWait: 1000 }
)

// Approve
const approve = (deduction) => {
    if (confirm(`Approve deduction for ${deduction.member_name} — ${deduction.month}?`)) {
        router.patch(route('admin.deductions.approve', deduction.id), {}, {
            onSuccess: () => toast.success('Deduction approved successfully'),
        })
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
            toast.success('Deduction rejected')
        },
    })
}

const changePerPage = (value) => {
    perPage.value = value
}
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Deductions</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Review and approve member monthly deduction confirmations
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Date Filter in Header -->
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

                    <!-- Custom Date Inputs -->
                    <div v-if="dateFilter === 'custom'" class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-300">
                        <Input v-model="fromDate" type="date" class="w-[130px] h-9 text-xs" />
                        <span class="text-muted-foreground">-</span>
                        <Input v-model="toDate" type="date" class="w-[130px] h-9 text-xs" />
                    </div>
                </div>
            </div>

            <!-- Stat Cards -->
            <Deferred data="stats">
                <template #fallback>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Card v-for="i in 3" :key="i" class="animate-pulse">
                            <CardHeader class="flex flex-row items-center justify-between pb-2">
                                <div class="h-4 w-24 bg-muted rounded"></div>
                                <div class="h-4 w-4 bg-muted rounded"></div>
                            </CardHeader>
                            <CardContent>
                                <div class="h-8 w-16 bg-muted rounded mb-2"></div>
                                <div class="h-3 w-32 bg-muted rounded"></div>
                            </CardContent>
                        </Card>
                    </div>
                </template>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card v-for="card in statCards" :key="card.label">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-md font-medium text-muted-foreground">
                                {{ card.label }}
                            </CardTitle>
                            <component
                                :is="card.icon"
                                class="h-4 w-4 text-primary"
                            />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-foreground">
                                {{ card.label === 'Total Amount' ? formatCurrency(card.value) : card.value }}
                            </p>
                            <p class="text-xs text-muted-foreground mt-1">{{ card.description }}</p>
                        </CardContent>
                    </Card>
                </div>
            </Deferred>

            <!-- Table Card -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search members or ID..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl"
                                />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Status Filter -->
                            <Select v-model="statusFilter">
                                <SelectTrigger class="w-[150px] h-10 bg-background border-none shadow-sm rounded-xl">
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
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="bg-background rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <!-- Empty state -->
                        <div v-if="deductions.data.length === 0" class="text-center py-20">
                            <CheckCircle class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No deductions found</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Month</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Timeline</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="d in deductions.data"
                                        :key="d.id"
                                        class="hover:bg-muted/20 transition-colors"
                                    >
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col">
                                                <span class="font-medium text-foreground">{{ d.member_name }}</span>
                                                <span class="text-xs text-muted-foreground font-mono">{{ d.member_id }}</span>
                                                <Badge variant="outline" class="w-fit mt-1 rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                    {{ d.loan_type }}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-center text-muted-foreground">
                                            {{ d.month }}
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium">
                                            {{ formatCurrency(d.expected_amount) }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col gap-1 text-[10px] text-muted-foreground">
                                                <div class="flex items-center gap-1.5">
                                                    <div class="h-1 w-1 rounded-full bg-blue-400"></div>
                                                    <span>Requested: {{ d.created_at }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <div class="h-1 w-1 rounded-full bg-green-400"></div>
                                                    <span>Confirmed: {{ d.confirmed_at }}</span>
                                                </div>
                                                <div v-if="d.status !== 'pending'" class="flex items-center gap-1.5">
                                                    <div class="h-1 w-1 rounded-full bg-gray-400"></div>
                                                    <span>Processed: {{ d.approved_at }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge :variant="statusVariant(d.status)" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ d.status }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <div v-if="d.status === 'pending'" class="flex items-center justify-end gap-2">
                                                <!-- Reject form -->
                                                <div v-if="rejectingId === d.id" class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2">
                                                    <Input
                                                        v-model="rejectForm.admin_note"
                                                        placeholder="Reason..."
                                                        class="h-8 text-xs w-32"
                                                        :class="rejectForm.errors.admin_note ? 'border-destructive' : ''"
                                                    />
                                                    <Button size="sm" variant="destructive" class="h-8 px-2" :disabled="rejectForm.processing" @click="submitReject(d)">
                                                        Confirm
                                                    </Button>
                                                    <Button size="sm" variant="ghost" class="h-8 px-2" @click="cancelReject">
                                                        <XCircle class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                                <template v-else>
                                                    <Button variant="ghost" size="sm" class="h-8 rounded-lg hover:bg-muted text-xs text-primary" @click="approve(d)">
                                                        <CheckCircle class="h-4 w-4 mr-1" /> Approve
                                                    </Button>
                                                    <Button variant="ghost" size="sm" class="h-8 rounded-lg hover:bg-destructive/10 text-xs text-destructive" @click="startReject(d)">
                                                        <XCircle class="h-4 w-4 mr-1" /> Reject
                                                    </Button>
                                                </template>
                                            </div>
                                            <div v-else class="text-xs text-muted-foreground px-4">
                                                Processed: {{ d.approved_at }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="deductions.last_page > 1" class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-muted-foreground">Rows per page</span>
                                <Select v-model="perPage" @update:modelValue="changePerPage">
                                    <SelectTrigger class="w-[70px] h-8 bg-background border-none shadow-sm rounded-lg text-xs">
                                        <SelectValue :placeholder="perPage" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="5">5</SelectItem>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="15">15</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <p class="text-xs font-medium text-muted-foreground">
                                Showing {{ deductions.from }}-{{ deductions.to }} of {{ deductions.total }}
                            </p>
                        </div>
                        <Pagination :links="deductions.links" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
