<script setup>
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Plus, Search, MoreHorizontal } from 'lucide-vue-next'

const props = defineProps({
    divisions: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const searchDivisions = () => {
    router.get(route('admin.divisions.index'), {
        search: search.value,
        per_page: props.filters?.per_page || 10,
    }, { preserveState: true })
}

const toggleActive = (division) => {
    router.patch(route('admin.divisions.toggle-active', division.id))
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">Divisions</h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Manage cooperative divisions
                        </p>
                    </div>
                </div>
                <Button as-child>
                    <Link :href="route('admin.divisions.create')">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Division
                    </Link>
                </Button>
            </div>

            <!-- Search & Filters -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        @keyup.enter="searchDivisions"
                        placeholder="Search divisions..."
                        class="pl-9"
                    />
                </div>
            </div>

            <!-- Divisions Table -->
            <Card>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium">Description</th>
                                    <th class="px-4 py-3 font-medium">Members</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Created</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="divisions.data.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                        No divisions found.
                                    </td>
                                </tr>
                                <tr v-for="division in divisions.data" :key="division.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors">
                                    <td class="px-4 py-3 font-medium">{{ division.name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground max-w-xs truncate">
                                        {{ division.description || '—' }}
                                    </td>
                                    <td class="px-4 py-3">{{ division.members_count }}</td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="division.is_active ? 'default' : 'secondary'">
                                            {{ division.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ division.created_at }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="route('admin.divisions.show', division.id)">
                                                    View
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="route('admin.divisions.edit', division.id)">
                                                    Edit
                                                </Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="divisions.links.length > 3" class="flex justify-center">
                <nav class="flex items-center gap-1">
                    <Link
                        v-for="link in divisions.links"
                        :key="link.label"
                        :href="link.url"
                        class="px-3 py-1 rounded text-sm"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                        v-html="link.label"
                    />
                </nav>
            </div>

        </div>
    </AppLayout>
</template>
