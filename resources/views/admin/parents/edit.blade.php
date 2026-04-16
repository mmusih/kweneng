<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Edit Parent
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.parents.update', $parent) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name', $parent->user->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                                    :value="old('email', $parent->user->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">
                                    Use the reset password action if the parent needs a new temporary password. The
                                    parent will be required to change it on first login.
                                </p>
                            </div>

                            <div>
                                <x-input-label for="phone" :value="__('Phone Number')" />
                                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone"
                                    :value="old('phone', $parent->phone)" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="address" :value="__('Address')" />
                                <textarea id="address" name="address" rows="3"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address', $parent->address) }}</textarea>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>

                            @if ($students->count() > 0)
                                <div class="md:col-span-2">
                                    <x-input-label :value="__('Link Students')" />
                                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($students as $student)
                                            <div class="flex items-center">
                                                <input type="checkbox" id="student_{{ $student->id }}"
                                                    name="student_ids[]" value="{{ $student->id }}"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                    {{ in_array($student->id, old('student_ids', $parent->students->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                <label for="student_{{ $student->id }}"
                                                    class="ml-2 block text-sm text-gray-700">
                                                    {{ $student->user->name }} ({{ $student->admission_no }})
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Select students to link to this parent</p>
                                </div>
                            @else
                                <div class="md:col-span-2">
                                    <p class="text-sm text-gray-500">No students available to link. Create students
                                        first.</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-3">
                            <form action="{{ route('admin.parents.reset-password', $parent) }}" method="POST"
                                onsubmit="return confirm('Reset password for this parent?');">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-md shadow-sm transition">
                                    Reset Password
                                </button>
                            </form>

                            <a href="{{ route('admin.parents.index') }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>

                            <x-primary-button>
                                {{ __('Update Parent') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
