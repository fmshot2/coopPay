<template>
    <MemberLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Savings Contribution Details
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Contribution Details -->
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contribution Information</h3>

                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Amount</label>
                                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                                {{ formatCurrency(contribution.amount) }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Status</label>
                                            <span :class="getStatusClass(contribution.status)" class="mt-1 px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full">
                                                {{ contribution.status }}
                                            </span>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-500">Date Submitted</label>
                                            <p class="mt-1 text-sm text-gray-900">
                                                {{ formatDate(contribution.created_at) }}
                                            </p>
                                        </div>

                                        <div v-if="contribution.approved_at">
                                            <label class="block text-sm font-medium text-gray-500">Date Approved</label>
                                            <p class="mt-1 text-sm text-gray-900">
                                                {{ formatDate(contribution.approved_at) }}
                                            </p>
                                        </div>

                                        <div v-if="contribution.narration">
                                            <label class="block text-sm font-medium text-gray-500">Narration</label>
                                            <p class="mt-1 text-sm text-gray-900">
                                                {{ contribution.narration }}
                                            </p>
                                        </div>

                                        <div v-if="contribution.admin_note">
                                            <label class="block text-sm font-medium text-gray-500">Admin Note</label>
                                            <p class="mt-1 text-sm text-gray-900">
                                                {{ contribution.admin_note }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Screenshot -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Proof</h3>

                                <div v-if="contribution.screenshot_path" class="border rounded-lg overflow-hidden">
                                    <img
                                        :src="'/storage/' + contribution.screenshot_path"
                                        alt="Payment Proof"
                                        class="w-full h-auto"
                                    />
                                </div>
                                <div v-else class="border rounded-lg p-8 text-center text-gray-500">
                                    No screenshot uploaded
                                </div>
                            </div>
                        </div>

                        <!-- Status Messages -->
                        <div v-if="contribution.status === 'pending'" class="mt-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Pending Approval</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Your contribution is being reviewed by an admin. You will be notified once it's approved.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="contribution.status === 'approved'" class="mt-6 bg-green-50 border border-green-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Approved</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>Your contribution has been approved and added to your savings balance.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="contribution.status === 'rejected'" class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Rejected</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>Your contribution was rejected. Please check the admin note above for more details.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import MemberLayout from '@/Layouts/MemberLayout.vue'

const props = defineProps({
    contribution: Object,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-NG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>
