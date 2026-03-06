<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <h2 class="font-bold text-2xl text-center text-gray-800 leading-tight">
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
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                            @if(isset($currentAcademicYear))
                                <p class="text-2xl font-bold text-green-600">
                                    {{ $currentAcademicYear->year_name }}
                                </p>
                                @if($currentAcademicYear->active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-800">
                                        Active
                                    </span>
                                @endif
                            @else
                                <p class="text-lg text-green-600">Not Set</p>
                            @endif
                        </div>
                        
                        <!-- Current Term Card -->
                        <div class="bg-purple-100 p-6 rounded-lg shadow">
                            <h4 class="text-lg font-semibold text-purple-800">Current Term</h4>
                            @if(isset($currentTerm) && $currentTerm)
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
                    
                    <!-- Marks Overview Section -->
                    @php
                        $student = auth()->user()?->student;
                        $currentTerm = cache()->remember('current_active_term_' . now()->timestamp, 3600, function () {
                            return \App\Models\Term::where('status', 'active')->first();
                        });
                        
                        $studentSubjects = collect();
                        $marks = collect();
                        
                        if ($student && $currentTerm) {
                            $cacheKey = "student_{$student->id}_term_{$currentTerm->id}_subjects_" . now()->format('Y-m-d');
                            $studentSubjects = cache()->remember($cacheKey, 1800, function () use ($student, $currentTerm) {
                                return \App\Models\StudentSubject::where('student_id', $student->id)
                                    ->where('academic_year_id', $currentTerm->academic_year_id)
                                    ->with('subject')
                                    ->get();
                            });
                            
                            $marksCacheKey = "student_{$student->id}_term_{$currentTerm->id}_marks_" . now()->format('Y-m-d');
                            $marks = cache()->remember($marksCacheKey, 1800, function () use ($student, $currentTerm) {
                                return \App\Models\Mark::where('student_id', $student->id)
                                    ->where('term_id', $currentTerm->id)
                                    ->with('subject')
                                    ->get();
                            })->keyBy('subject_id');
                        }
                    @endphp
                    
                    <div class="mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Your Marks Overview</h4>
                            <a href="{{ route('student.marks.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                View Detailed Marks →
                            </a>
                        </div>
                        
                        @if($student)
                            @if($currentTerm)
                                @if($studentSubjects->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($studentSubjects as $studentSubject)
                                            @php
                                                $mark = $marks->get($studentSubject->subject_id);
                                                $average = null;
                                                $grade = null;
                                                
                                                if ($mark) {
                                                    if ($mark->midterm_score !== null && $mark->endterm_score !== null) {
                                                        $average = ($mark->midterm_score + $mark->endterm_score) / 2;
                                                    } elseif ($mark->midterm_score !== null) {
                                                        $average = $mark->midterm_score;
                                                    } elseif ($mark->endterm_score !== null) {
                                                        $average = $mark->endterm_score;
                                                    }
                                                    $grade = $mark->grade;
                                                }
                                            @endphp
                                            
                                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h5 class="font-medium text-gray-800">
                                                            {{ $studentSubject->subject->name ?? 'Unknown Subject' }}
                                                        </h5>
                                                        <p class="text-sm text-gray-600">
                                                            {{ $studentSubject->subject->code ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                    @if($studentSubject->is_elective)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            Elective
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                @if($mark)
                                                    <div class="mt-3">
                                                        <div class="flex justify-between text-sm">
                                                            <span class="text-gray-600">Midterm:</span>
                                                            <span class="font-medium">{{ $mark->midterm_score ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="flex justify-between text-sm mt-1">
                                                            <span class="text-gray-600">Endterm:</span>
                                                            <span class="font-medium">{{ $mark->endterm_score ?? 'N/A' }}</span>
                                                        </div>
                                                        @if($average !== null)
                                                            <div class="flex justify-between text-sm mt-1">
                                                                <span class="text-gray-600">Average:</span>
                                                                <span class="font-medium">{{ number_format($average, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        @if($grade)
                                                            <div class="flex justify-between text-sm mt-1">
                                                                <span class="text-gray-600">Grade:</span>
                                                                <span class="font-medium">
                                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                        @switch($grade)
                                                                            @case('A*') bg-green-100 text-green-800 @break
                                                                            @case('A') bg-green-100 text-green-800 @break
                                                                            @case('B') bg-blue-100 text-blue-800 @break
                                                                            @case('C') bg-yellow-100 text-yellow-800 @break
                                                                            @case('D') bg-orange-100 text-orange-800 @break
                                                                            @case('E') bg-red-100 text-red-800 @break
                                                                            @case('F') bg-red-100 text-red-800 @break
                                                                            @default bg-gray-100 text-gray-800
                                                                        @endswitch">
                                                                        {{ $grade }}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="mt-3 text-center py-2">
                                                        <p class="text-sm text-gray-500">No marks entered yet</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No subjects assigned</h3>
                                        <p class="mt-1 text-sm text-gray-500">You haven't been assigned to any subjects for this term.</p>
                                    </div>
                                @endif
                            @else
                                <div class="bg-blue-50 rounded-lg p-6">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-blue-800">Marks Information</h3>
                                            <div class="mt-2 text-sm text-blue-700">
                                                <p>There is no active term currently. Marks will be displayed here once a term is active and marks are entered.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="bg-yellow-50 rounded-lg p-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Student Record Required</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p>Your student record could not be found. Please contact the school administration.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Academic Performance Summary -->
                    @if($currentTerm && $student)
                        @php
                            // Calculate averages for the current term
                            $marksForAvg = \App\Models\Mark::where('student_id', $student->id)
                                ->where('term_id', $currentTerm->id)
                                ->get();
                            
                            $midtermScores = $marksForAvg->pluck('midterm_score')->filter(fn($score) => $score !== null);
                            $endtermScores = $marksForAvg->pluck('endterm_score')->filter(fn($score) => $score !== null);
                            
                            $midtermAverage = $midtermScores->isNotEmpty() ? $midtermScores->avg() : null;
                            $endtermAverage = $endtermScores->isNotEmpty() ? $endtermScores->avg() : null;
                        @endphp
                        
                        @if($midtermAverage || $endtermAverage)
                            <div class="mt-8">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">Performance Summary</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @if($midtermAverage)
                                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-100">
                                            <div class="flex items-center">
                                                <div class="rounded-full bg-blue-500 p-3">
                                                    <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h5 class="text-lg font-medium text-gray-900">Midterm Average</h5>
                                                    <p class="text-2xl font-bold text-blue-600">{{ number_format($midtermAverage, 2) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($endtermAverage)
                                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border border-green-100">
                                            <div class="flex items-center">
                                                <div class="rounded-full bg-green-500 p-3">
                                                    <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <h5 class="text-lg font-medium text-gray-900">Endterm Average</h5>
                                                    <p class="text-2xl font-bold text-green-600">{{ number_format($endtermAverage, 2) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                    
                    <!-- Additional Information Section -->
                    <div class="mt-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Quick Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h5 class="font-medium text-gray-700">Access Status</h5>
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
