<x-member::layouts.master>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Make a Savings Contribution</h1>
                <a href="{{ route('member.savings.index') }}" class="text-blue-500 hover:text-blue-700">
                    &larr; Back to Savings
                </a>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('member.savings.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-6">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (₦) *</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="{{ $minimumSavings }}" value="{{ old('amount') }}" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('amount') border-red-500 @enderror">
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Minimum contribution: ₦{{ number_format($minimumSavings, 2) }}</p>
                    </div>

                    <div class="mb-6">
                        <label for="narration" class="block text-sm font-medium text-gray-700 mb-2">Narration</label>
                        <textarea name="narration" id="narration" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('narration') border-red-500 @enderror">{{ old('narration') }}</textarea>
                        @error('narration')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="screenshot" class="block text-sm font-medium text-gray-700 mb-2">Payment Proof (Screenshot) *</label>
                        <input type="file" name="screenshot" id="screenshot" accept="image/*" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('screenshot') border-red-500 @enderror">
                        @error('screenshot')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Upload a screenshot of your payment proof (max 2MB)</p>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Submit Contribution
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-member::layouts.master>
