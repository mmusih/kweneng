<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Academic Staff
        </h2>
    </x-slot>

    <div class="kw-page-header py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.teachers.store') }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input
                                    id="name"
                                    class="block mt-1 w-full"
                                    type="text"
                                    name="name"
                                    :value="old('name')"
                                    required
                                    autofocus
                                />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input
                                    id="email"
                                    class="block mt-1 w-full"
                                    type="email"
                                    name="email"
                                    :value="old('email')"
                                    required
                                />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Default password will be 'password'</p>
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="role" :value="__('Role')" />
                                <select
                                    id="role"
                                    name="role"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select role</option>
                                    <option value="teacher" {{ old('role') === 'teacher' ? 'selected' : '' }}>
                                        Teacher
                                    </option>
                                    <option value="headmaster" {{ old('role') === 'headmaster' ? 'selected' : '' }}>
                                        Headmaster
                                    </option>
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">
                                    Headmaster users will also be created in the teachers table.
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.teachers.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Create Staff Member') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>