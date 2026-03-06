<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Teacher Dashboard
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}!</h3>
                        <p class="text-gray-600">Manage your classes and subjects.</p>
                    </div>
                    
                    <!-- Enter Marks Module -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Enter Marks</h4>
                            <a href="{{ route('teacher.marks.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                View All Marks →
                            </a>
                        </div>
                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-6 border border-indigo-100">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h5 class="text-lg font-medium text-gray-900">Ready to Enter Marks?</h5>
                                    <p class="mt-1 text-gray-600">
                                        Enter midterm and endterm scores for your students. 
                                        Select a class and subject to get started.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('teacher.marks.index') }}" 
                                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Enter Marks Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subject Assignments -->
                    <div class="mb-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Your Subject Assignments</h4>
                        @php
                            $teacherSubjects = auth()->user()->teacher->teacherSubjects()
                                ->with('subject', 'class', 'academicYear')
                                ->orderBy('academic_year_id', 'desc')
                                ->get()
                                ->groupBy('academic_year_id');
                        @endphp
                        
                        @if($teacherSubjects->count() > 0)
                            @foreach($teacherSubjects as $yearId => $assignments)
                                <div class="mb-6">
                                    <h5 class="font-medium text-gray-700 mb-2">
                                        {{ $assignments->first()->academicYear->year_name ?? 'Current Year' }}
                                    </h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($assignments as $assignment)
                                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h6 class="font-medium text-indigo-700">
                                                            {{ $assignment->subject->name }}
                                                        </h6>
                                                        <p class="text-sm text-gray-600">
                                                            {{ $assignment->class->name }}
                                                        </p>
                                                        @if($assignment->is_primary)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                                                Primary Teacher
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-right">
                                                        <a href="{{ route('teacher.marks.index') }}?class_id={{ $assignment->class_id }}&subject_id={{ $assignment->subject_id }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                                            Enter Marks
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No subject assignments</h3>
                                <p class="mt-1 text-sm text-gray-500">You haven't been assigned to any subjects yet.</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <div class="text-3xl font-bold text-blue-600">
                                {{ auth()->user()->teacher->teacherSubjects()->count() }}
                            </div>
                            <div class="text-blue-800 font-medium">Subject Assignments</div>
                            <div class="text-sm text-blue-700">Current academic year</div>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <div class="text-3xl font-bold text-green-600">
                                {{ auth()->user()->teacher->teacherSubjects()->groupBy('class_id')->count() }}
                            </div>
                            <div class="text-green-800 font-medium">Classes Teaching</div>
                            <div class="text-sm text-green-700">Current academic year</div>
                        </div>
                        
                        <div class="bg-purple-50 p-6 rounded-lg">
                            <div class="text-3xl font-bold text-purple-600">
                                {{ auth()->user()->teacher->teacherSubjects()->where('is_primary', true)->count() }}
                            </div>
                            <div class="text-purple-800 font-medium">Primary Subjects</div>
                            <div class="text-sm text-purple-700">Lead teaching responsibilities</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
