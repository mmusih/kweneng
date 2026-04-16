<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        Teacher Dashboard
                    </h2>
                    <p class="text-blue-100 text-sm mt-1">
                        Teaching, class management, and daily operations
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $teacher = $user?->teacher;

        $classTeacherClasses = collect();

        if ($teacher) {
            $classTeacherClasses = \App\Models\ClassModel::with('academicYear')
                ->where('class_teacher_id', $teacher->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get();
        }

        $isClassTeacher = $classTeacherClasses->count() > 0;

        $teachingAssignments = collect();

        if ($teacher) {
            $teachingAssignments = \App\Models\TeacherSubject::with(['class', 'subject', 'academicYear'])
                ->where('teacher_id', $teacher->id)
                ->orderByDesc('academic_year_id')
                ->get();
        }

        $currentAcademicYear = \App\Models\AcademicYear::where('active', true)->first();

        $currentTerm = \App\Models\Term::whereHas('academicYear', function ($query) {
            $query->where('active', true);
        })
            ->where('status', 'active')
            ->first();

        $homeworkCount = 0;
        $recentHomeworkCount = 0;

        if ($teacher) {
            $homeworkCount = \App\Models\Homework::where('teacher_id', $teacher->id)->count();

            $recentHomeworkCount = \App\Models\Homework::where('teacher_id', $teacher->id)
                ->when($currentTerm, function ($query) use ($currentTerm) {
                    $query->where('term_id', $currentTerm->id);
                })
                ->count();
        }
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
                <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                    <p class="text-sm text-gray-500">Academic Year</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $currentAcademicYear?->year_name ?? 'N/A' }}
                    </h3>
                    <p class="text-sm mt-2 text-gray-500">
                        {{ $currentAcademicYear ? ucfirst($currentAcademicYear->status) : 'No active academic year' }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                    <p class="text-sm text-gray-500">Current Term</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $currentTerm?->name ?? 'N/A' }}
                    </h3>
                    <p class="text-sm mt-2 text-gray-500">
                        {{ $currentTerm ? ucfirst($currentTerm->status) : 'No active term' }}
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                    <p class="text-sm text-gray-500">Teaching Assignments</p>
                    <h3 class="text-2xl font-bold text-indigo-600 mt-2">
                        {{ $teachingAssignments->count() }}
                    </h3>
                    <p class="text-sm mt-2 text-gray-500">
                        Subject-class assignments
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                    <p class="text-sm text-gray-500">Homework Records</p>
                    <h3 class="text-2xl font-bold text-cyan-600 mt-2">
                        {{ $homeworkCount }}
                    </h3>
                    <p class="text-sm mt-2 text-gray-500">
                        {{ $recentHomeworkCount }} in current term
                    </p>
                </div>

                <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                    <p class="text-sm text-gray-500">Class Teacher Role</p>
                    <h3 class="text-2xl font-bold mt-2 {{ $isClassTeacher ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $isClassTeacher ? 'Assigned' : 'Not Assigned' }}
                    </h3>
                    <p class="text-sm mt-2 text-gray-500">
                        {{ $isClassTeacher ? $classTeacherClasses->count() . ' class(es)' : 'No class teacher class assigned' }}
                    </p>
                </div>
            </div>

            <!-- General Actions -->
            <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                <div class="mb-6 border-b border-gray-200 pb-3">
                    <h3 class="text-xl font-semibold text-gray-800">Main Actions</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Access your core teaching tools
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <a href="{{ route('teacher.marks.index') }}"
                        class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-blue-50 to-white shadow-sm hover:shadow-md transition-all hover:-translate-y-1 flex items-start">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Enter Marks</h4>
                            <p class="text-sm text-gray-500 mt-1">Record and update student marks.</p>
                        </div>
                    </a>

                    <a href="{{ route('teacher.homeworks.index') }}"
                        class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-cyan-50 to-white shadow-sm hover:shadow-md transition-all hover:-translate-y-1 flex items-start">
                        <div class="p-3 rounded-full bg-cyan-100 text-cyan-600 mr-4 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18c3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Homework</h4>
                            <p class="text-sm text-gray-500 mt-1">Create homework and record homework marks.</p>
                        </div>
                    </a>

                    <div
                        class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-indigo-50 to-white shadow-sm flex items-start">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Teaching Overview</h4>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $teachingAssignments->count() }} active assignment(s) linked to your account.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Teacher Tools -->
            <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                <div class="mb-6 border-b border-gray-200 pb-3">
                    <h3 class="text-xl font-semibold text-gray-800">Class Teacher Tools</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Tools available only when you are assigned as a class teacher
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    @if ($isClassTeacher)
                        <a href="{{ route('teacher.attendance.index') }}"
                            class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-emerald-50 to-white shadow-sm hover:shadow-md transition-all hover:-translate-y-1 flex items-start">
                            <div class="p-3 rounded-full bg-emerald-100 text-emerald-600 mr-4 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 104 0M9 5a2 2 0 012 2m0-2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Attendance</h4>
                                <p class="text-sm text-gray-500 mt-1">Record daily class attendance.</p>
                            </div>
                        </a>

                        <a href="{{ route('teacher.punctuality.index') }}"
                            class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-amber-50 to-white shadow-sm hover:shadow-md transition-all hover:-translate-y-1 flex items-start">
                            <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Punctuality</h4>
                                <p class="text-sm text-gray-500 mt-1">Record lateness and arrival status.</p>
                            </div>
                        </a>

                        <a href="{{ route('teacher.behaviour.index') }}"
                            class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-rose-50 to-white shadow-sm hover:shadow-md transition-all hover:-translate-y-1 flex items-start">
                            <div class="p-3 rounded-full bg-rose-100 text-rose-600 mr-4 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 5.636l-1.414 1.414M15 7l-1-1m-4 4a4 4 0 118 0c0 1.657-.896 3.105-2.229 3.88L15 15H9l-.771-1.12A4.001 4.001 0 018 10z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Behaviour</h4>
                                <p class="text-sm text-gray-500 mt-1">Record behaviour incidents and actions.</p>
                            </div>
                        </a>

                        <a href="{{ route('teacher.term-summary.index') }}"
                            class="border rounded-xl p-6 min-h-[130px] bg-gradient-to-br from-violet-50 to-white shadow-sm hover:shadow-md transition-all hover:-translate-y-1 flex items-start">
                            <div class="p-3 rounded-full bg-violet-100 text-violet-600 mr-4 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-6h13M9 5v6h13M5 5h.01M5 12h.01M5 19h.01" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Term Summary</h4>
                                <p class="text-sm text-gray-500 mt-1">Enter overall attendance, punctuality, and
                                    behaviour.</p>
                            </div>
                        </a>
                    @else
                        <div
                            class="md:col-span-2 xl:col-span-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                            You are not currently assigned as a class teacher. Attendance, punctuality, behaviour, and
                            term summary tools become available once a class is assigned to you as class teacher.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Class Teacher Assignment -->
            <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                <div class="mb-6 border-b border-gray-200 pb-3">
                    <h3 class="text-xl font-semibold text-gray-800">Class Teacher Assignment</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Classes where you are officially assigned as class teacher
                    </p>
                </div>

                @if ($isClassTeacher)
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach ($classTeacherClasses as $class)
                            <div
                                class="border border-gray-200 rounded-xl p-5 bg-gradient-to-br from-green-50 to-white">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $class->name }}</h4>
                                <p class="text-sm text-gray-500 mt-1">Level {{ $class->level }}</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $class->academicYear->year_name ?? 'N/A' }}
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ route('teacher.attendance.index', ['class_id' => $class->id]) }}"
                                        class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-md hover:bg-emerald-700">
                                        Attendance
                                    </a>

                                    <a href="{{ route('teacher.punctuality.index', ['class_id' => $class->id]) }}"
                                        class="inline-flex items-center px-3 py-2 bg-amber-600 text-white text-sm font-semibold rounded-md hover:bg-amber-700">
                                        Punctuality
                                    </a>

                                    <a href="{{ route('teacher.behaviour.index', ['class_id' => $class->id]) }}"
                                        class="inline-flex items-center px-3 py-2 bg-rose-600 text-white text-sm font-semibold rounded-md hover:bg-rose-700">
                                        Behaviour
                                    </a>

                                    <a href="{{ route('teacher.term-summary.index', ['class_id' => $class->id]) }}"
                                        class="inline-flex items-center px-3 py-2 bg-violet-600 text-white text-sm font-semibold rounded-md hover:bg-violet-700">
                                        Term Summary
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                        You are not currently assigned as a class teacher. Attendance, punctuality, behaviour, and term
                        summary tools
                        become available once a class is assigned to you as class teacher.
                    </div>
                @endif
            </div>

            <!-- Teaching Assignments -->
            <div class="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
                <div class="mb-6 border-b border-gray-200 pb-3">
                    <h3 class="text-xl font-semibold text-gray-800">Teaching Assignments</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Subject assignments linked to your teaching role
                    </p>
                </div>

                @if ($teachingAssignments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Subject
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Academic Year
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Primary
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($teachingAssignments as $assignment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $assignment->class->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $assignment->subject->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $assignment->academicYear->year_name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($assignment->is_primary)
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Yes
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-700">
                                                    No
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                        No teaching assignments found for your account.
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
