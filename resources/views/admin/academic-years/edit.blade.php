<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Academic Year
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.academic-years.update', $academicYear) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="year_name" :value="__('Academic Year Name')" />
                                <x-text-input id="year_name" class="block mt-1 w-full" type="text" name="year_name" :value="old('year_name', $academicYear->year_name)" required autofocus />
                                <x-input-error :messages="$errors->get('year_name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="open" {{ old('status', $academicYear->status) == 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="closed" {{ old('status', $academicYear->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                                    <option value="locked" {{ old('status', $academicYear->status) == 'locked' ? 'selected' : '' }}>Locked</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="active" :value="__('Active')" />
                                <div class="mt-1">
                                    <input id="active" type="checkbox" name="active" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('active', $academicYear->active) ? 'checked' : '' }}>
                                    <x-input-label for="active" :value="__('Set as Active Academic Year')" class="ml-2" />
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Only one academic year can be active at a time</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.academic-years.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Update Academic Year') }}
                            </x-primary-button>
                        </div>
                    </form>
                    
                    <!-- Danger Zone -->
                    @if($academicYear->status !== 'locked')
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Danger Zone</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Deleting this academic year will permanently remove it from the system.
                            </p>
                            
                            <form method="POST" action="{{ route('admin.academic-years.destroy', $academicYear) }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        onclick="return confirm('Are you sure you want to delete this academic year? This action cannot be undone.')">
                                    {{ __('Delete Academic Year') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Cannot Delete</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                This academic year is locked and cannot be deleted.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
