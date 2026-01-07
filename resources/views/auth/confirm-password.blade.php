<x-guest-layout>
    <div class="mb-8 text-center sm:text-left">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Confirm Password</h1>
        <p class="text-gray-500 text-sm">This is a secure area of the application. Please confirm your password before
            continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
        @csrf

        <!-- Password -->
        <div class="space-y-2">
            <label for="password" class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                {{ __('Password') }}
            </label>
            <div class="input-group relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    placeholder="••••••••"
                    class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-[8px] text-gray-900 placeholder-gray-400 transition-all focus:bg-white">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit"
            class="w-full py-4 bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white font-bold rounded-[8px] shadow-sm shadow-sky-200 transition-all transform hover:translate-y-[-2px] active:translate-y-[0px] disabled:opacity-50 disabled:cursor-not-allowed">
            {{ __('Confirm') }}
        </button>
    </form>
</x-guest-layout>