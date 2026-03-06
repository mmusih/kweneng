<x-app-layout>
<x-slot name="header">
    <div class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
        <h2 class="font-semibold text-2xl text-white leading-tight">
            Student Dashboard
        </h2>
    </div>
</x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}!</h3>
                        <p class="text-gray-600">Access your academic information and results here.</p>
                    </div>
                    
                    <!-- Academic Structure Section -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Current Class Card -->
                        <div class="bg-blue-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-blue-800">Current Class</h4>
                            @if(auth()->user()->student && auth()->user()->student->currentClass)
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ auth()->user()->student->currentClass->name }}
                                </p>
                                <p class="text-sm text-blue-700 mt-1">
                                    {{ auth()->user()->student->currentClass->academicYear->year_name ?? 'N/A' }}
                                </p>
                            @else
                                <p class="text-lg text-blue-600">Not Assigned</p>
                            @endif
                        </div>
                        
                        <!-- Academic Year Card -->
                        <div class="bg-green-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-green-800">Academic Year</h4>
                            @php
                                $currentAcademicYear = App\Models\AcademicYear::where('active', true)->first();
                            @endphp
                            @if($currentAcademicYear)
                                <p class="text-2xl font-bold text-green-600">
                                    {{ $currentAcademicYear->year_name }}
                                </p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @switch($currentAcademicYear->status)
                                        @case('open')
                                            bg-green-200 text-green-800
                                            @break
                                        @case('locked')
                                            bg-yellow-200 text-yellow-800
                                            @break
                                        @case('closed')
                                            bg-red-200 text-red-800
                                            @break
                                    @endswitch">
                                    {{ ucfirst($currentAcademicYear->status) }}
                                </span>
                            @else
                                <p class="text-lg text-green-600">Not Set</p>
                            @endif
                        </div>
                        
                        <!-- Current Term Card -->
                        <div class="bg-purple-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-purple-800">Current Term</h4>
                            @php
                                $currentTerm = null;
                                if ($currentAcademicYear) {
                                    $currentTerm = App\Models\Term::where('academic_year_id', $currentAcademicYear->id)
                                        ->where('status', 'active')
                                        ->first();
                                }
                            @endphp
                            @if($currentTerm)
                                <p class="text-2xl font-bold text-purple-600">
                                    {{ $currentTerm->name }}
                                </p>
                                <p class="text-sm text-purple-700 mt-1">
                                    {{ $currentTerm->start_date->format('M j') }} - {{ $currentTerm->end_date->format('M j, Y') }}
                                </p>
                                @if($currentTerm->locked)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800 mt-1">
                                        Locked
                                    </span>
                                @endif
                            @else
                                <p class="text-lg text-purple-600">No Active Term</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Access Status Section -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Access Status</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h5 class="font-medium text-gray-700">Academic Access</h5>
                                <div class="mt-2 space-y-2">
                                    @if(auth()->user()->student)
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full {{ auth()->user()->student->results_access ? 'bg-green-500' : 'bg-red-500' }} mr-2"></div>
                                            <span class="text-sm">
                                                {{ auth()->user()->student->results_access ? 'Results Access Enabled' : 'Results Access Blocked' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full {{ auth()->user()->student->fees_blocked ? 'bg-red-500' : 'bg-green-500' }} mr-2"></div>
                                            <span class="text-sm">
                                                {{ auth()->user()->student->fees_blocked ? 'Fees Access Blocked' : 'Fees Access Enabled' }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h5 class="font-medium text-gray-700">Academic Progress</h5>
                                <p class="text-sm text-gray-600 mt-2">Results and attendance data will appear here once available.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subjects Section -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Your Subjects</h4>
                        @php
                            $currentClass = auth()->user()->student->currentClass ?? null;
                            $currentYear = App\Models\AcademicYear::where('active', true)->first();
                            $classSubjects = collect();
                            
                            if ($currentClass && $currentYear) {
                                $classSubjects = $currentClass->classSubjects()
                                    ->where('academic_year_id', $currentYear->id)
                                    ->with('subject', 'teacherSubjects.teacher.user')
                                    ->get();
                            }
                        @endphp
                        
                        @if($classSubjects->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($classSubjects as $classSubject)
                                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h5 class="font-medium text-indigo-700">
                                                    {{ $classSubject->subject->name }}
                                                </h5>
                                                <p class="text-sm text-gray-600">
                                                    Code: {{ $classSubject->subject->code }}
                                                </p>
                                                <div class="mt-2 text-xs text-gray-500">
                                                    <div>Max Marks: {{ $classSubject->max_marks }}</div>
                                                    <div>Passing Marks: {{ $classSubject->passing_marks }}</div>
                                                </div>
                                            </div>
                                            <div>
                                                @if($classSubject->subject->is_core)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Core
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Elective
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Show assigned teacher if exists -->
                                        @php
                                            $primaryTeacher = $classSubject->teacherSubjects()
                                                ->where('is_primary', true)
                                                ->with('teacher.user')
                                                ->first();
                                        @endphp
                                        @if($primaryTeacher)
                                            <div class="mt-3 text-sm">
                                                <span class="text-gray-600">Teacher:</span>
                                                <span class="font-medium">{{ $primaryTeacher->teacher->user->name }}</span>
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Primary
                                                </span>
                                            </div>
                                        @elseif($classSubject->teacherSubjects->count() > 0)
                                            <div class="mt-3 text-sm">
                                                <span class="text-gray-600">Teachers:</span>
                                                @foreach($classSubject->teacherSubjects->take(2) as $teacherSubject)
                                                    <span class="font-medium">{{ $teacherSubject->teacher->user->name }}</span>
                                                    @if(!$loop->last) , @endif
                                                @endforeach
                                                @if($classSubject->teacherSubjects->count() > 2)
                                                    <span class="text-gray-500">+{{ $classSubject->teacherSubjects->count() - 2 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="mt-3 text-sm text-gray-500">
                                                No teachers assigned yet
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No subjects assigned</h3>
                                <p class="mt-1 text-sm text-gray-500">Your subjects for this academic year will appear here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
