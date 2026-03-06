<x-app-layout>
<x-slot name="header">
    <div class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
        <h2 class="font-semibold text-2xl text-white leading-tight">
            Admin Dashboard
        </h2>
    </div>
</x-slot>


    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Academic Management Section (NEW) -->
            <div class="mt-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Academic Management</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="border rounded-lg p-6">
                                <h4 class="font-medium text-gray-700 mb-3">Academic Years</h4>
                                <p class="text-sm text-gray-600 mb-4">Manage academic years, terms, and enrollment periods.</p>
                                <a href="{{ route('admin.academic-years.index') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Manage Academic Years
                                </a>
                            </div>
                            
                            <div class="border rounded-lg p-6">
                                <h4 class="font-medium text-gray-700 mb-3">Terms</h4>
                                <p class="text-sm text-gray-600 mb-4">Set up terms and marking periods for each academic year.</p>
                                <a href="{{ route('admin.terms.index') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Manage Terms
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Structure Status Section -->
            <div class="mb-8 mt-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b border-gray-200">Academic Structure Status</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Current Academic Year -->
                            <div class="border rounded-lg p-5 bg-gradient-to-br from-blue-50 to-white shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center">
                                    <div class="p-2 rounded-lg bg-blue-100 text-blue-600 mr-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="font-medium text-gray-700 text-lg">Current Academic Year</h4>
                                </div>
                                @php
                                    $currentYear = \App\Models\AcademicYear::where('active', true)->first();
                                @endphp
                                @if($currentYear)
                                    <p class="text-2xl font-bold mt-3 text-gray-900">{{ $currentYear->year_name }}</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-2
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
                                        @endswitch">
                                        {{ ucfirst($currentYear->status) }}
                                    </span>
                                @else
                                    <p class="text-gray-500 mt-3">No active academic year</p>
                                @endif
                            </div>
                            
                            <!-- Current Term -->
                            <div class="border rounded-lg p-5 bg-gradient-to-br from-indigo-50 to-white shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center">
                                    <div class="p-2 rounded-lg bg-indigo-100 text-indigo-600 mr-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="font-medium text-gray-700 text-lg">Current Term</h4>
                                </div>
                                @php
                                    $currentTerm = \App\Models\Term::whereHas('academicYear', function($q) {
                                        $q->where('active', true);
                                    })->where('status', 'active')->first();
                                @endphp
                                @if($currentTerm)
                                    <p class="text-2xl font-bold mt-3 text-gray-900">{{ $currentTerm->name }}</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mt-2
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
                                        @endswitch">
                                        {{ ucfirst($currentTerm->status) }}
                                    </span>
                                @else
                                    <p class="text-gray-500 mt-3">No active term</p>
                                @endif
                            </div>
                            
                            <!-- Promotion Status -->
                            <div class="border rounded-lg p-5 bg-gradient-to-br from-green-50 to-white shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center">
                                    <div class="p-2 rounded-lg bg-green-100 text-green-600 mr-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                    <h4 class="font-medium text-gray-700 text-lg">Promotion Status</h4>
                                </div>
                                @php
                                    $canPromote = $currentYear && $currentYear->isClosed();
                                @endphp
                                @if($canPromote)
                                    <p class="text-2xl font-bold text-green-600 mt-3">Ready</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mt-2">
                                        Current year is closed
                                    </span>
                                @else
                                    <p class="text-2xl font-bold text-yellow-600 mt-3">Not Ready</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mt-2">
                                        Current year not closed
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 text-gray-900">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-lg shadow-sm border border-blue-200">
                            <h4 class="text-lg font-semibold text-blue-800">Total Users</h4>
                            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['totalUsers'] }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-lg shadow-sm border border-green-200">
                            <h4 class="text-lg font-semibold text-green-800">Students</h4>
                            <p class="text-3xl font-bold text-green-600 mt-2">{{ $stats['totalStudents'] }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-lg shadow-sm border border-purple-200">
                            <h4 class="text-lg font-semibold text-purple-800">Teachers</h4>
                            <p class="text-3xl font-bold text-purple-600 mt-2">{{ $stats['totalTeachers'] }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-5 rounded-lg shadow-sm border border-yellow-200">
                            <h4 class="text-lg font-semibold text-yellow-800">Parents</h4>
                            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['totalParents'] }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-red-50 to-red-100 p-5 rounded-lg shadow-sm border border-red-200">
                            <h4 class="text-lg font-semibold text-red-800">Accounts Officers</h4>
                            <p class="text-3xl font-bold text-red-600 mt-2">{{ $stats['totalAccountsOfficers'] }}</p>
                        </div>

                        <!-- Alumni Stats Card -->
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-5 rounded-lg shadow-sm border border-indigo-200 relative">
                            <h4 class="text-lg font-semibold text-indigo-800">Alumni</h4>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">{{ $stats['totalAlumni'] ?? 0 }}</p>
                            @php
                                $pendingInterests = \App\Models\AlumniInterest::where('processed', false)->count();
                            @endphp
                            @if($pendingInterests > 0)
                                <span class="absolute top-3 right-3 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                    {{ $pendingInterests }} pending
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Navigation Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                        <a href="{{ route('admin.students.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-blue-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Manage Students</h3>
                                    <p class="text-sm text-gray-500">{{ $stats['totalStudents'] }} enrolled</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.classes.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-green-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Manage Classes</h3>
                                    <p class="text-sm text-gray-500">{{ $classes->count() }} active classes</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.teachers.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-purple-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.747 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Manage Teachers</h3>
                                    <p class="text-sm text-gray-500">{{ $stats['totalTeachers'] }} active</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.parents.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-yellow-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Manage Parents</h3>
                                    <p class="text-sm text-gray-500">{{ $stats['totalParents'] }} registered</p>
                                </div>
                            </div>
                        </a>

                        <!-- Alumni Navigation Card -->
                        <a href="{{ route('admin.alumni.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-indigo-300 hover:-translate-y-1 relative">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Manage Alumni</h3>
                                    <p class="text-sm text-gray-500">{{ $stats['totalAlumni'] ?? 0 }} registered</p>
                                </div>
                            </div>
                            @php
                                $pendingInterests = \App\Models\AlumniInterest::where('processed', false)->count();
                            @endphp
                            @if($pendingInterests > 0)
                                <span class="absolute top-3 right-3 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                    {{ $pendingInterests }} new
                                </span>
                            @endif
                        </a>
                    </div>

                    <!-- MARKS MANAGEMENT CARDS (NEW) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <a href="{{ route('admin.marks.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-indigo-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">View All Marks</h3>
                                    <p class="text-sm text-gray-500">Manage student grades and results</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('admin.student-subjects.index') }}" 
                           class="bg-white p-5 rounded-lg shadow-sm hover:shadow-md border border-gray-200 transition-all duration-200 hover:border-teal-300 hover:-translate-y-1">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-teal-100 text-teal-600 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.747 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Student Subject Assignments</h3>
                                    <p class="text-sm text-gray-500">Manage student-subject enrollments</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Alumni Interests Section (New) -->
                    @php
                        $recentInterests = \App\Models\AlumniInterest::where('processed', false)
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp

                    @if($recentInterests->count() > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-semibold text-gray-800">Recent Alumni Interests</h3>
                                <a href="{{ route('admin.alumni.interests') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    View All
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduation Year</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($recentInterests as $interest)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $interest->full_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $interest->email }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $interest->graduation_year }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $interest->created_at->diffForHumans() }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form action="{{ route('admin.alumni.process-interest', $interest) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900">Mark Processed</button>
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

                    <!-- Classes Section -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-semibold text-gray-800">Classes</h3>
                                <a href="{{ route('admin.classes.create') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Add New Class
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            @if($classes->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($classes as $class)
                                        <div class="border border-gray-200 rounded-lg p-5 hover:bg-gray-50 transition-colors">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-semibold text-lg text-gray-900">{{ $class->name }}</h4>
                                                    <p class="text-gray-600">Level {{ $class->level }}</p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('admin.classes.edit', $class) }}" 
                                                       class="text-blue-600 hover:text-blue-900">
                                                        Edit
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $class->students->count() }} students
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No classes found. <a href="{{ route('admin.classes.create') }}" class="text-blue-600 hover:text-blue-900">Create your first class</a>.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Management Overview Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 pb-2 border-b border-gray-200">Subject Management Overview</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <!-- Total Subjects -->
                        <div class="border rounded-lg p-5 text-center bg-gradient-to-br from-indigo-50 to-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="text-3xl font-bold text-indigo-600 mb-2">
                                {{ App\Models\Subject::count() }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium">Total Subjects</div>
                        </div>
                        
                        <!-- Active Subjects -->
                        <div class="border rounded-lg p-5 text-center bg-gradient-to-br from-green-50 to-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="text-3xl font-bold text-green-600 mb-2">
                                {{ App\Models\Subject::where('is_active', true)->count() }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium">Active Subjects</div>
                        </div>
                        
                        <!-- Core Subjects -->
                        <div class="border rounded-lg p-5 text-center bg-gradient-to-br from-blue-50 to-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="text-3xl font-bold text-blue-600 mb-2">
                                {{ App\Models\Subject::where('is_core', true)->count() }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium">Core Subjects</div>
                        </div>
                        
                        <!-- Subject-Class Assignments -->
                        <div class="border rounded-lg p-5 text-center bg-gradient-to-br from-purple-50 to-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="text-3xl font-bold text-purple-600 mb-2">
                                {{ App\Models\ClassSubject::count() }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium">Class Assignments</div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="flex flex-wrap gap-3 justify-center pt-4 border-t border-gray-200">
                        <a href="{{ route('admin.subjects.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.747 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Manage Subjects
                        </a>
                        <a href="{{ route('admin.subjects.manage-classes') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Assign Subjects to Classes
                        </a>
                        <a href="{{ route('admin.subjects.manage-teachers') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c1.747 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Assign Teachers to Subjects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
