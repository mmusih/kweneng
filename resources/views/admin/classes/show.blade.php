<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Class Details: {{ $class->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Class Basic Information -->
                    <div class="border-b border-gray-200 pb-5 mb-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $class->name }}</h3>
                                <p class="text-gray-600 mt-1">
                                    Level: {{ $class->level }} | 
                                    Academic Year: {{ $class->academicYear->year_name ?? 'Not Assigned' }}
                                </p>
                            </div>
                            <div class="mt-4 md:mt-0">
                                <a href="{{ route('admin.classes.edit', $class) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Edit Class
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Class Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">
                                {{ $class->students()->count() }}
                            </div>
                            <div class="text-sm text-gray-600">Students Enrolled</div>
                        </div>
                        
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">
                                {{ $class->classSubjects()->count() }}
                            </div>
                            <div class="text-sm text-gray-600">Subjects Assigned</div>
                        </div>
                        
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ $class->teacherSubjects()->distinct('teacher_id')->count() }}
                            </div>
                            <div class="text-sm text-gray-600">Teachers Assigned</div>
                        </div>
                        
                        <div class="border rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ $class->historyRecords()->count() }}
                            </div>
                            <div class="text-sm text-gray-600">Enrollment Records</div>
                        </div>
                    </div>

                    <!-- Students in Class -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Students in Class</h4>
                            <a href="{{ route('admin.students.create') }}?class_id={{ $class->id }}" 
                               class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add Student
                            </a>
                        </div>
                        
                        @if($class->students->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admission No</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date of Birth</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($class->students as $student)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        @if($student->photo)
                                                            <img class="h-10 w-10 rounded-full" src="{{ Storage::url($student->photo) }}" alt="{{ $student->user->name }}">
                                                        @else
                                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                <span class="text-gray-500">{{ substr($student->user->name, 0, 1) }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">{{ $student->user->name }}</div>
                                                            <div class="text-sm text-gray-500">{{ $student->user->email }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $student->admission_no }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                    {{ $student->gender }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $student->date_of_birth->format('M j, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="{{ route('admin.students.show', $student) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                        View
                                                    </a>
                                                    <a href="{{ route('admin.students.edit', $student) }}" 
                                                       class="text-blue-600 hover:text-blue-900">
                                                        Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No students enrolled</h3>
                                <p class="mt-1 text-sm text-gray-500">This class currently has no students assigned.</p>
                                <div class="mt-6">
                                    <a href="{{ route('admin.students.create') }}?class_id={{ $class->id }}" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Add Student
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Class Subjects Section -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Class Subjects</h4>
                            <div class="space-x-2">
                                <a href="{{ route('admin.subjects.manage-classes') }}?class_id={{ $class->id }}" 
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Manage Subjects
                                </a>
                                <a href="{{ route('admin.subjects.manage-teachers') }}?class_id={{ $class->id }}" 
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Assign Teachers
                                </a>
                            </div>
                        </div>
                        
                        @php
                            $currentYear = App\Models\AcademicYear::where('active', true)->first();
                            $classSubjects = $class->classSubjects()
                                ->where('academic_year_id', $currentYear->id ?? null)
                                ->with('subject', 'teacherSubjects.teacher.user')
                                ->get();
                        @endphp
                        
                        @if($classSubjects->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teachers</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($classSubjects as $classSubject)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $classSubject->subject->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $classSubject->subject->code }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if($classSubject->subject->is_core)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            Core
                                                        </span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                            Elective
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    Max: {{ $classSubject->max_marks }}<br>
                                                    Pass: {{ $classSubject->passing_marks }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    @if($classSubject->teacherSubjects->count() > 0)
                                                        @foreach($classSubject->teacherSubjects as $teacherSubject)
                                                            <div class="mb-1">
                                                                {{ $teacherSubject->teacher->user->name }}
                                                                @if($teacherSubject->is_primary)
                                                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                        Primary
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <span class="text-gray-400">No teachers assigned</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <form action="{{ route('admin.subjects.remove-class') }}" method="POST" 
                                                          onsubmit="return confirm('Remove this subject from class? This will also remove teacher assignments.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                                                        <input type="hidden" name="subject_id" value="{{ $classSubject->subject->id }}">
                                                        <input type="hidden" name="academic_year_id" value="{{ $classSubject->academic_year_id }}">
                                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-6 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No subjects assigned</h3>
                                <p class="mt-1 text-sm text-gray-500">This class has no subjects assigned for the current academic year.</p>
                                <div class="mt-4">
                                    <a href="{{ route('admin.subjects.manage-classes') }}?class_id={{ $class->id }}" 
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Assign Subjects
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Academic History -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Class Academic History</h4>
                        @if($class->historyRecords->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exited</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($class->historyRecords->sortByDesc('enrolled_at')->take(10) as $history)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $history->student->user->name ?? 'Unknown Student' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $history->academicYear->year_name ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $history->enrolled_at ? $history->enrolled_at->format('M j, Y') : 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $history->exited_at ? $history->exited_at->format('M j, Y') : 'Active' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        @switch($history->status)
                                                            @case('active')
                                                                bg-green-100 text-green-800
                                                                @break
                                                            @case('promoted')
                                                                bg-blue-100 text-blue-800
                                                                @break
                                                            @case('repeated')
                                                                bg-yellow-100 text-yellow-800
                                                                @break
                                                            @case('transferred')
                                                                bg-purple-100 text-purple-800
                                                                @break
                                                            @default
                                                                bg-gray-100 text-gray-800
                                                        @endswitch">
                                                        {{ ucfirst($history->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($class->historyRecords->count() > 10)
                                <div class="mt-2 text-sm text-gray-500 text-center">
                                    Showing 10 of {{ $class->historyRecords->count() }} records. 
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">View all</a>
                                </div>
                            @endif
                        @else
                            <div class="bg-gray-50 rounded-lg p-6 text-center">
                                <p class="text-gray-500">No academic history records found for this class.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
