<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create New Parent
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.parents.store') }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Default password will be 'password'</p>
                            </div>
                            
                            <div>
                                <x-input-label for="phone" :value="__('Phone Number')" />
                                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="address" :value="__('Address')" />
                                <textarea id="address" name="address" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address') }}</textarea>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>
                            
                            <!-- Student Linking Section -->
                            @if($students->count() > 0)
                            <div class="md:col-span-2">
                                <x-input-label :value="__('Link Students')" />
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($students as $student)
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="student_{{ $student->id }}" 
                                                   name="student_ids[]" 
                                                   value="{{ $student->id }}"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <label for="student_{{ $student->id }}" class="ml-2 block text-sm text-gray-700">
                                                {{ $student->user->name }} ({{ $student->admission_no }})
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Select students to link to this parent</p>
                            </div>
                            @else
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500">No students available to link. Create students first.</p>
                            </div>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.parents.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Create Parent') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
