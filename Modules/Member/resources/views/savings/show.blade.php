<x-member::layouts.master>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Contribution Details</h1>
                <a href="{{ route('member.savings.index') }}" class="text-blue-500 hover:text-blue-700">
                    &larr; Back to Savings
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Amount</label>
                        <p class="mt-1 text-lg font-semibold text-gray-900">₦{{ number_format($contribution->amount, 2) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <p class="mt-1">
                            @if($contribution->status === 'pending')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @elseif($contribution->status === 'approved')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Rejected
                                </span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Date Submitted</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $contribution->created_at->format('M d, Y h:i A') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Approved By</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $contribution->approvedBy->name ?? '-' }}</p>
                    </div>

                    @if($contribution->approved_at)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Approved At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $contribution->approved_at->format('M d, Y h:i A') }}</p>
                        </div>
                    @endif

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-500">Narration</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $contribution->narration ?: 'No narration provided' }}</p>
                    </div>

                    @if($contribution->admin_note)
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Admin Note</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $contribution->admin_note }}</p>
                        </div>
                    @endif

                    @if($contribution->screenshot_path)
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Payment Proof</label>
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $contribution->screenshot_path) }}" alt="Payment Proof" class="max-w-full h-auto rounded-lg shadow-md">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-member::layouts.master>
