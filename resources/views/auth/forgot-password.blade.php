<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? Enter your email address and we will send you a secure link to choose a new password.') }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                :value="old('email')" required autofocus autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a href="{{ route('login') }}"
                class="text-sm text-gray-600 hover:text-gray-900 underline">
                {{ __('Back to login') }}
            </a>

            <x-primary-button>
                {{ __('Email reset link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
