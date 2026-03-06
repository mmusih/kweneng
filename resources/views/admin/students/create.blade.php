<x-app-layout>
<x-slot name="header">
    <div class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
        <h2 class="font-semibold text-2xl text-white leading-tight">
            Add New Student
        </h2>
    </div>
</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
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
                                <p class="text-sm text-gray-500 mt-1">Note: Student can reset password through login page if needed.</p>
                            </div>
                            
                            <div>
                                <x-input-label for="admission_no" :value="__('Admission Number')" />
                                <x-text-input id="admission_no" class="block mt-1 w-full" type="text" name="admission_no" :value="old('admission_no')" required />
                                <x-input-error :messages="$errors->get('admission_no')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="gender" :value="__('Gender')" />
                                <select id="gender" name="gender" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                </select>
                                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="date_of_birth" :value="__('Date of Birth')" />
                                <x-text-input id="date_of_birth" class="block mt-1 w-full" type="date" name="date_of_birth" :value="old('date_of_birth')" required />
                                <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="current_class_id" :value="__('Current Class')" />
                                <select id="current_class_id" name="current_class_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('current_class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('current_class_id')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="photo" :value="__('Passport Photo')" />
                                <x-text-input id="photo" class="block mt-1 w-full" type="file" name="photo" accept="image/*" />
                                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">JPEG, PNG or WebP. Max 2MB.</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="results_access" type="checkbox" name="results_access" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('results_access', true) ? 'checked' : '' }}>
                                    <x-input-label for="results_access" :value="__('Allow Access to Results')" class="ml-2" />
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="fees_blocked" type="checkbox" name="fees_blocked" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('fees_blocked') ? 'checked' : '' }}>
                                    <x-input-label for="fees_blocked" :value="__('Block Fees Access')" class="ml-2" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.students.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Create Student') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
