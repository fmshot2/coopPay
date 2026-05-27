<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AlertTriangle } from 'lucide-vue-next'

const importForm = useForm({
    file: null,
})

const handleFileChange = (event) => {
    importForm.file = event.target.files[0]
}

const submitImport = () => {
    importForm.post(route('admin.members.import.csv'), {
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
                <h2 class="text-2xl font-bold text-foreground">Upload Members CSV</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Upload a CSV file with no header row. The first column should be member name and the second should
                    be division.
                    Exact duplicates with the same name and division will be skipped. Created members are flagged as
                    temporary.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">CSV Upload</CardTitle>
                    <CardDescription>
                        Use a plain CSV file without headers. If you want a sample, download the template.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="file">CSV File <span class="text-destructive">*</span></Label>
                            <Input id="file" type="file" accept=".csv" @change="handleFileChange"
                                :class="importForm.errors.file ? 'border-destructive' : ''" />
                            <p v-if="importForm.errors.file" class="text-xs text-destructive">
                                {{ importForm.errors.file }}
                            </p>
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
                                Column 2: <span class="font-medium">division</span>
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button type="submit" :disabled="importForm.processing || !importForm.file">
                                {{ importForm.processing ? 'Importing...' : 'Upload CSV' }}
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('admin.members.template')">Download Template</Link>
                            </Button>
                            <Button variant="ghost" as-child>
                                <Link :href="route('admin.members.index')">Back to Members</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
