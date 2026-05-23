<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Parent Registration</h2>
        <p class="mt-2 text-sm text-gray-600">
            Create your parent account using the code on your child's school login slip.
        </p>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('parent.register') }}" class="space-y-5">
        @csrf

        {{-- Full name --}}
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Phone --}}
        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                :value="old('phone')" required />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        {{-- Address --}}
        <div>
            <x-input-label for="address" :value="__('Home Address')" />
            <textarea id="address" name="address" rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                required>{{ old('address') }}</textarea>
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        {{-- Relationship to child --}}
        <div>
            <x-input-label for="relationship" :value="__('Relationship to Child')" />
            <select id="relationship" name="relationship"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                required>
                <option value="">— Select —</option>
                <option value="father"   {{ old('relationship') === 'father'   ? 'selected' : '' }}>Father</option>
                <option value="mother"   {{ old('relationship') === 'mother'   ? 'selected' : '' }}>Mother</option>
                <option value="guardian" {{ old('relationship') === 'guardian' ? 'selected' : '' }}>Guardian</option>
                <option value="other"    {{ old('relationship') === 'other'    ? 'selected' : '' }}>Other</option>
            </select>
            <x-input-error :messages="$errors->get('relationship')" class="mt-2" />
        </div>

        {{-- Parent invite code --}}
        <div>
            <x-input-label for="invite_code" :value="__('Parent Code')" />
            <x-text-input id="invite_code" name="invite_code" type="text"
                class="mt-1 block w-full tracking-widest uppercase font-mono"
                :value="old('invite_code')"
                maxlength="10"
                placeholder="e.g. K3X7PM2WQN"
                required />
            <p class="mt-1 text-xs text-gray-500">
                This 10-character code is printed on your child's school login slip. It is valid for 48 hours.
            </p>
            <x-input-error :messages="$errors->get('invite_code')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Choose a Password')" />
            <x-text-input id="password" name="password" type="password"
                class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Confirm password --}}
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                &larr; Back to login
            </a>
            <x-primary-button>
                {{ __('Create Account') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
