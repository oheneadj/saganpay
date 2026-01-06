<div x-data="{ 
    state: @entangle('state'),
    formData: @entangle('formData'),
    transactionId: @entangle('transactionId'),
    paymentDate: @entangle('paymentDate'),
    paymentTime: @entangle('paymentTime'),
    clientReference: @entangle('clientReference')
}">
    <!-- Payment Form State -->
    <div x-show="state === 'form'" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="form-card w-full max-w-md p-8 lg:p-10 transition-all duration-500 transform">
        <div class="mb-8 text-center sm:text-left">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">SaganPay</h1>
            <p class="text-gray-500 text-sm">Please fill in the details below to proceed with your payment.</p>
        </div>

        <form wire:submit.prevent="submitForm" class="space-y-6">
            <!-- Meter/Account Number -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Meter/Account Number
                </label>
                <div class="input-group relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-400 font-bold text-lg">#</span>
                    </div>
                    <input type="text" wire:model="formData.account_number" required
                        placeholder="Enter your meter/account number"
                        class="w-full pl-10 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white">
                </div>
                @error('formData.account_number') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Service Type -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Service Type
                </label>
                <div class="relative">
                    <select wire:model="formData.service_type" required
                        class="w-full appearance-none pl-4 pr-10 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 transition-all focus:bg-white">
                        <option value="ECG Prepaid">ECG Prepaid</option>
                        <option value="ECG Postpaid">ECG Postpaid</option>
                        <option value="Ghana Water">Ghana Water</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <svg class="h-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                @error('formData.service_type') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Amount to Pay -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Amount to Pay (GHS)
                </label>
                <div class="input-group relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-900 font-bold">GHS</span>
                    </div>
                    <input type="number" wire:model="formData.amount" required step="0.01" placeholder="0.00"
                        class="w-full pl-16 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white">
                </div>
                @error('formData.amount') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Customer Name -->
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Customer Name
                </label>
                <div class="input-group relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model="formData.customer_name" required placeholder="John Doe"
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white">
                </div>
                @error('formData.customer_name') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Mobile Number -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700">Mobile Number</label>
                    <input type="tel" wire:model="formData.mobile_number" required placeholder="0501234567"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white">
                    @error('formData.mobile_number') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <!-- Email -->
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-gray-700">Email Address</label>
                    <input type="email" wire:model="formData.email" required placeholder="email@example.com"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white">
                    @error('formData.email') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submitForm"
                class="w-full py-4 bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white font-bold rounded-[8px] shadow-sm shadow-sky-200 transition-all transform hover:translate-y-[-2px] active:translate-y-[0px] disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="submitForm">Proceed Payment</span>
                <span wire:loading wire:target="submitForm">Processing...</span>
            </button>
        </form>

        <div class="flex items-center justify-center gap-2 text-gray-400 text-sm mt-4">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                    clip-rule="evenodd" />
            </svg>
            <span>© {{ date('Y') }} SaganPay - Instant Utility Payments</span>
        </div>
    </div>

    <!-- Processing State -->
    @if($state === 'processing')
        <div x-show="state === 'processing'" wire:poll.2s="pollTransactionStatus"
            x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="form-card w-full max-w-md p-12 text-center transition-all duration-500">
            <div class="relative flex justify-center mb-8">
                <!-- Outer Spinner -->
                <div class="w-24 h-24 border-4 border-sky-100 rounded-full"></div>
                <div
                    class="absolute top-0 w-24 h-24 border-4 border-sky-500 rounded-full border-t-transparent animate-spinner-slow">
                </div>
                <!-- Secured Lock Icon -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-10 h-10 text-sky-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Processing Payment</h2>
            <p class="text-gray-500 mb-6 text-sm italic">Securing your transaction with premium encryption...</p>
            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-sky-500 h-full transition-all duration-[3000ms] ease-linear w-0"
                    :class="state === 'processing' ? 'w-full' : ''"></div>
            </div>
            <div class="mt-4 flex items-center justify-center gap-2 text-xs text-emerald-600 font-semibold">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                Secured Payment
            </div>
            <div class="mt-2">
                <span class="text-sky-500 text-sm">Instant Utility Payments • Secure & Reliable</span>
            </div>
        </div>
    @endif

    <!-- Success State -->
    <div x-show="state === 'success'" x-transition:enter="transition ease-out duration-500 delay-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="form-card w-full max-sm:max-w-xs max-w-sm overflow-hidden transition-all duration-500">
        <div class="bg-white p-8 text-center border-b border-dashed border-gray-100">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Payment Successful</h2>
            <p class="text-gray-500 text-sm mt-1">Successfully Paid GHS <span
                    x-text="Number(formData.amount).toFixed(2)"></span></p>
        </div>

        <div class="px-8 pb-8 space-y-6 mt-6">
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Transaction ID</span>
                    <span class="text-gray-900 font-semibold" x-text="transactionId"></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Service Name</span>
                    <span class="text-gray-900 font-semibold" x-text="formData.service_type"></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Account No.</span>
                    <span class="text-gray-900 font-semibold" x-text="formData.account_number"></span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Total Amount</span>
                    <span class="text-sky-600 font-bold" x-text="'GHS ' + Number(formData.amount).toFixed(2)"></span>
                </div>
            </div>

            <button wire:click="resetForm"
                class="w-full py-4 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-[8px] transition-all shadow-sm shadow-sky-100">
                Buy Again
            </button>
        </div>
    </div>

    <!-- Failed State -->
    <div x-show="state === 'failed'" x-transition:enter="transition ease-out duration-500 delay-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="form-card w-full max-sm:max-w-xs max-w-sm overflow-hidden transition-all duration-500">
        <div class="bg-white p-8 text-center">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Payment Failed</h2>
            <p class="text-gray-500 text-sm mt-1">Unable to process your payment of GHS <span
                    x-text="Number(formData.amount).toFixed(2) || '0.00'"></span></p>
        </div>

        <div class="px-8 pb-8 space-y-6">
            <div class="bg-rose-50 p-4 rounded-[8px]">
                <p class="text-rose-600 text-sm font-medium text-center">
                    Wait for some time and check your connection or contact your bank if the issue persists.
                </p>
            </div>

            <button wire:click="tryAgain"
                class="w-full py-4 bg-gray-900 hover:bg-black text-white font-bold rounded-[8px] transition-all shadow-lg shadow-gray-200">
                Try Again
            </button>
        </div>
    </div>
</div>