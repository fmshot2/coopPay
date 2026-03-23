<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { ActivitySquare, Search } from 'lucide-vue-next'

const props = defineProps({
    activities: Array,
})

const search = ref('')

const filtered = computed(() => {
    if (!search.value) return props.activities
    const q = search.value.toLowerCase()
    return props.activities.filter(a =>
        a.description?.toLowerCase().includes(q) ||
        a.causer?.toLowerCase().includes(q) ||
        a.subject?.toLowerCase().includes(q)
    )
})

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
                            <CardDescription>Last 100 system events</CardDescription>
                        </div>
                        <div class="relative w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search activity..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="filtered.length === 0" class="text-center py-10">
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
                                <tr
                                    v-for="a in filtered"
                                    :key="a.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors"
                                >
                                    <td class="py-3 pr-4 text-foreground max-w-xs truncate">
                                        {{ a.description }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-foreground">{{ a.causer }}</p>
                                        <p class="text-xs text-muted-foreground font-mono">{{ a.causer_id }}</p>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="outline" class="text-xs">{{ a.subject }}</Badge>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="eventVariant(a.event)" class="text-xs">
                                            {{ a.event ?? '—' }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 text-muted-foreground text-xs">{{ a.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
