<x-app-layout>
    @php
        $stats = [
            'revenue' => \App\Models\Transaction::where('status', 'success')->sum('amount'),
            'total' => \App\Models\Transaction::count(),
            'success' => \App\Models\Transaction::where('status', 'success')->count(),
            'failed' => \App\Models\Transaction::where('status', 'failed')->count(),
        ];
    @endphp

    <div class="space-y-8">
        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Revenue -->
            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-500">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-900 tracking-tight">GHS
                        {{ number_format($stats['revenue'], 2) }}</h3>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-sky-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-500">Total Transactions</p>
                    <h3 class="text-2xl font-bold text-gray-900 tracking-tight">{{ number_format($stats['total']) }}
                    </h3>
                </div>
            </div>

            <!-- Successful Payments -->
            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-500">Successful Payments</p>
                    <h3 class="text-2xl font-bold text-gray-900 tracking-tight">{{ number_format($stats['success']) }}
                    </h3>
                </div>
            </div>

            <!-- Failed Attempts -->
            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-500">Failed Attempts</p>
                    <h3 class="text-2xl font-bold text-gray-900 tracking-tight">{{ number_format($stats['failed']) }}
                    </h3>
                </div>
            </div>
        </div>

        <!-- Transactions Table Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Recent Transactions</h2>
                    <p class="text-sm text-gray-500">Manage and track all utility payment activities.</p>
                </div>
                <button
                    class="px-4 py-2 bg-gray-900 text-white text-sm font-bold rounded-lg hover:bg-black transition-all">
                    Export Reports
                </button>
            </div>

            @livewire('admin.transaction-table')
        </div>
    </div>
</x-app-layout>