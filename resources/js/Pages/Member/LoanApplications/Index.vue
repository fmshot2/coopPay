<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { FileText, Plus, Search, Eye } from 'lucide-vue-next'

const props = defineProps({
    applications: { type: Array, default: () => [] },
    filters:      { type: Object, default: () => ({}) },
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || 'all')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}

const statusVariant = (s) => {
    if (s === 'approved') return 'default'
    if (s === 'rejected') return 'destructive'
    return 'outline'
}

const updateFilters = () => {
    router.get(route('member.loan-applications.index'), {
        search: search.value || undefined,
        status: status.value !== 'all' ? status.value : undefined,
    }, {
        preserveState:  true,
        preserveScroll: true,
        replace:        true,
    })
}

watchDebounced([search, status], updateFilters, { debounce: 400 })
</script>

<template>
    <AppLayout>
        <div class="space-y-8">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Loan Applications</h2>
                    <p class="text-sm text-muted-foreground mt-1">Track your loan application history</p>
                </div>
                <Button as-child>
                    <Link :href="route('member.loan-applications.create')">
                        <Plus class="h-4 w-4 mr-2" />
                        Apply for Loan
                    </Link>
                </Button>
            </div>

            <!-- Table -->
            <Card class="border-none shadow-none bg-transparent">
                <CardContent class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                        <div class="flex-1 max-w-sm">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search loan type..."
                                    class="pl-9 h-10 bg-background border-none shadow-sm rounded-xl"
                                />
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
                        <div v-if="applications.length === 0" class="text-center py-20">
                            <FileText class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
                            <p class="text-base text-muted-foreground">No loan applications yet</p>
                            <Button as-child class="mt-4">
                                <Link :href="route('member.loan-applications.create')">
                                    Apply for your first loan
                                </Link>
                            </Button>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/30 text-left">
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Loan Type</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Amount</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-center">Duration</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Monthly</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Applied</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground">Status</th>
                                        <th class="py-4 px-6 font-medium text-muted-foreground text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="a in applications"
                                        :key="a.id"
                                        class="hover:bg-muted/20 transition-colors"
                                    >
                                        <td class="py-4 px-6">
                                            <Badge variant="outline" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ a.loan_type?.name ?? '—' }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6 text-right font-medium text-primary">
                                            {{ formatCurrency(a.amount) }}
                                        </td>
                                        <td class="py-4 px-6 text-center text-muted-foreground">
                                            {{ a.duration_months }} months
                                        </td>
                                        <td class="py-4 px-6 text-right text-muted-foreground">
                                            {{ formatCurrency(a.monthly_payment) }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-muted-foreground">
                                            {{ new Date(a.created_at).toLocaleDateString('en-NG') }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <Badge :variant="statusVariant(a.status)" class="rounded-lg px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold">
                                                {{ a.status }}
                                            </Badge>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg" as-child>
                                                <Link :href="route('member.loan-applications.show', a.id)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
