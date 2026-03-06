<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Assign Teachers to Subjects
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold">Teacher-Subject Assignments</h3>
                        <p class="text-gray-600">Assign teachers to teach specific subjects in classes for academic years.</p>
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
                        <h4 class="text-lg font-semibold mb-4">Assign Teacher to Subject</h4>
                        <form method="POST" action="{{ route('admin.subjects.assign-teacher') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                <x-input-label for="teacher_id" :value="__('Teacher')" />
                                <select id="teacher_id" name="teacher_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select Teacher</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>
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
                            
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="is_primary" type="checkbox" name="is_primary" value="1" 
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <x-input-label for="is_primary" :value="__('Primary Teacher')" class="ml-2" />
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Check if this is the main teacher for this subject</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <x-primary-button>
                                    {{ __('Assign Teacher to Subject') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Current Assignments -->
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Current Teacher-Subject Assignments</h4>
                        @foreach($classes as $class)
                            <div class="mb-6 border rounded-lg p-4">
                                <h5 class="font-medium text-gray-800">{{ $class->name }}</h5>
                                <div class="mt-2">
                                    @php
                                        $classTeacherSubjects = $class->teacherSubjects()
                                            ->with('teacher.user', 'subject')
                                            ->get()
                                            ->groupBy('subject_id');
                                    @endphp
                                    @if($classTeacherSubjects->count() > 0)
                                        @foreach($classTeacherSubjects as $subjectId => $assignments)
                                            <div class="mb-3">
                                                <div class="font-medium text-gray-700">
                                                    {{ $assignments->first()->subject->name }} ({{ $assignments->first()->subject->code }})
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                                                    @foreach($assignments as $assignment)
                                                        <div class="bg-green-50 p-2 rounded border flex justify-between items-center">
                                                            <div>
                                                                <span class="font-medium">{{ $assignment->teacher->user->name }}</span>
                                                                @if($assignment->is_primary)
                                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                        Primary
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <form action="{{ route('admin.subjects.remove-teacher') }}" method="POST" 
                                                                  onsubmit="return confirm('Remove this teacher from subject?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="teacher_id" value="{{ $assignment->teacher_id }}">
                                                                <input type="hidden" name="subject_id" value="{{ $assignment->subject_id }}">
                                                                <input type="hidden" name="class_id" value="{{ $assignment->class_id }}">
                                                                <input type="hidden" name="academic_year_id" value="{{ $assignment->academic_year_id }}">
                                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                                                    Remove
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-gray-500 text-sm">No teachers assigned to subjects in this class.</p>
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
