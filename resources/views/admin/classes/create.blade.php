<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create New Class
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.classes.store') }}">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Class Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Example: Form 1A, Grade 10B, etc.</p>
                            </div>
                            
                            <div>
                                <x-input-label for="level" :value="__('Level')" />
                                <x-text-input id="level" class="block mt-1 w-full" type="number" name="level" :value="old('level')" min="1" max="12" required />
                                <x-input-error :messages="$errors->get('level')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Grade level (1-12)</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.classes.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Create Class') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
