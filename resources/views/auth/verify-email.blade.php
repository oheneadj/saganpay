<x-guest-layout>
    <div class="mb-8 text-center sm:text-left">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Verify Email</h1>
        <p class="text-gray-500 text-sm">Thanks for signing up! Before getting started, please verify your email address
            by clicking on the link we just emailed to you.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-[8px] text-emerald-700 text-sm font-medium">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="space-y-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit"
                class="w-full py-4 bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white font-bold rounded-[8px] shadow-sm shadow-sky-200 transition-all transform hover:translate-y-[-2px] active:translate-y-[0px] disabled:opacity-50 disabled:cursor-not-allowed">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf

            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 font-medium underline">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>