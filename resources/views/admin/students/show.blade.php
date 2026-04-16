<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Student Profile
                </h2>
                <p class="text-blue-100 text-sm mt-1">
                    Full student overview, academic placement, and access status
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.students.index') }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition text-sm font-medium">
                    ← Back to List
                </a>

                <a href="{{ route('admin.students.edit', $student) }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-blue-50 transition text-sm font-semibold">
                    Edit Student
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $studentName = $student->user->name ?? 'N/A';
        $studentEmail = $student->user->email ?? 'N/A';
        $studentInitial = strtoupper(substr($studentName, 0, 1));

        $currentClass = $student->currentClass;
        $currentAcademicYear = $currentClass?->academicYear?->year_name;
        $historyCount = $student->classHistory?->count() ?? 0;

        $parents = collect();
        try {
            if (method_exists($student, 'parents')) {
                $parents = $student->parents;
            }
        } catch (\Throwable $e) {
            $parents = collect();
        }

        $studentSubjects = collect();
        try {
            if (method_exists($student, 'studentSubjects')) {
                $studentSubjects = $student->studentSubjects;
            }
        } catch (\Throwable $e) {
            $studentSubjects = collect();
        }

        $age = null;
        try {
            $age = $student->date_of_birth ? $student->date_of_birth->age : null;
        } catch (\Throwable $e) {
            $age = null;
        }

        $currentHistory = $student->classHistory?->firstWhere('is_current', true);
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 md:p-8">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-8">

                        <div class="flex-shrink-0">
                            @if ($student->photo)
                                <img src="{{ Storage::url($student->photo) }}" alt="{{ $studentName }}"
                                    class="h-28 w-28 rounded-full object-cover border-4 border-white shadow-lg ring-2 ring-indigo-100">
                            @else
                                <div
                                    class="h-28 w-28 rounded-full bg-gradient-to-br from-indigo-100 to-blue-100 flex items-center justify-center border-4 border-white shadow-lg ring-2 ring-indigo-100">
                                    <span class="text-3xl font-bold text-indigo-600">{{ $studentInitial }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1">
                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                                <div>
                                    <h3 class="text-3xl font-bold text-gray-900">
                                        {{ $studentName }}
                                    </h3>
                                    <p class="text-gray-600 mt-1">
                                        {{ $studentEmail }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                            Admission No: {{ $student->admission_no }}
                                        </span>

                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 capitalize">
                                            {{ $student->gender }}
                                        </span>

                                        @if ($age)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                                {{ $age }} years old
                                            </span>
                                        @endif

                                        @if ($currentClass)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                {{ $currentClass->name }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                No Class Assigned
                                            </span>
                                        @endif

                                        @if ($student->user?->must_change_password)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                                Must Change Password
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 min-w-[260px]">
                                    <div
                                        class="rounded-xl border p-4 {{ $student->results_access ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">
                                            Results Access
                                        </p>
                                        <p
                                            class="mt-2 font-bold {{ $student->results_access ? 'text-green-700' : 'text-red-700' }}">
                                            {{ $student->results_access ? 'Enabled' : 'Blocked' }}
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-xl border p-4 {{ $student->fees_blocked ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }}">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">
                                            Fees Status
                                        </p>
                                        <p
                                            class="mt-2 font-bold {{ $student->fees_blocked ? 'text-red-700' : 'text-green-700' }}">
                                            {{ $student->fees_blocked ? 'Blocked' : 'Clear' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Date of Birth
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900">
                                        {{ $student->date_of_birth ? $student->date_of_birth->format('M j, Y') : 'N/A' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Current Class
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900">
                                        {{ $currentClass?->name ?? 'Not Assigned' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Academic Year
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900">
                                        {{ $currentAcademicYear ?? 'N/A' }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">History
                                        Records</p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900">
                                        {{ $historyCount }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Enrollment Status</p>
                    <h4 class="text-2xl font-bold mt-2 {{ $currentClass ? 'text-emerald-600' : 'text-yellow-600' }}">
                        {{ $currentClass ? 'Active' : 'Unassigned' }}
                    </h4>
                    <p class="text-sm mt-2 text-gray-500">
                        {{ $currentClass ? 'Student is currently enrolled in a class' : 'Student has no current class allocation' }}
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Parent / Guardian Links</p>
                    <h4 class="text-2xl font-bold mt-2 text-blue-600">
                        {{ $parents->count() }}
                    </h4>
                    <p class="text-sm mt-2 text-gray-500">
                        Linked parent or guardian records
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Subjects</p>
                    <h4 class="text-2xl font-bold mt-2 text-indigo-600">
                        {{ $studentSubjects->count() }}
                    </h4>
                    <p class="text-sm mt-2 text-gray-500">
                        Assigned student subject records
                    </p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Current History Status</p>
                    <h4 class="text-2xl font-bold mt-2 text-purple-600">
                        {{ $currentHistory ? ucfirst($currentHistory->status ?? 'active') : 'N/A' }}
                    </h4>
                    <p class="text-sm mt-2 text-gray-500">
                        From student class history
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <div class="xl:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-5 border-b border-gray-200 pb-3">
                        <h4 class="text-lg font-semibold text-gray-800">Current Enrollment</h4>
                        <p class="text-sm text-gray-500 mt-1">Current class placement and school access information</p>
                    </div>

                    @if ($currentClass)
                        <div class="space-y-4">
                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Class</p>
                                <p class="mt-2 text-sm font-bold text-gray-900">{{ $currentClass->name }}</p>
                            </div>

                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Level</p>
                                <p class="mt-2 text-sm font-bold text-gray-900">Level
                                    {{ $currentClass->level ?? 'N/A' }}</p>
                            </div>

                            <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Academic Year</p>
                                <p class="mt-2 text-sm font-bold text-gray-900">{{ $currentAcademicYear ?? 'N/A' }}</p>
                            </div>

                            @if ($currentClass->classTeacher)
                                <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Class Teacher
                                    </p>
                                    <p class="mt-2 text-sm font-bold text-gray-900">
                                        {{ $currentClass->classTeacher->user->name ?? 'N/A' }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                            This student does not currently have an active class assignment.
                        </div>
                    @endif
                </div>

                <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-5 border-b border-gray-200 pb-3">
                        <h4 class="text-lg font-semibold text-gray-800">Parent / Guardian Information</h4>
                        <p class="text-sm text-gray-500 mt-1">Linked parent or guardian contacts for this student</p>
                    </div>

                    @if ($parents->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($parents as $parent)
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                                    <h5 class="font-semibold text-gray-900">
                                        {{ $parent->user->name ?? 'N/A' }}
                                    </h5>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ $parent->user->email ?? 'No email' }}
                                    </p>

                                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                                        @if (!empty($parent->phone))
                                            <p><span class="font-medium text-gray-500">Phone:</span>
                                                {{ $parent->phone }}</p>
                                        @endif

                                        @php
                                            $relationship = null;
                                            if (isset($parent->pivot) && isset($parent->pivot->relationship)) {
                                                $relationship = $parent->pivot->relationship;
                                            }
                                        @endphp

                                        @if ($relationship)
                                            <p>
                                                <span class="font-medium text-gray-500">Relationship:</span>
                                                <span class="capitalize">{{ $relationship }}</span>
                                            </p>
                                        @endif

                                        @if (!empty($parent->address))
                                            <p><span class="font-medium text-gray-500">Address:</span>
                                                {{ $parent->address }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                            No parent or guardian has been linked to this student yet.
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="mb-5 border-b border-gray-200 pb-3">
                    <h4 class="text-lg font-semibold text-gray-800">Subject Overview</h4>
                    <p class="text-sm text-gray-500 mt-1">Subjects currently linked to the student</p>
                </div>

                @if ($studentSubjects->count() > 0)
                    <div class="flex flex-wrap gap-3">
                        @foreach ($studentSubjects as $studentSubject)
                            <span
                                class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium {{ !empty($studentSubject->is_elective) ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                                {{ $studentSubject->subject->name ?? 'Subject' }}
                                @if (!empty($studentSubject->is_elective))
                                    <span class="ml-2 text-xs font-semibold">(Elective)</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-gray-600">
                        No student subject records available.
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="mb-5 border-b border-gray-200 pb-3">
                    <h4 class="text-lg font-semibold text-gray-800">Academic History</h4>
                    <p class="text-sm text-gray-500 mt-1">Class placement history across academic years</p>
                </div>

                @if ($student->classHistory->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Academic Year
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Enrolled
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exited
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($student->classHistory->sortByDesc(fn($history) => $history->academicYear->year_name ?? '') as $history)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $history->academicYear->year_name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $history->class->name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if (($history->status ?? 'active') === 'active') bg-blue-100 text-blue-800
                                                @elseif(($history->status ?? '') === 'promoted') bg-green-100 text-green-800
                                                @elseif(($history->status ?? '') === 'repeated') bg-amber-100 text-amber-800
                                                @elseif(($history->status ?? '') === 'graduated') bg-purple-100 text-purple-800
                                                @elseif(($history->status ?? '') === 'transferred') bg-gray-100 text-gray-800
                                                @else bg-gray-100 text-gray-700 @endif">
                                                {{ ucfirst($history->status ?? 'active') }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($history->is_current)
                                                <span
                                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Current
                                                </span>
                                            @else
                                                <span
                                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-700">
                                                    Past
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $history->enrolled_at ? \Carbon\Carbon::parse($history->enrolled_at)->format('M j, Y') : 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $history->exited_at ? \Carbon\Carbon::parse($history->exited_at)->format('M j, Y') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-gray-600">
                        No academic history available for this student.
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <form action="{{ route('admin.students.reset-password', $student) }}" method="POST"
                    onsubmit="return confirm('Reset password for this student?');">
                    @csrf
                    <button type="submit"
                        class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm transition">
                        Reset Password
                    </button>
                </form>

                <a href="{{ route('admin.students.edit', $student) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm transition">
                    Edit Student
                </a>

                <a href="{{ route('admin.students.index') }}"
                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm transition">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
