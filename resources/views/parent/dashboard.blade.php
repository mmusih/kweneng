<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Parent Dashboard
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}!</h3>
                        <p class="text-gray-600">Monitor your children's academic progress, school information, and
                            library records.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-6 mb-8">
                        <div class="bg-blue-50 border border-blue-100 p-5 rounded-lg">
                            <p class="text-sm text-blue-700">Total Children</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2">
                                {{ $stats['totalChildren'] }}
                            </p>
                        </div>

                        <div class="bg-green-50 border border-green-100 p-5 rounded-lg">
                            <p class="text-sm text-green-700">Results Accessible</p>
                            <p class="text-3xl font-bold text-green-600 mt-2">
                                {{ $stats['accessibleChildren'] }}
                            </p>
                        </div>

                        <div class="bg-red-50 border border-red-100 p-5 rounded-lg">
                            <p class="text-sm text-red-700">Blocked</p>
                            <p class="text-3xl font-bold text-red-600 mt-2">
                                {{ $stats['blockedChildren'] }}
                            </p>
                        </div>

                        <div class="bg-indigo-50 border border-indigo-100 p-5 rounded-lg">
                            <p class="text-sm text-indigo-700">Children With Marks</p>
                            <p class="text-3xl font-bold text-indigo-600 mt-2">
                                {{ $stats['childrenWithMarks'] }}
                            </p>
                        </div>

                        <div class="bg-purple-50 border border-purple-100 p-5 rounded-lg">
                            <p class="text-sm text-purple-700">Borrowed Books</p>
                            <p class="text-3xl font-bold text-purple-600 mt-2">
                                {{ $stats['borrowedBooks'] }}
                            </p>
                        </div>

                        <div class="bg-amber-50 border border-amber-100 p-5 rounded-lg">
                            <p class="text-sm text-amber-700">Overdue Books</p>
                            <p class="text-3xl font-bold text-amber-600 mt-2">
                                {{ $stats['overdueBooks'] }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800">Current Academic Year</h4>
                            @if ($currentAcademicYear)
                                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $currentAcademicYear->year_name }}
                                </p>
                            @else
                                <p class="text-gray-500 mt-2">No active academic year</p>
                            @endif
                        </div>

                        <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800">Current Term</h4>
                            @if ($currentTerm)
                                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $currentTerm->name }}</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $currentTerm->start_date->format('M j') }} -
                                    {{ $currentTerm->end_date->format('M j, Y') }}
                                </p>
                            @else
                                <p class="text-gray-500 mt-2">No active term</p>
                            @endif
                        </div>
                    </div>

                    <div class="mb-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Your Children</h4>

                        @if ($children->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($children as $child)
                                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                                        <div class="flex items-center">
                                            @if ($child->photo)
                                                <img class="h-12 w-12 rounded-full"
                                                    src="{{ Storage::url($child->photo) }}"
                                                    alt="{{ $child->user->name }}">
                                            @else
                                                <div
                                                    class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-gray-500">
                                                        {{ substr($child->user->name ?? 'N', 0, 1) }}
                                                    </span>
                                                </div>
                                            @endif

                                            <div class="ml-4">
                                                <h5 class="font-medium text-gray-800">
                                                    {{ $child->user->name ?? 'Unknown Student' }}
                                                </h5>
                                                <p class="text-sm text-gray-600">{{ $child->admission_no ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3 text-sm text-gray-600">
                                            Class:
                                            <span class="font-medium">
                                                {{ $child->currentClass->name ?? 'N/A' }}
                                            </span>
                                        </div>

                                        @if ($child->fees_blocked)
                                            <div class="mt-4 bg-red-50 border border-red-200 rounded p-3">
                                                <p class="text-sm font-semibold text-red-700">
                                                    Results Access Blocked
                                                </p>
                                                <p class="text-xs text-red-600 mt-1">
                                                    Please contact the accounts office.
                                                </p>
                                            </div>
                                        @else
                                            <div class="mt-4 bg-green-50 border border-green-200 rounded p-3">
                                                <p class="text-sm font-semibold text-green-700">
                                                    Results Accessible
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <p class="text-gray-500">No children linked to your account.</p>
                            </div>
                        @endif
                    </div>

                    @if ($blockedChildren->count() > 0)
                        <div class="mb-8 bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="text-lg font-semibold text-red-800">Restricted Academic Access</h4>
                            <p class="text-sm text-red-700 mt-1">
                                One or more students linked to your account currently have restricted access to results
                                due to outstanding obligations.
                            </p>
                        </div>
                    @endif

                    @if ($accessibleChildren->count() > 0)
                        <div class="mt-8">
                            <div class="mb-4">
                                <h4 class="text-lg font-semibold text-gray-800">Children's Academic Overview</h4>
                                <p class="text-sm text-gray-600 mt-1">Current term performance and student life summary
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($marksOverview as $overview)
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h5 class="font-medium text-gray-800">{{ $overview['student_name'] }}</h5>
                                            <span class="text-sm text-gray-600">{{ $overview['admission_no'] }}</span>
                                        </div>

                                        @if ($currentTerm)
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="bg-blue-50 p-3 rounded">
                                                    <p class="text-xs text-blue-700">Midterm Avg</p>
                                                    <p class="text-xl font-bold text-blue-600">
                                                        {{ $overview['midterm_average'] !== null ? number_format($overview['midterm_average'], 2) : 'N/A' }}
                                                    </p>
                                                </div>

                                                <div class="bg-green-50 p-3 rounded">
                                                    <p class="text-xs text-green-700">Endterm Avg</p>
                                                    <p class="text-xl font-bold text-green-600">
                                                        {{ $overview['endterm_average'] !== null ? number_format($overview['endterm_average'], 2) : 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-600">
                                                {{ $overview['subjects_count'] }} subjects marked
                                                | {{ $overview['class_name'] }}
                                            </div>

                                            <div class="mt-4 grid grid-cols-1 gap-2 text-sm">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Performance</span>
                                                    <span class="font-medium text-indigo-600">
                                                        {{ $overview['performance_label'] ?? 'N/A' }}
                                                    </span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Trend</span>
                                                    <span class="font-medium text-blue-600">
                                                        {{ $overview['trend'] ?? 'N/A' }}
                                                    </span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Midterm Position</span>
                                                    <span class="font-medium text-gray-800">
                                                        @if ($overview['midterm_position'] && $overview['midterm_position']['position'])
                                                            {{ $overview['midterm_position']['position'] }}/{{ $overview['midterm_position']['class_size'] }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Endterm Position</span>
                                                    <span class="font-medium text-gray-800">
                                                        @if ($overview['endterm_position'] && $overview['endterm_position']['position'])
                                                            {{ $overview['endterm_position']['position'] }}/{{ $overview['endterm_position']['class_size'] }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Attendance</span>
                                                    <span class="font-medium text-indigo-600">
                                                        {{ $overview['attendance_rate'] !== null ? $overview['attendance_rate'] . '%' : 'N/A' }}
                                                    </span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Punctuality</span>
                                                    <span class="font-medium text-gray-800">
                                                        On time {{ $overview['punctuality_on_time'] }},
                                                        Late {{ $overview['punctuality_late'] }},
                                                        Very late {{ $overview['punctuality_very_late'] }}
                                                    </span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Behaviour</span>
                                                    <span class="font-medium text-gray-800">
                                                        {{ $overview['behaviour_label'] }}
                                                        ({{ $overview['behaviour_total'] }})
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">No active term currently</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-8">
                        <div class="mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">Children's Library Summary</h4>
                            <p class="text-sm text-gray-600 mt-1">Current borrowed books and overdue alerts</p>
                        </div>

                        @if (count($childrenLibrarySummary) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($childrenLibrarySummary as $librarySummary)
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h5 class="font-medium text-gray-800">
                                                {{ $librarySummary['student_name'] }}</h5>
                                            <span
                                                class="text-sm text-gray-600">{{ $librarySummary['class_name'] }}</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div class="bg-purple-50 p-3 rounded">
                                                <p class="text-xs text-purple-700">Borrowed</p>
                                                <p class="text-xl font-bold text-purple-600">
                                                    {{ $librarySummary['borrowed_books'] }}
                                                </p>
                                            </div>

                                            <div class="bg-amber-50 p-3 rounded">
                                                <p class="text-xs text-amber-700">Overdue</p>
                                                <p class="text-xl font-bold text-amber-600">
                                                    {{ $librarySummary['overdue_books'] }}
                                                </p>
                                            </div>
                                        </div>

                                        @if ($librarySummary['borrowings']->count() > 0)
                                            <div class="space-y-2">
                                                @foreach ($librarySummary['borrowings'] as $borrowing)
                                                    @php
                                                        $isOverdue = $borrowing->due_at && $borrowing->due_at->isPast();
                                                    @endphp
                                                    <div
                                                        class="rounded-md border {{ $isOverdue ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50' }} p-3">
                                                        <div class="font-medium text-gray-900">
                                                            {{ $borrowing->bookCopy->book->title ?? 'N/A' }}
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            Barcode: {{ $borrowing->bookCopy->barcode ?? 'N/A' }}
                                                        </div>
                                                        <div
                                                            class="text-xs mt-1 {{ $isOverdue ? 'text-red-700' : 'text-gray-600' }}">
                                                            Due: {{ optional($borrowing->due_at)->format('d M Y') }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">No active borrowed books.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-6 text-center text-gray-500">
                                No library records found.
                            </div>
                        @endif
                    </div>

                    <div class="mt-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Quick Access</h4>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            @if ($accessibleChildren->count() > 0)
                                <a href="{{ route('parent.children.marks.index') }}"
                                    class="border rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex items-center">
                                        <div class="rounded-full bg-blue-100 p-2">
                                            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                                            </svg>
                                        </div>

                                        <div class="ml-3">
                                            <h5 class="font-medium text-gray-800">View All Marks</h5>
                                            <p class="text-sm text-gray-600">See detailed performance</p>
                                        </div>
                                    </div>
                                </a>
                            @else
                                <div class="border rounded-lg p-4 bg-gray-50 opacity-80">
                                    <div class="flex items-center">
                                        <div class="rounded-full bg-gray-200 p-2">
                                            <svg class="h-6 w-6 text-gray-500" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
                                            </svg>
                                        </div>

                                        <div class="ml-3">
                                            <h5 class="font-medium text-gray-700">View All Marks</h5>
                                            <p class="text-sm text-gray-500">Unavailable at this time</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('parent.children.library.index') }}"
                                class="border rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-center">
                                    <div class="rounded-full bg-purple-100 p-2">
                                        <svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>

                                    <div class="ml-3">
                                        <h5 class="font-medium text-gray-800">Children Library</h5>
                                        <p class="text-sm text-gray-600">View borrowed books and due dates</p>
                                    </div>
                                </div>
                            </a>

                            <div class="border rounded-lg p-4 opacity-50">
                                <h5 class="font-medium">Attendance</h5>
                                <p class="text-sm text-gray-600">Detailed reports coming soon</p>
                            </div>

                            <div class="border rounded-lg p-4 opacity-50">
                                <h5 class="font-medium">Events</h5>
                                <p class="text-sm text-gray-600">Coming soon</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">Account Information</h4>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Email</p>
                                    <p class="font-medium">{{ auth()->user()->email }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-600">Account Status</p>
                                    <span
                                        class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
