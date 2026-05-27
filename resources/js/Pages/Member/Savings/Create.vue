<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ArrowLeft, Info, UploadCloud } from 'lucide-vue-next'

const props = defineProps({
    minimumSavings: { type: Number, default: 0 },
    monthlyTarget: { type: Number, default: 0 },
})

const form = useForm({
    amount: '',
    narration: '',
    screenshot: null,
})

const handleFileUpload = (event) => {
    form.screenshot = event.target.files[0]
}

const submit = () => {
    form.post(route('member.savings.store'), {
        forceFormData: true,
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 2,
    }).format(amount ?? 0)
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6 max-w-4xl mx-auto">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="route('member.savings.index')">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold text-foreground">Make Savings Contribution</h2>
                    <p class="text-sm text-muted-foreground mt-1">Submit your payment proof and notify the admin for approval.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Contribution Details</CardTitle>
                    <CardDescription>
                        Upload proof of payment and enter the amount you deposited.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div class="space-y-2">
                                <Label for="amount">Amount (NGN)</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    :min="minimumSavings"
                                    v-model="form.amount"
                                    placeholder="Enter amount"
                                    :class="form.errors.amount ? 'border-destructive' : ''"
                                />
                                <p v-if="form.errors.amount" class="text-xs text-destructive">{{ form.errors.amount }}</p>
                                <p class="text-xs text-muted-foreground">Minimum contribution: {{ formatCurrency(minimumSavings) }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="narration">Narration (Optional)</Label>
                                <textarea
                                    id="narration"
                                    v-model="form.narration"
                                    rows="4"
                                    class="w-full rounded-md border border-input px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                                    :class="form.errors.narration ? 'border-destructive' : ''"
                                    placeholder="Optional description of this contribution"
                                ></textarea>
                                <p v-if="form.errors.narration" class="text-xs text-destructive">{{ form.errors.narration }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="screenshot">Payment Proof (Screenshot)</Label>
                                <label class="flex items-center gap-3 rounded-lg border border-input px-4 py-3 bg-background text-sm text-muted-foreground cursor-pointer hover:border-primary">
                                    <UploadCloud class="h-5 w-5 text-primary" />
                                    <span>{{ form.screenshot ? form.screenshot.name : 'Choose an image file' }}</span>
                                    <input
                                        id="screenshot"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="handleFileUpload"
                                    />
                                </label>
                                <p v-if="form.errors.screenshot" class="text-xs text-destructive">{{ form.errors.screenshot }}</p>
                                <p class="text-xs text-muted-foreground">Upload your payment receipt image (max 2MB).</p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-primary/10 border border-primary/20 p-4">
                            <div class="flex items-start gap-3">
                                <Info class="h-5 w-5 text-primary shrink-0" />
                                <div class="text-sm text-primary-foreground">
                                    <p class="font-semibold">Important</p>
                                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm text-muted-foreground">
                                        <li>Your contribution is reviewed by the admin before approval.</li>
                                        <li>Minimum contribution: {{ formatCurrency(minimumSavings) }}</li>
                                        <li v-if="monthlyTarget > 0">Your monthly target is {{ formatCurrency(monthlyTarget) }}.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Submitting...' : 'Submit Contribution' }}
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('member.savings.index')">Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
