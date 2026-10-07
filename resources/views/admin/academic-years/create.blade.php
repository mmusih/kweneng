<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Academic Year
        </h2>
    </x-slot>

    <div class="kw-page-header py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.academic-years.store') }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="year_name" :value="__('Academic Year')" />
                                <x-text-input id="year_name" class="block mt-1 w-full" type="text" name="year_name" :value="old('year_name')" inputmode="numeric" pattern="[1-9][0-9]{3}" maxlength="4" placeholder="2026" required autofocus />
                                <x-input-error :messages="$errors->get('year_name')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Enter a single year, for example 2026. The school year runs from January to December of the same year.</p>
                            </div>
                            
                            <div>
                                <x-input-label for="active" :value="__('Set as Active')" />
                                <div class="mt-1">
                                    <input id="active" type="checkbox" name="active" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('active') ? 'checked' : '' }}>
                                    <x-input-label for="active" :value="__('Make this the active academic year')" class="ml-2" />
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Only one academic year can be active at a time</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.academic-years.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Create Academic Year') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
