<template>
    <MemberLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Make Savings Contribution
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <Link :href="route('member.savings.index')" class="text-blue-600 hover:text-blue-900">
                                &larr; Back to Savings
                            </Link>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-6">
                                <!-- Amount -->
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount (NGN)</label>
                                    <input
                                        type="number"
                                        id="amount"
                                        v-model="form.amount"
                                        :min="minimumSavings"
                                        step="0.01"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': form.errors.amount }"
                                    />
                                    <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                                    <p class="mt-1 text-sm text-gray-500">Minimum: {{ formatCurrency(minimumSavings) }}</p>
                                </div>

                                <!-- Narration -->
                                <div>
                                    <label for="narration" class="block text-sm font-medium text-gray-700">Narration (Optional)</label>
                                    <textarea
                                        id="narration"
                                        v-model="form.narration"
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': form.errors.narration }"
                                    ></textarea>
                                    <p v-if="form.errors.narration" class="mt-1 text-sm text-red-600">{{ form.errors.narration }}</p>
                                </div>

                                <!-- Screenshot -->
                                <div>
                                    <label for="screenshot" class="block text-sm font-medium text-gray-700">Payment Proof (Screenshot)</label>
                                    <input
                                        type="file"
                                        id="screenshot"
                                        @change="handleFileUpload"
                                        accept="image/*"
                                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        :class="{ 'border-red-500': form.errors.screenshot }"
                                    />
                                    <p v-if="form.errors.screenshot" class="mt-1 text-sm text-red-600">{{ form.errors.screenshot }}</p>
                                    <p class="mt-1 text-sm text-gray-500">Upload a screenshot of your payment proof (max 2MB)</p>
                                </div>

                                <!-- Info Box -->
                                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                                            <div class="mt-2 text-sm text-blue-700">
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li>Your contribution will be reviewed by an admin before being added to your savings.</li>
                                                    <li>Minimum contribution: {{ formatCurrency(minimumSavings) }}</li>
                                                    <li v-if="monthlyTarget > 0">Your monthly target: {{ formatCurrency(monthlyTarget) }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex items-center justify-end">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ form.processing ? 'Submitting...' : 'Submit Contribution' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import MemberLayout from '@/Layouts/MemberLayout.vue'

const props = defineProps({
    minimumSavings: Number,
    monthlyTarget: Number,
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
        currency: 'NGN'
    }).format(amount)
}
</script>
