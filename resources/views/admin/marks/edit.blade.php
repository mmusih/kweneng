<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Mark
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Edit Mark</h3>
                        <p class="text-gray-600">Modify student mark details.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.marks.update', $mark) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="student_name" :value="__('Student')" />
                                <x-text-input id="student_name" class="block mt-1 w-full" type="text" 
                                             :value="$mark->student->user->name" disabled />
                            </div>
                            
                            <div>
                                <x-input-label for="subject_name" :value="__('Subject')" />
                                <x-text-input id="subject_name" class="block mt-1 w-full" type="text" 
                                             :value="$mark->subject->name . ' (' . $mark->subject->code . ')'" disabled />
                            </div>
                            
                            <div>
                                <x-input-label for="class_name" :value="__('Class')" />
                                <x-text-input id="class_name" class="block mt-1 w-full" type="text" 
                                             :value="$mark->class->name" disabled />
                            </div>
                            
                            <div>
                                <x-input-label for="term_name" :value="__('Term')" />
                                <x-text-input id="term_name" class="block mt-1 w-full" type="text" 
                                             :value="$mark->term->name" disabled />
                            </div>
                            
                            <div>
                                <x-input-label for="midterm_score" :value="__('Midterm Score (0-100)')" />
                                <x-text-input id="midterm_score" class="block mt-1 w-full" type="number" 
                                             name="midterm_score" :value="old('midterm_score', $mark->midterm_score)" 
                                             min="0" max="100" step="0.01" />
                                <x-input-error :messages="$errors->get('midterm_score')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="endterm_score" :value="__('Endterm Score (0-100)')" />
                                <x-text-input id="endterm_score" class="block mt-1 w-full" type="number" 
                                             name="endterm_score" :value="old('endterm_score', $mark->endterm_score)" 
                                             min="0" max="100" step="0.01" />
                                <x-input-error :messages="$errors->get('endterm_score')" class="mt-2" />
                            </div>
                            
                            <div>
                                <x-input-label for="grade" :value="__('Grade')" />
                                <x-text-input id="grade" class="block mt-1 w-full" type="text" 
                                             name="grade" :value="old('grade', $mark->grade)" maxlength="2" />
                                <x-input-error :messages="$errors->get('grade')" class="mt-2" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-input-label for="remarks" :value="__('Remarks')" />
                                <textarea id="remarks" name="remarks" rows="3" 
                                         class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('remarks', $mark->remarks) }}</textarea>
                                <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.marks.index') }}" class="mr-4 text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            
                            <x-primary-button>
                                {{ __('Update Mark') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
