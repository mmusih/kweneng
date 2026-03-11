<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Admin Dashboard
            </h2>
        </div>
    </x-slot>

    @php
        $currentYear = \App\Models\AcademicYear::where('active', true)->first();

        $currentTerm = \App\Models\Term::whereHas('academicYear', function ($q) {
            $q->where('active', true);
        })
            ->where('status', 'active')
            ->first();

        $canPromote = $currentYear && $currentYear->isClosed();

        $pendingInterests = \App\Models\AlumniInterest::where('processed', false)->count();

        $recentInterests = \App\Models\AlumniInterest::where('processed', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $studentSubjectCount = \App\Models\StudentSubject::count();
        $totalSubjects = \App\Models\Subject::count();
        $activeSubjects = \App\Models\Subject::where('is_active', true)->count();
        $coreSubjects = \App\Models\Subject::where('is_core', true)->count();
        $classAssignments = \App\Models\ClassSubject::count();

        $schoolAverage = $schoolOverview['schoolAverage'] ?? null;
        $bestClass = $schoolOverview['bestClass'] ?? null;
        $weakestClass = $schoolOverview['weakestClass'] ?? null;
        $topSubject = $schoolOverview['topSubject'] ?? null;
        $weakestSubject = $schoolOverview['weakestSubject'] ?? null;
        $averageMarksCompletion = $schoolOverview['averageMarksCompletion'] ?? null;
        $atRiskStudentsCount = $schoolOverview['atRiskStudentsCount'] ?? 0;
        $totalMarks = $schoolOverview['totalMarks'] ?? 0;
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- ACADEMIC STATUS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Academic Status</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Current year, term, promotion readiness, and school snapshot
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        <div class="border rounded-lg p-5 bg-gradient-to-br from-blue-50 to-white shadow-sm">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-700">Academic Year</h4>
                                <span class="text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </span>
                            </div>

                            @if ($currentYear)
                                <p class="text-2xl font-bold mt-3 text-gray-900">{{ $currentYear->year_name }}</p>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-3
                                    @switch($currentYear->status)
                                        @case('open')
                                            bg-green-100 text-green-800
                                            @break
                                        @case('locked')
                                            bg-yellow-100 text-yellow-800
                                            @break
                                        @case('closed')
                                            bg-red-100 text-red-800
                                            @break
                                        @default
                                            bg-gray-100 text-gray-800
                                    @endswitch">
                                    {{ ucfirst($currentYear->status) }}
                                </span>
                            @else
                                <p class="text-gray-500 mt-3">No active academic year</p>
                            @endif
                        </div>

                        <div class="border rounded-lg p-5 bg-gradient-to-br from-indigo-50 to-white shadow-sm">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-700">Current Term</h4>
                                <span class="text-indigo-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            </div>

                            @if ($currentTerm)
                                <p class="text-2xl font-bold mt-3 text-gray-900">{{ $currentTerm->name }}</p>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-3
                                    @switch($currentTerm->status)
                                        @case('active')
                                            bg-blue-100 text-blue-800
                                            @break
                                        @case('finalized')
                                            bg-purple-100 text-purple-800
                                            @break
                                        @case('locked')
                                            bg-gray-100 text-gray-800
                                            @break
                                        @default
                                            bg-gray-100 text-gray-800
                                    @endswitch">
                                    {{ ucfirst($currentTerm->status) }}
                                </span>
                            @else
                                <p class="text-gray-500 mt-3">No active term</p>
                            @endif
                        </div>

                        <div class="border rounded-lg p-5 bg-gradient-to-br from-green-50 to-white shadow-sm">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-700">Promotion Status</h4>
                                <span class="text-green-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </span>
                            </div>

                            @if ($canPromote)
                                <p class="text-2xl font-bold text-green-600 mt-3">Ready</p>
                                <p class="text-sm text-green-700 mt-2">Current year is closed</p>
                            @else
                                <p class="text-2xl font-bold text-yellow-600 mt-3">Not Ready</p>
                                <p class="text-sm text-yellow-700 mt-2">Current year not closed</p>
                            @endif
                        </div>

                        <div class="border rounded-lg p-5 bg-gradient-to-br from-purple-50 to-white shadow-sm">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium text-gray-700">School Average</h4>
                                <span class="text-purple-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 3v18m-4-4h8" />
                                    </svg>
                                </span>
                            </div>

                            <p class="text-2xl font-bold text-purple-600 mt-3">
                                {{ $schoolAverage !== null ? number_format($schoolAverage, 2) . '%' : 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                From {{ $totalMarks }} mark records
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mt-6">
                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Best Class</p>
                            <p class="text-lg font-bold text-green-600 mt-2">
                                {{ $bestClass?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $bestClass && $bestClass->average_score !== null ? number_format($bestClass->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Weakest Class</p>
                            <p class="text-lg font-bold text-red-600 mt-2">
                                {{ $weakestClass?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $weakestClass && $weakestClass->average_score !== null ? number_format($weakestClass->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Top Subject</p>
                            <p class="text-lg font-bold text-blue-600 mt-2">
                                {{ $topSubject?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $topSubject && $topSubject->average_score !== null ? number_format($topSubject->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">At-Risk Students</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-2">
                                {{ $atRiskStudentsCount }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">Average below 40%</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CORE MANAGEMENT --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Core Management</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Main operational areas and day-to-day administration
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <a href="{{ route('admin.students.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-blue-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Students</h4>
                                    <p class="text-sm text-gray-500">{{ $stats['totalStudents'] }} enrolled</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.classes.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-green-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Classes</h4>
                                    <p class="text-sm text-gray-500">{{ $stats['totalClasses'] }} total</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.teachers.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-purple-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.747 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Academic Staff</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ $stats['totalTeachers'] }} teachers, {{ $stats['totalHeadmasters'] ?? 0 }}
                                        headmasters
                                    </p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.accounts-officers.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-red-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 1.343-3 3v1H8a2 2 0 00-2 2v2h12v-2a2 2 0 00-2-2h-1v-1c0-1.657-1.343-3-3-3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Accounts Officers</h4>
                                    <p class="text-sm text-gray-500">{{ $stats['totalAccountsOfficers'] ?? 0 }} active
                                        records</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.parents.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-yellow-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Parents</h4>
                                    <p class="text-sm text-gray-500">{{ $stats['totalParents'] }} registered</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.librarians.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-emerald-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-emerald-100 text-emerald-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13l-8-3v13l8 3 8-3v-13l-8 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Librarians</h4>
                                    <p class="text-sm text-gray-500">{{ $stats['totalLibrarians'] ?? 0 }} active</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.alumni.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-indigo-300 hover:-translate-y-1 relative">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Alumni</h4>
                                    <p class="text-sm text-gray-500">{{ $stats['totalAlumni'] ?? 0 }} registered</p>
                                </div>
                            </div>

                            @if ($pendingInterests > 0)
                                <span
                                    class="absolute top-3 right-3 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                    {{ $pendingInterests }} new
                                </span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>

            {{-- ACADEMIC OPERATIONS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Academic Operations</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Terms, years, promotions, marks, summaries, and subject setup
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <a href="{{ route('admin.academic-years.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-sky-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-sky-100 text-sky-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Academic Years</h4>
                                    <p class="text-sm text-gray-500">Open, lock, and close years</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.terms.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-indigo-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Terms</h4>
                                    <p class="text-sm text-gray-500">Create, edit, activate, finalize, lock</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.promotions.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-green-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Promotions</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ $canPromote ? 'Ready to promote students' : 'Promotion tools and history' }}
                                    </p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.marks.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-indigo-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Marks</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ $averageMarksCompletion !== null ? number_format($averageMarksCompletion, 1) . '% completion' : 'No data yet' }}
                                    </p>
                                </div>
                            </div>
                        </a>

                        @if (Route::has('admin.exam-summaries.index'))
                            <a href="{{ route('admin.exam-summaries.index') }}"
                                class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-red-300 hover:-translate-y-1">
                                <div class="flex items-center">
                                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-semibold">Exam Summaries</h4>
                                        <p class="text-sm text-gray-500">Midterm and endterm summary sheets</p>
                                    </div>
                                </div>
                            </a>
                        @endif

                        <a href="{{ route('admin.student-subjects.index') }}"
                            class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-teal-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-teal-100 text-teal-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.747 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Student Subject Assignments</h4>
                                    <p class="text-sm text-gray-500">{{ $studentSubjectCount }} active records</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            {{-- SUBJECT OVERVIEW --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b border-gray-200">
                        Subject Overview
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div
                            class="border rounded-lg p-5 text-center bg-gradient-to-br from-indigo-50 to-white shadow-sm">
                            <div class="text-3xl font-bold text-indigo-600 mb-2">{{ $totalSubjects }}</div>
                            <div class="text-sm text-gray-600 font-medium">Total Subjects</div>
                        </div>

                        <div
                            class="border rounded-lg p-5 text-center bg-gradient-to-br from-green-50 to-white shadow-sm">
                            <div class="text-3xl font-bold text-green-600 mb-2">{{ $activeSubjects }}</div>
                            <div class="text-sm text-gray-600 font-medium">Active Subjects</div>
                        </div>

                        <div
                            class="border rounded-lg p-5 text-center bg-gradient-to-br from-blue-50 to-white shadow-sm">
                            <div class="text-3xl font-bold text-blue-600 mb-2">{{ $coreSubjects }}</div>
                            <div class="text-sm text-gray-600 font-medium">Core Subjects</div>
                        </div>

                        <div
                            class="border rounded-lg p-5 text-center bg-gradient-to-br from-purple-50 to-white shadow-sm">
                            <div class="text-3xl font-bold text-purple-600 mb-2">{{ $classAssignments }}</div>
                            <div class="text-sm text-gray-600 font-medium">Class Assignments</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 justify-center pt-4 border-t border-gray-200">
                        <a href="{{ route('admin.subjects.index') }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Manage Subjects
                        </a>

                        <a href="{{ route('admin.subjects.manage-classes') }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Assign Subjects to Classes
                        </a>

                        <a href="{{ route('admin.subjects.manage-teachers') }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Assign Teachers to Subjects
                        </a>
                    </div>
                </div>
            </div>

            {{-- ALERTS & PENDING ITEMS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Alerts & Pending Items</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Items that need administrative attention
                        </p>
                    </div>

                    <div class="space-y-4">
                        @if (!$currentYear)
                            <div class="border border-yellow-200 bg-yellow-50 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-semibold text-yellow-800">No Active Academic Year</h4>
                                        <p class="text-sm text-yellow-700 mt-1">
                                            Activate an academic year so academic operations can proceed normally.
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.academic-years.index') }}"
                                        class="px-4 py-2 bg-yellow-600 text-white rounded-md text-sm font-semibold hover:bg-yellow-700">
                                        Open Academic Years
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if (!$currentTerm && $currentYear)
                            <div class="border border-orange-200 bg-orange-50 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-semibold text-orange-800">No Active Term</h4>
                                        <p class="text-sm text-orange-700 mt-1">
                                            An academic year is active, but no active term is currently set.
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.terms.index') }}"
                                        class="px-4 py-2 bg-orange-600 text-white rounded-md text-sm font-semibold hover:bg-orange-700">
                                        Open Terms
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($pendingInterests > 0)
                            <div class="border border-red-200 bg-red-50 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-semibold text-red-800">Pending Alumni Interest Requests</h4>
                                        <p class="text-sm text-red-700 mt-1">
                                            {{ $pendingInterests }} alumni interest
                                            {{ $pendingInterests === 1 ? 'request is' : 'requests are' }} waiting to be
                                            reviewed.
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.alumni.interests') }}"
                                        class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">
                                        Review Requests
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if ($averageMarksCompletion !== null && $averageMarksCompletion < 80)
                            <div class="border border-indigo-200 bg-indigo-50 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-semibold text-indigo-800">Marks Completion Below Target</h4>
                                        <p class="text-sm text-indigo-700 mt-1">
                                            Current average marks completion is
                                            {{ number_format($averageMarksCompletion, 1) }}%. Review marks entry
                                            progress.
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.marks.index') }}"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700">
                                        Open Marks
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if (($stats['totalLibrarians'] ?? 0) === 0)
                            <div class="border border-emerald-200 bg-emerald-50 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-semibold text-emerald-800">No Librarian Accounts</h4>
                                        <p class="text-sm text-emerald-700 mt-1">
                                            Create at least one librarian account to prepare for the library module.
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.librarians.create') }}"
                                        class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-semibold hover:bg-emerald-700">
                                        Create Librarian
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if (
                            $currentYear &&
                                $currentTerm &&
                                $pendingInterests === 0 &&
                                ($averageMarksCompletion === null || $averageMarksCompletion >= 80) &&
                                ($stats['totalLibrarians'] ?? 0) > 0)
                            <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                                <h4 class="font-semibold text-green-800">No Critical Alerts</h4>
                                <p class="text-sm text-green-700 mt-1">
                                    The main administrative areas currently look healthy.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RECENT ALUMNI INTERESTS --}}
            @if ($recentInterests->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-semibold text-gray-800">Recent Alumni Interests</h3>
                            <a href="{{ route('admin.alumni.interests') }}"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Graduation Year</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Submitted</th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($recentInterests as $interest)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $interest->full_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $interest->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $interest->graduation_year }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $interest->created_at->diffForHumans() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form action="{{ route('admin.alumni.process-interest', $interest) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="text-indigo-600 hover:text-indigo-900">
                                                        Mark Processed
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- CLASSES OVERVIEW --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-800">Classes Overview</h3>
                        <a href="{{ route('admin.classes.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Add New Class
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @if ($classes->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($classes as $class)
                                <div class="border border-gray-200 rounded-lg p-5 hover:bg-gray-50 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-lg text-gray-900">{{ $class->name }}</h4>
                                            <p class="text-gray-600">Level {{ $class->level }}</p>
                                        </div>
                                        <a href="{{ route('admin.classes.edit', $class) }}"
                                            class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            Edit
                                        </a>
                                    </div>
                                    <div class="mt-3">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $class->students->count() }} students
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">
                            No classes found.
                            <a href="{{ route('admin.classes.create') }}" class="text-blue-600 hover:text-blue-900">
                                Create your first class
                            </a>.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
