<div x-data="{ 
    state: $wire.entangle('state'),
    transactionId: $wire.entangle('transactionId'),
    paymentDate: $wire.entangle('paymentDate'),
    paymentTime: $wire.entangle('paymentTime'),
    clientReference: $wire.entangle('clientReference'),
    errorMessage: $wire.entangle('errorMessage'),
    formData: $wire.entangle('formData'),
    step: $wire.entangle('step'),
    verifiedName: $wire.entangle('verifiedName')
}" x-init="
    window.addEventListener('focus-error', event => {
        const field = event.detail.field;
        const id = field.replace(/\./g, '_');
        const element = document.getElementById(id);
        if (element) {
            element.focus();
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
">
    <!-- Payment Form State -->
    <div x-show="state === 'form'" x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="form-card w-full max-w-md p-8 lg:p-10 transition-all duration-500 transform">
        <div class="mb-8 text-center sm:text-left">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">SaganPay</h1>
            <p class="text-gray-500 text-sm">Please fill in the details below to proceed with your payment.</p>
        </div>

        <form wire:submit.prevent="submitForm" class="space-y-6">

            <!-- Step 1: Service Selection & Validation -->
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                <!-- Service Grid -->
                <label class="block text-sm font-semibold text-gray-700 mb-2">Select Service</label>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div @click="$wire.set('formData.service_type', 'ECG_Prepaid')"
                        class="cursor-pointer border rounded-xl p-4 flex flex-col items-center justify-center gap-2 transition-all hover:border-sky-500 hover:shadow-md"
                        :class="['ECG_Prepaid', 'ECG_Postpaid'].includes(formData.service_type) ? 'border-sky-500 bg-sky-50 ring-2 ring-sky-200' : 'border-gray-200 bg-white'">
                        <img src="/assets/images/services/ecg.png" class="h-12 w-auto object-contain" alt="ECG">
                        <span class="text-xs font-bold text-center text-gray-700">ECG</span>
                    </div>
                    <div @click="$wire.set('formData.service_type', 'Ghana_Water_Postpaid')"
                        class="cursor-pointer border rounded-xl p-4 flex flex-col items-center justify-center gap-2 transition-all hover:border-sky-500 hover:shadow-md"
                        :class="formData.service_type === 'Ghana_Water_Postpaid' ? 'border-sky-500 bg-sky-50 ring-2 ring-sky-200' : 'border-gray-200 bg-white'">
                        <img src="/assets/images/services/water.png" class="h-12 w-auto object-contain" alt="Water">
                        <span class="text-xs font-bold text-center text-gray-700">Ghana Water</span>
                    </div>
                    <div @click="$wire.set('formData.service_type', 'DSTV')"
                        class="cursor-pointer border rounded-xl p-4 flex flex-col items-center justify-center gap-2 transition-all hover:border-sky-500 hover:shadow-md"
                        :class="formData.service_type === 'DSTV' ? 'border-sky-500 bg-sky-50 ring-2 ring-sky-200' : 'border-gray-200 bg-white'">
                        <img src="/assets/images/services/dstv.png" class="h-12 w-auto object-contain" alt="DSTV">
                        <span class="text-xs font-bold text-center text-gray-700">DSTV</span>
                    </div>
                    <div @click="$wire.set('formData.service_type', 'GOTV')"
                        class="cursor-pointer border rounded-xl p-4 flex flex-col items-center justify-center gap-2 transition-all hover:border-sky-500 hover:shadow-md"
                        :class="formData.service_type === 'GOTV' ? 'border-sky-500 bg-sky-50 ring-2 ring-sky-200' : 'border-gray-200 bg-white'">
                        <img src="/assets/images/services/gotv.png" class="h-12 w-auto object-contain" alt="GoTV">
                        <span class="text-xs font-bold text-center text-gray-700">GoTV</span>
                    </div>
                </div>

                <!-- Account Input (Only for Non-ECG in Step 1) -->
                <div x-show="formData.service_type && !['ECG_Prepaid', 'ECG_Postpaid'].includes(formData.service_type)">
                    <div class="space-y-2 mb-4">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                            Account Number
                        </label>
                        <div class="input-group relative">
                            <input type="text" wire:model="formData.account_number" placeholder="Enter account number"
                                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 transition-all focus:bg-white">
                        </div>
                    </div>

                    <!-- Mobile Number for Ghana Water (Required for validation) -->
                    <div x-show="formData.service_type === 'Ghana_Water_Postpaid'" class="space-y-2 mb-4">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                            Mobile Number
                        </label>
                        <div class="input-group relative">
                            <input type="tel" wire:model="formData.mobile_number" placeholder="0501234567"
                                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 transition-all focus:bg-white">
                        </div>
                        <p class="text-xs text-gray-500">Required to validate Ghana Water meter</p>
                    </div>

                    <!-- Error Alert -->
                    @if($errorMessage)
                        <div class="mb-4 bg-rose-50 border border-rose-200 rounded-lg p-4 flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-rose-800">{{ $errorMessage }}</p>
                            </div>
                        </div>
                    @endif

                    <button type="button" wire:click="validateAccount" wire:loading.attr="disabled"
                        wire:target="validateAccount"
                        class="w-full py-3.5 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-[8px] transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="validateAccount">Validate Account</span>
                        <span wire:loading wire:target="validateAccount">Validating...</span>
                    </button>
                </div>

                <div x-show="!formData.service_type" class="text-center py-4 text-gray-400 text-sm">
                    Select a service to proceed
                </div>
            </div>

            <!-- Step 2: Details & Payment -->
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                <div class="flex items-center justify-between mb-6">
                    <button type="button" @click="$wire.set('step', 1); $wire.set('formData.service_type', '')"
                        class="text-sm text-gray-500 hover:text-gray-900 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Change Service
                    </button>
                    <span class="text-sm font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded-full"
                        x-text="formData.service_type.replace('_Prepaid', '').replace('_Postpaid', '').replace('_', ' ')"></span>
                </div>

                <!-- Verified Name Display -->
                <div x-show="verifiedName"
                    class="mb-6 bg-emerald-50 border border-emerald-100 p-4 rounded-lg flex items-center gap-3">
                    <div class="bg-emerald-100 p-2 rounded-full text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wide">Verified Account</p>
                        <p class="text-gray-900 font-bold" x-text="verifiedName"></p>
                    </div>
                </div>

                <!-- ECG Specific: Account Number (Since skipped S1) -->
                <div x-show="['ECG_Prepaid', 'ECG_Postpaid'].includes(formData.service_type)" class="space-y-2 mb-4">
                    <label class="text-sm font-semibold text-gray-700">Meter Number</label>
                    <input type="text" wire:model="formData.account_number" placeholder="Enter meter number"
                        class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900">
                    @error('formData.account_number') <span class="text-rose-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Amount -->
                <div class="space-y-2 mb-4">
                    <label class="text-sm font-semibold text-gray-700">Amount (GHS)</label>
                    <input type="text" wire:model="formData.amount" placeholder="0.00" inputmode="decimal"
                        class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900">
                    @error('formData.amount') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Customer Name (Editable if not verified or ECG) -->
                <div x-show="!verifiedName" class="space-y-2 mb-4">
                    <label class="text-sm font-semibold text-gray-700">Customer Name</label>
                    <input type="text" wire:model="formData.customer_name" placeholder="John Doe"
                        class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900">
                    @error('formData.customer_name') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <!-- Mobile -->
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Mobile Number</label>
                        <input type="tel" wire:model="formData.mobile_number" placeholder="050..."
                            class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900">
                        @error('formData.mobile_number') <span class="text-rose-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Email -->
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-gray-700">Email</label>
                        <input type="email" wire:model="formData.email" placeholder="email@example.com"
                            class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900">
                        @error('formData.email') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="submitForm"
                    class="w-full py-4 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-[8px] transition-all shadow-lg shadow-sky-200">
                    <span wire:loading.remove wire:target="submitForm">Pay Now</span>
                    <span wire:loading wire:target="submitForm">Processing...</span>
                </button>
            </div>
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
    <div x-cloak x-show="state === 'processing'" wire:poll.2s="pollTransactionStatus"
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
        <p class="text-gray-500 mb-6 text-sm italic">Waiting for confirmation from your network...</p>
        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
            <div class="bg-sky-500 h-full transition-all duration-[30s] ease-linear w-0" x-data
                x-init="setTimeout(() => $el.classList.add('w-full'), 100)"></div>
        </div>
        <div class="mt-4 flex items-center justify-center gap-2 text-xs text-emerald-600 font-semibold">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            Please check your phone for the code or message.
        </div>
        <div class="mt-2">
            <span class="text-sky-500 text-sm">Instant Utility Payments • Secure & Reliable</span>
        </div>
    </div>

    <!-- Success State -->
    <div x-cloak x-show="state === 'success'" x-transition:enter="transition ease-out duration-500 delay-300"
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
            <p class="text-gray-500 text-sm mt-1">Successfully Paid GHS
                <span>{{ number_format((float) ($formData['amount'] ?? 0), 2) }}</span>
            </p>
        </div>

        <div class="px-8 pb-8 space-y-6 mt-6">
            <div class="space-y-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Trans. ID</span>
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
    <div x-cloak x-show="state === 'failed'" x-transition:enter="transition ease-out duration-500 delay-300"
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
            <p class="text-gray-500 text-sm mt-1">Unable to process your payment of GHS
                <span>{{ number_format((float) ($formData['amount'] ?? 0), 2) }}</span>
            </p>
        </div>

        <div class="px-8 pb-8 space-y-6">
            <div class="bg-rose-50 p-4 rounded-[8px]">
                <p class="text-rose-600 text-sm font-medium text-center" x-text="errorMessage">{{ $errorMessage }}</p>
            </div>

            <button wire:click="tryAgain"
                class="w-full py-4 bg-gray-900 hover:bg-black text-white font-bold rounded-[8px] transition-all shadow-lg shadow-gray-200">
                Try Again
            </button>
        </div>
    </div>
</div>