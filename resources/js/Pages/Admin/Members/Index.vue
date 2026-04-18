<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, router, Deferred } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Pagination } from '@/components/ui/pagination'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Users, Plus, Search, Upload, Download, CreditCard, Calendar } from 'lucide-vue-next'

const props = defineProps({
    members: Object,
    filters: Object,
    stats: Object,
})

const statCards = computed(() => [
    {
        label: 'Total Members',
        value: props.stats.total_members,
        icon: Users,
        route: route('admin.members.index'),
        description: 'Registered cooperative members',
    },
    {
        label: 'Active Loans',
        value: props.stats.active_loans,
        icon: CreditCard,
        route: route('admin.loans.index'),
        description: `${props.stats.completed_loans} completed`,
    },
    {
        label: 'pending Loans',
        value: props.stats.unapproved_loans,
        icon: CreditCard,
        route: route('admin.loans.applications'),
        description: `${props.stats.unapproved_loans} unapproved`,
    },
])

const search = ref(props.filters?.search || '')
const showImport = ref(false)
const statusFilter = ref(props.filters?.status || 'all')
const passwordFilter = ref(props.filters?.password || 'all')
const loanFilter = ref(props.filters?.loan || 'all')
const dateFilter = ref(props.filters?.date_filter || 'all')
const fromDate = ref(props.filters?.from_date || '')
const toDate = ref(props.filters?.to_date || '')
const perPage = ref(props.filters?.per_page?.toString() || '5')

const updateFilters = () => {
    router.get(route('admin.members.index'), {
        search: search.value,
        status: statusFilter.value,
        password: passwordFilter.value,
        loan: loanFilter.value,
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

// Watch with 500ms debounce for smoother searching
watchDebounced(
    [search, statusFilter, passwordFilter, loanFilter, dateFilter, fromDate, toDate, perPage],
    () => {
        updateFilters()
    },
    { debounce: 500, maxWait: 1000 }
)

const importForm = useForm({
    file: null,
})

const handleFileChange = (e) => {
    importForm.file = e.target.files[0]
}

const submitImport = () => {
    importForm.post(route('admin.members.import'), {
        onSuccess: () => {
            showImport.value = false
            importForm.reset()
        },
    })
}

const loanStatusVariant = (status) => {
    if (status === 'active') return 'default'
    if (status === 'completed') return 'secondary'
    if (status === 'suspended') return 'destructive'
    return 'outline'
}

const changePerPage = (value) => {
    perPage.value = value
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Members</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage all cooperative members
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

                    <!-- Custom Date Inputs (Absolute positioned or inline) -->
                    <div v-if="dateFilter === 'custom'" class="flex items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-300">
                        <Input v-model="fromDate" type="date" class="w-[130px] h-9 text-xs" />
                        <span class="text-muted-foreground">-</span>
                        <Input v-model="toDate" type="date" class="w-[130px] h-9 text-xs" />
                    </div>

                    <div class="h-8 w-px bg-border mx-1 hidden md:block"></div>

                    <Button variant="outline" size="sm" as-child>
                        <a :href="route('admin.members.template')">
                            <Download class="h-4 w-4 mr-2" />
                            Template
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" @click="showImport = !showImport">
                        <Upload class="h-4 w-4 mr-2" />
                        Import
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="route('admin.members.create')">
                            <Plus class="h-4 w-4 mr-2" />
                            Add New member
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Import Panel -->
            <Card v-if="showImport">
                <CardHeader>
                    <CardTitle class="text-base">Import Members via CSV/Excel</CardTitle>
                    <CardDescription>
                        Upload a CSV or Excel file. Required columns: name, email. Optional: phone, member_id.
                        Download the template above to get started.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitImport" class="flex items-end gap-4">
                        <div class="flex-1 space-y-2">
                            <Input
                                type="file"
                                accept=".csv,.xlsx,.xls"
                                @change="handleFileChange"
                                :class="importForm.errors.file ? 'border-destructive' : ''"
                            />
                            <p v-if="importForm.errors.file" class="text-xs text-destructive">
                                {{ importForm.errors.file }}
                            </p>
                        </div>
                        <Button type="submit" :disabled="importForm.processing || !importForm.file">
                            {{ importForm.processing ? 'Importing...' : 'Import' }}
                        </Button>
                        <Button variant="outline" type="button" @click="showImport = false">
                            Cancel
                        </Button>
                    </form>
                </CardContent>
            </Card>

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
                                 <Link :href="card.route" class="">
                                {{ card.label }}
                                </Link>
                            </CardTitle>
                            <component
                                :is="card.icon"
                                class="h-4 w-4 text-primary"
                            />
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-foreground">{{ card.value }}</p>
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
                                    placeholder="Search members..."
                                    class="pl-9 h-10 border-none shadow-sm rounded-xl"
                                />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Status Filter -->
                            <Select v-model="statusFilter">
                                <SelectTrigger class="w-[130px] h-10 border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Password Filter -->
                            <Select v-model="passwordFilter">
                                <SelectTrigger class="w-[130px] h-10 border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Password" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Password</SelectItem>
                                    <SelectItem value="must_change">Must Change</SelectItem>
                                    <SelectItem value="changed">Changed</SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Loan Filter -->
                            <Select v-model="loanFilter">
                                <SelectTrigger class="w-[130px] h-10 border-none shadow-sm rounded-xl">
                                    <SelectValue placeholder="All Loans" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Loans</SelectItem>
                                    <SelectItem value="active">Active Loan</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                    <SelectItem value="suspended">Suspended</SelectItem>
                                    <SelectItem value="no loan">No Loan</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="px-0">
                    <div class="rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                        <!-- Empty state -->
                        <div v-if="members.data.length === 0" class="text-center py-20">
                            <Users class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No members found</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Member ID</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Name</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Division</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Phone</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Loan</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="member in members.data"
                                        :key="member.id"
                                        class="hover:bg-muted/20 transition-colors"
                                    >
                                        <td class="py-4 px-6 font-mono text-xs text-muted-foreground">
                                            {{ member.member_id ?? '—' }}
                                        </td>
                                        <td class="py-4 px-6 font-medium text-foreground">
                                            {{ member.name }}
                                        </td>
                                        <td class="py-4 px-6 text-muted-foreground">
                                            {{ member.division_name ?? '—' }}
                                        </td>
                                        <td class="py-4 px-6 text-muted-foreground">
                                            {{ member.phone ?? '—' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge :variant="loanStatusVariant(member.loan_status)" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ member.loan_status }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge :variant="member.is_active ? 'default' : 'destructive'" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ member.is_active ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <Button variant="ghost" size="sm" class="h-8 rounded-lg hover:bg-muted" as-child>
                                                <Link :href="route('admin.members.show', member.id)">
                                                    View Details
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="members.last_page > 1" class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 px-2">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-muted-foreground">Rows per page</span>
                                <Select v-model="perPage" @update:modelValue="changePerPage">
                                    <SelectTrigger class="w-[70px] h-8 border-none shadow-sm rounded-lg text-xs">
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
                                Showing {{ members.from }}-{{ members.to }} of {{ members.total }}
                            </p>
                        </div>
                        <Pagination :links="members.links" />
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
