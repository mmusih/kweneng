<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Assign Subjects to Classes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold">Subject-Class Assignments</h3>
                        <p class="text-gray-600">Assign subjects to classes for specific academic years.</p>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Assignment Form -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                        <h4 class="text-lg font-semibold mb-4">Assign Subject to Class</h4>
                        <form method="POST" action="{{ route('admin.subjects.assign-class') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf
                            
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Academic Year</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}">{{ $year->year_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="subject_id" :value="__('Subject')" />
                                <select id="subject_id" name="subject_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <x-input-label for="max_marks" :value="__('Maximum Marks')" />
                                <x-text-input id="max_marks" name="max_marks" type="number" step="0.01" value="100" class="block mt-1 w-full" />
                            </div>
                            
                            <div>
                                <x-input-label for="passing_marks" :value="__('Passing Marks')" />
                                <x-text-input id="passing_marks" name="passing_marks" type="number" value="40" class="block mt-1 w-full" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-primary-button>
                                    {{ __('Assign Subject to Class') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Assignments -->
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Current Subject-Class Assignments</h4>
                        @foreach($classes as $class)
                            <div class="mb-6 border rounded-lg p-4">
                                <h5 class="font-medium text-gray-800">{{ $class->name }} ({{ $class->academicYear->year_name ?? 'No Year' }})</h5>
                                <div class="mt-2">
                                    @php
                                        $classSubjects = $class->classSubjects()->with('subject')->get();
                                    @endphp
                                    @if($classSubjects->count() > 0)
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
                                            @foreach($classSubjects as $classSubject)
                                                <div class="bg-blue-50 p-3 rounded border flex justify-between items-center">
                                                    <div>
                                                        <span class="font-medium">{{ $classSubject->subject->code }}</span>
                                                        <span class="text-sm text-gray-600 ml-1">({{ $classSubject->subject->name }})</span>
                                                        <div class="text-xs text-gray-500">
                                                            Max: {{ $classSubject->max_marks }}, Pass: {{ $classSubject->passing_marks }}
                                                        </div>
                                                    </div>
                                                    <form action="{{ route('admin.subjects.remove-class') }}" method="POST" 
                                                          onsubmit="return confirm('Remove this subject from class?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                                                        <input type="hidden" name="subject_id" value="{{ $classSubject->subject->id }}">
                                                        <input type="hidden" name="academic_year_id" value="{{ $classSubject->academic_year_id }}">
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-500 text-sm">No subjects assigned to this class.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
