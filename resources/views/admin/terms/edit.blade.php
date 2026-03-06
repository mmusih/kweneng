<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Edit Term
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.terms.update', $term) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Academic Year</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $term->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="name" :value="__('Term Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $term->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="start_date" :value="__('Start Date')" />
                                <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date" :value="old('start_date', $term->start_date->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="end_date" :value="__('End Date')" />
                                <x-text-input id="end_date" class="block mt-1 w-full" type="date" name="end_date" :value="old('end_date', $term->end_date->format('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="active" {{ old('status', $term->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="finalized" {{ old('status', $term->status) == 'finalized' ? 'selected' : '' }}>Finalized</option>
                                    <option value="locked" {{ old('status', $term->status) == 'locked' ? 'selected' : '' }}>Locked</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.terms.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Update Term') }}
                            </x-primary-button>
                        </div>
                    </form>
                    
                    <!-- Action Buttons Based on Status -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Term Actions</h3>
                        
                        @if($term->status === 'active')
                            <div class="space-y-3">
                                <form action="{{ route('admin.terms.finalize', $term) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                                            onclick="return confirm('Finalize this term? Students will no longer be able to submit assignments.')">
                                        Finalize Term
                                    </button>
                                </form>
                                
                                <form action="{{ route('admin.terms.lock', $term) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                            onclick="return confirm('Lock this term? This action cannot be undone.')">
                                        Lock Term
                                    </button>
                                </form>
                            </div>
                        @elseif($term->status === 'finalized')
                            <form action="{{ route('admin.terms.lock', $term) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                        onclick="return confirm('Lock this term? This action cannot be undone.')">
                                    Lock Term
                                </button>
                            </form>
                        @elseif($term->status === 'locked')
                            <p class="text-sm text-gray-500">This term is locked and cannot be modified.</p>
                        @endif
                    </div>
                    
                    <!-- Danger Zone -->
                    @if($term->status !== 'locked')
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Danger Zone</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Deleting this term will permanently remove it from the system.
                            </p>
                            
                            <form method="POST" action="{{ route('admin.terms.destroy', $term) }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        onclick="return confirm('Are you sure you want to delete this term? This action cannot be undone.')">
                                    {{ __('Delete Term') }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Cannot Delete</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                This term is locked and cannot be deleted.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
