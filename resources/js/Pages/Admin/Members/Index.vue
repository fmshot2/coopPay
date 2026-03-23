<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ref, computed } from 'vue'
import { Users, Plus, Search, Upload, Download } from 'lucide-vue-next'

const props = defineProps({
    members: Array,
})

const search = ref('')
const showImport = ref(false)

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

const filtered = computed(() => {
    if (!search.value) return props.members
    const q = search.value.toLowerCase()
    return props.members.filter(m =>
        m.name.toLowerCase().includes(q) ||
        m.email.toLowerCase().includes(q) ||
        m.member_id?.toLowerCase().includes(q)
    )
})

const loanStatusVariant = (status) => {
    if (status === 'active') return 'default'
    if (status === 'completed') return 'secondary'
    if (status === 'suspended') return 'destructive'
    return 'outline'
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Members</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage all cooperative members
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="route('admin.members.template')">
                            <Download class="h-4 w-4 mr-2" />
                            Download Template
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" @click="showImport = !showImport">
                        <Upload class="h-4 w-4 mr-2" />
                        Import CSV
                    </Button>
                    <Button as-child>
                        <Link :href="route('admin.members.create')">
                            <Plus class="h-4 w-4 mr-2" />
                            Add Member
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

            <!-- Search + Table Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-base">All Members</CardTitle>
                            <CardDescription>{{ members.length }} registered members</CardDescription>
                        </div>
                        <div class="relative w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search name, email, ID..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>

                    <!-- Empty state -->
                    <div v-if="filtered.length === 0" class="text-center py-10">
                        <Users class="h-10 w-10 text-muted-foreground mx-auto mb-3" />
                        <p class="text-sm text-muted-foreground">No members found</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Member ID</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Name</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Email</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Phone</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Loan</th>
                                    <th class="pb-3 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th class="pb-3 font-medium text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="member in filtered"
                                    :key="member.id"
                                    class="border-b last:border-0 hover:bg-muted/40 transition-colors"
                                >
                                    <td class="py-3 pr-4 font-mono text-xs">
                                        {{ member.member_id ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4 font-medium text-foreground">
                                        {{ member.name }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ member.email }}
                                    </td>
                                    <td class="py-3 pr-4 text-muted-foreground">
                                        {{ member.phone ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="loanStatusVariant(member.loan_status)" class="text-xs">
                                            {{ member.loan_status }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge :variant="member.is_active ? 'default' : 'destructive'" class="text-xs">
                                            {{ member.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </td>
                                    <td class="py-3">
                                        <Button variant="ghost" size="sm" as-child>
                                            <Link :href="route('admin.members.show', member.id)">
                                                View
                                            </Link>
                                        </Button>
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
