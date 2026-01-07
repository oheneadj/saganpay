<div class="space-y-6">
    <!-- Filters -->
    <div
        class="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Search by customer, account, or ref..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:bg-white focus:ring-sky-500 focus:border-sky-500 transition-all">
        </div>

        <div class="flex gap-4 w-full md:w-auto">
            <select wire:model.live="service"
                class="block w-full md:w-48 py-2 pl-3 pr-10 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:bg-white focus:ring-sky-500 transition-all">
                <option value="">All Services</option>
                <option value="ECG_Prepaid">ECG Prepaid</option>
                <option value="ECG_Postpaid">ECG Postpaid</option>
                <option value="Ghana_Water">Ghana Water</option>
            </select>

            <select wire:model.live="status"
                class="block w-full md:w-36 py-2 pl-3 pr-10 border border-gray-200 rounded-lg bg-gray-50 text-sm focus:bg-white focus:ring-sky-500 transition-all">
                <option value="">All Statuses</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
                <option value="pending">Pending</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Service
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Reference</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Amount
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-sm">
                                        {{ substr($transaction->customer_name, 0, 1) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900">{{ $transaction->customer_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $transaction->account_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-sky-50 text-sky-700">
                                    {{ str_replace('_', ' ', $transaction->service_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                {{ $transaction->client_reference }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                GHS {{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($transaction->status === 'success')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2"></div>
                                        Success
                                    </span>
                                @elseif($transaction->status === 'failed')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></div>
                                        Failed
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-2"></div>
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $transaction->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button wire:click="viewTransaction({{ $transaction->id }})"
                                    class="inline-flex items-center px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-xs font-bold rounded-lg transition-all shadow-sm shadow-sky-100">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 italic">
                                No transactions found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Transaction Details Modal -->
    <x-modal name="transaction-details" :show="$showModal" wire:model="showModal">
        @if($selectedTransaction)
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-sky-50 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Transaction Details</h3>
                            <p class="text-sm text-gray-500">{{ $selectedTransaction->client_reference }}</p>
                        </div>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-y-8 gap-x-12 mb-10">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Customer Name</p>
                        <p class="text-sm font-bold text-gray-900">{{ $selectedTransaction->customer_name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Account Number</p>
                        <p class="text-sm font-bold text-gray-900">{{ $selectedTransaction->account_number }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Service Type</p>
                        <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-md bg-sky-50 text-sky-700">
                            {{ str_replace('_', ' ', $selectedTransaction->service_type) }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Amount Paid</p>
                        <p class="text-lg font-bold text-gray-900">GHS {{ number_format($selectedTransaction->amount, 2) }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Status</p>
                        @if($selectedTransaction->status === 'success')
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Success
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                {{ ucfirst($selectedTransaction->status) }}
                            </span>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Date & Time</p>
                        <p class="text-sm font-bold text-gray-900">
                            {{ $selectedTransaction->created_at->format('M d, Y @ H:i:s') }}
                        </p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center gap-4">
                    <button type="button" onclick="window.print()"
                        class="flex-1 py-3.5 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-xl shadow-sm shadow-sky-100 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Receipt
                    </button>
                    <button wire:click="closeModal"
                        class="px-6 py-3.5 bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold rounded-xl transition-all">
                        Close
                    </button>
                </div>
            </div>

            <!-- Print-on-only Receipt Area -->
            <div id="printable-receipt" class="hidden print:block p-8 border-2 border-gray-100">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold">SaganPay Receipt</h1>
                    <p class="text-gray-500">Official Payment Confirmation</p>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold">Reference:</span>
                        <span>{{ $selectedTransaction->client_reference }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold">Customer:</span>
                        <span>{{ $selectedTransaction->customer_name }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold">Account:</span>
                        <span>{{ $selectedTransaction->account_number }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold">Service:</span>
                        <span>{{ str_replace('_', ' ', $selectedTransaction->service_type) }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold text-xl">Total Amount:</span>
                        <span class="font-bold text-xl text-sky-600">GHS
                            {{ number_format($selectedTransaction->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold">Status:</span>
                        <span>{{ strtoupper($selectedTransaction->status) }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-bold">Date:</span>
                        <span>{{ $selectedTransaction->created_at->format('M d, Y H:i:s') }}</span>
                    </div>
                </div>
                <div class="mt-12 text-center text-gray-400 text-sm">
                    Thank you for using SaganPay!
                </div>
            </div>
        @endif
    </x-modal>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #printable-receipt,
            #printable-receipt * {
                visibility: visible;
            }

            #printable-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</div>