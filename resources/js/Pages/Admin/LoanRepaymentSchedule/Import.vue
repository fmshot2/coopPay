<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { AlertTriangle, Upload } from 'lucide-vue-next'

const props = defineProps({
    years: { type: Array, default: () => [] },
    months: { type: Array, default: () => [] },
})

const months = ref(props.months || [])
const importForm = useForm({
    file: null,
    year_id: props.years[0]?.id || null,
    month_id: months.value[0]?.id || null,
})

const loadMonths = async (yearId) => {
    if (!yearId) {
        months.value = []
        importForm.month_id = null
        return
    }

    try {
        const response = await window.axios.get(route('admin.years.months', { year: yearId }))
        months.value = response.data
        if (!months.value.some((m) => m.id === importForm.month_id)) {
            importForm.month_id = months.value[0]?.id || null
        }
    } catch (e) {
        months.value = []
        importForm.month_id = null
    }
}

watch(() => importForm.year_id, (y) => loadMonths(y), { immediate: true })

const handleFileChange = (event) => {
    importForm.file = event.target.files[0]
}

const submitImport = () => {
    importForm.post(route('admin.deductions.import.process'), {
        onSuccess: () => {
            importForm.reset()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-foreground">Upload Monthly Deductions (CSV)</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Upload a CSV to import monthly deductions for the selected year and month.
                    Use the template or export sample to match the expected columns.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">CSV Import</CardTitle>
                    <CardDescription>
                        CSV format: no header row. Columns: name, division, amount, optional email or member id.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-sm font-medium text-muted-foreground mb-2">Year</span>
                                <Select v-model="importForm.year_id">
                                    <SelectTrigger class="w-full h-10 border-none shadow-sm rounded-xl text-sm">
                                        <SelectValue placeholder="Select year" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="year in props.years" :key="year.id"
                                            :value="year.id.toString()">
                                            {{ year.year }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-muted-foreground mb-2">Month</span>
                                <Select v-model="importForm.month_id">
                                    <SelectTrigger class="w-full h-10 border-none shadow-sm rounded-xl text-sm">
                                        <SelectValue placeholder="Select month" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="month in months" :key="month.id"
                                            :value="month.id.toString()">
                                            {{ month.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="flex items-end gap-4">
                            <div class="flex-1 space-y-2">
                                <Input id="file" type="file" accept=".csv,.xlsx,.xls" @change="handleFileChange"
                                    :class="importForm.errors.file ? 'border-destructive' : ''" />
                                <p v-if="importForm.errors.file" class="text-xs text-destructive">
                                    {{ importForm.errors.file }}
                                </p>
                            </div>
                            <Button type="submit"
                                :disabled="importForm.processing || !importForm.file || !importForm.year_id || !importForm.month_id">
                                <Upload class="h-4 w-4 mr-2" />
                                {{ importForm.processing ? 'Importing...' : 'Import' }}
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('admin.deductions.index')">Back to Deductions</Link>
                            </Button>
                        </div>

                        <div
                            class="rounded-xl border border-muted/30 bg-muted/10 p-4 text-sm text-muted-foreground space-y-2">
                            <div class="flex items-start gap-2">
                                <AlertTriangle class="h-4 w-4 text-destructive mt-0.5" />
                                <div>
                                    <p class="font-semibold text-foreground">CSV format</p>
                                    <p>No header row; only data rows.</p>
                                </div>
                            </div>
                            <p>
                                Column 1: <span class="font-medium">name</span><br />
                                Column 2: <span class="font-medium">division</span><br />
                                Column 3: <span class="font-medium">amount</span>
                            </p>
                        </div>

                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
