<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Alumni
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.alumni.update', $alumni) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Full Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $alumni->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="email" :value="__('Email Address')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $alumni->email)" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="phone" :value="__('Phone Number')" />
                                <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $alumni->phone)" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="graduation_year" :value="__('Graduation Year')" />
                                <x-text-input id="graduation_year" class="block mt-1 w-full" type="number" name="graduation_year" :value="old('graduation_year', $alumni->graduation_year)" min="1950" max="{{ date('Y') + 5 }}" required />
                                <x-input-error :messages="$errors->get('graduation_year')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="current_occupation" :value="__('Current Occupation')" />
                                <x-text-input id="current_occupation" class="block mt-1 w-full" type="text" name="current_occupation" :value="old('current_occupation', $alumni->current_occupation)" />
                                <x-input-error :messages="$errors->get('current_occupation')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="company" :value="__('Company/Organization')" />
                                <x-text-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $alumni->company)" />
                                <x-input-error :messages="$errors->get('company')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="location" :value="__('Location')" />
                                <x-text-input id="location" class="block mt-1 w-full" type="text" name="location" :value="old('location', $alumni->location)" />
                                <x-input-error :messages="$errors->get('location')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="linkedin_url" :value="__('LinkedIn Profile URL')" />
                                <x-text-input id="linkedin_url" class="block mt-1 w-full" type="url" name="linkedin_url" :value="old('linkedin_url', $alumni->linkedin_url)" />
                                <x-input-error :messages="$errors->get('linkedin_url')" class="mt-2" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-input-label for="bio" :value="__('Biography/Special Achievements')" />
                                <textarea id="bio" name="bio" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('bio', $alumni->bio) }}</textarea>
                                <x-input-error :messages="$errors->get('bio')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Brief biography or notable achievements (max 1000 characters)</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="is_published" type="checkbox" name="is_published" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('is_published', $alumni->is_published) ? 'checked' : '' }}>
                                    <x-input-label for="is_published" :value="__('Publish this alumni profile')" class="ml-2" />
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Published alumni will appear on the public alumni page</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.alumni.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Update Alumni') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
