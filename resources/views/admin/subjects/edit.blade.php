<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Subject
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Subject Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $subject->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="code" :value="__('Subject Code')" />
                                <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" :value="old('code', $subject->code)" required />
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Unique identifier (e.g., MATH101, ENG202)</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Description')" />
                                <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $subject->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="display_order" :value="__('Display Order')" />
                                <x-text-input id="display_order" class="block mt-1 w-full" type="number" name="display_order" :value="old('display_order', $subject->display_order)" min="0" />
                                <x-input-error :messages="$errors->get('display_order')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">Lower numbers appear first</p>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input id="is_core" type="checkbox" name="is_core" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('is_core', $subject->is_core) ? 'checked' : '' }}>
                                    <x-input-label for="is_core" :value="__('Core Subject')" class="ml-2" />
                                </div>
                                
                                <div class="flex items-center">
                                    <input id="is_active" type="checkbox" name="is_active" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                                    <x-input-label for="is_active" :value="__('Active')" class="ml-2" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.subjects.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Update Subject') }}
                            </x-primary-button>
                        </div>
                    </form>
                    
                    <!-- Delete Form (only if no assignments exist) -->
                    @if($subject->classSubjects()->count() === 0)
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Danger Zone</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Deleting this subject will permanently remove it from the system.
                            </p>
                            
                            <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                
                                <button type="button" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        onclick="confirmDelete(this.form)">
                                    {{ __('Delete Subject') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Cannot Delete</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                This subject is currently assigned to {{ $subject->classSubjects()->count() }} classes and cannot be deleted.
                                Remove all class assignments first to enable deletion.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function confirmDelete(form) {
            if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                form.submit();
            }
        }
    </script>
    @endpush
</x-app-layout>
