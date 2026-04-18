<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ActivitySquare, Search, Filter, X } from 'lucide-vue-next'

const props = defineProps({
    activities: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const event = ref(props.filters?.event || '')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')
const showFilters = ref(false)
const expanded = ref(null)

const toggleRow = (id) => {
    expanded.value = expanded.value === id ? null : id
}

const hasChanges = (activity) => {
    return Object.keys(activity.properties?.attributes || {}).length > 0
}

const applyFilters = () => {
    router.get(route('admin.activity.index'), {
        search: search.value || undefined,
        event: event.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    event.value = ''
    dateFrom.value = ''
    dateTo.value = ''
    router.get(route('admin.activity.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    })
}

const eventVariant = (event) => {
    if (event === 'created') return 'default'
    if (event === 'updated') return 'secondary'
    if (event === 'deleted') return 'destructive'
    return 'outline'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div>
                <h2 class="text-2xl font-bold text-foreground">Activity Log</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Track all system actions and changes
                </p>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">Recent Activity</CardTitle>
                            <CardDescription>{{ activities.total }} system events</CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" @click="showFilters = !showFilters">
                                <Filter class="h-4 w-4 mr-2" />
                                Filters
                            </Button>
                            <div class="relative w-64">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input v-model="search" placeholder="Search activity..." class="pl-9"
                                    @keyup.enter="applyFilters" />
                            </div>
                        </div>
                    </div>

                    <!-- Filters Panel -->
                    <div v-if="showFilters" class="mt-4 p-4 bg-muted/40 rounded-lg space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium">Filters</h4>
                            <Button variant="ghost" size="sm" @click="clearFilters">
                                <X class="h-4 w-4 mr-1" />
                                Clear
                            </Button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Event Type</label>
                                <select v-model="event"
                                    class="w-full border border-input rounded-md px-3 py-2 text-sm bg-background">
                                    <option value="">All Events</option>
                                    <option value="created">Created</option>
                                    <option value="updated">Updated</option>
                                    <option value="deleted">Deleted</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Date From</label>
                                <Input v-model="dateFrom" type="date" class="w-full" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Date To</label>
                                <Input v-model="dateTo" type="date" class="w-full" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button variant="outline" size="sm" @click="showFilters = false">Cancel</Button>
                            <Button size="sm" @click="applyFilters">Apply Filters</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="activities.data.length === 0" class="text-center py-10">
                        <ActivitySquare class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No activity recorded yet</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Description</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">By</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Model</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Event</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="a in activities.data" :key="a.id">
                                    <!-- Main Row -->
                                    <tr class="border-b hover:bg-muted/40 cursor-pointer" @click="toggleRow(a.id)">
                                        <td class="py-3 pr-4 max-w-xs truncate">
                                            {{ a.description }}
                                        </td>

                                        <td class="py-3 pr-4">
                                            <p class="font-medium">{{ a.causer }}</p>
                                            <p class="text-xs text-muted-foreground font-mono">
                                                {{ a.causer_id }}
                                            </p>
                                        </td>

                                        <td class="py-3 pr-4">
                                            <Badge variant="outline">
                                                {{ a.subject }} #{{ a.subject_id ?? '—' }}
                                            </Badge>
                                        </td>

                                        <td class="py-3 pr-4">
                                            <Badge :variant="eventVariant(a.event)">
                                                {{ a.event ?? '—' }}
                                            </Badge>
                                        </td>

                                        <td class="py-3 text-xs text-muted-foreground">
                                            {{ a.created_at }}
                                        </td>
                                    </tr>

                                    <!-- Expanded Row -->
                                    <tr v-if="expanded === a.id" class="bg-muted/20">
                                        <td colspan="5" class="p-4">
                                            <div v-if="hasChanges(a)" class="space-y-3">

                                                <p class="text-xs font-semibold text-muted-foreground">
                                                    Changes
                                                </p>

                                                <div class="overflow-x-auto">
                                                    <table class="w-full text-xs">
                                                        <thead>
                                                            <tr class="text-left text-muted-foreground">
                                                                <th class="pb-2">Field</th>
                                                                <th class="pb-2">Old</th>
                                                                <th class="pb-2">New</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(value, key) in a.properties.attributes"
                                                                :key="key" class="border-t">
                                                                <td class="py-2 font-medium">{{ key }}</td>

                                                                <td class="py-2 text-red-500">
                                                                    {{ a.properties.old?.[key] ?? '—' }}
                                                                </td>

                                                                <td class="py-2 text-green-600">
                                                                    {{ value }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div v-else class="text-xs text-muted-foreground">
                                                No detailed changes recorded.
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="activities.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ activities.from }} to {{ activities.to }} of {{ activities.total }} results
                        </p>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" :disabled="!activities.prev_page_url"
                                @click="router.visit(activities.prev_page_url)">
                                Previous
                            </Button>
                            <Button variant="outline" size="sm" :disabled="!activities.next_page_url"
                                @click="router.visit(activities.next_page_url)">
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
