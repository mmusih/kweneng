<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Subject
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.subjects.store') }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Subject Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="code" :value="__('Subject Code')" />
                                <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code')" required />
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Unique identifier (e.g., MATH101, ENG202)</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="display_order" :value="__('Display Order')" />
                                <x-text-input id="display_order" class="block mt-1 w-full" type="number" name="display_order" :value="old('display_order', 0)" min="0" />
                                <x-input-error :messages="$errors->get('display_order')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Lower numbers appear first</p>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input id="is_core" type="checkbox" name="is_core" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('is_core') ? 'checked' : '' }}>
                                    <x-input-label for="is_core" :value="__('Core Subject')" class="ml-2" />
                                </div>
                                
                                <div class="flex items-center">
                                    <input id="is_active" type="checkbox" name="is_active" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <x-input-label for="is_active" :value="__('Active')" class="ml-2" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.subjects.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Create Subject') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
