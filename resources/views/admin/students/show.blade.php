<x-app-layout>
    <x-slot name="header">
        <div
            class="p-6 bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] text-white rounded-xl shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Student Profile
                </h2>
                <p class="text-white/80 text-sm mt-1">
                    Full student overview, academic placement, and access status
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.students.index', request()->only(['search', 'class_id', 'page'])) }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition text-sm font-medium">
                    ← Back to List
                </a>

                <a href="{{ route('admin.students.edit', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))) }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-white text-indigo-700 hover:bg-blue-50 transition text-sm font-semibold">
                    Edit Student
                </a>
                <a href="{{ route('admin.students.academic-record.show', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))) }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition text-sm font-semibold">
                    Academic Record
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $studentName = $student->user->name ?? 'N/A';
        $studentEmail = $student->user->email ?? 'N/A';

        $currentClass = $student->currentClass;
        $currentAcademicYear = $currentClass?->academicYear?->year_name;

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

    @endphp

    <div class="py-10">
        <div class="student-profile max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            @include('admin.students.partials.profile-summary')

            <nav class="student-profile-nav" aria-label="Student profile sections">
                <a href="#personal-details">Personal &amp; emergency</a>
                <a href="#family-enrollment">Family &amp; enrollment</a>
                <a href="#academic-progress">Academic progress</a>
                <a href="#class-history">Class history</a>
                <a href="#achievements">Achievements</a>
            </nav>

            <div id="personal-details" class="profile-section grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-5 border-b border-gray-200 pb-3">
                        <h4 class="text-lg font-semibold text-gray-800">Identity Information</h4>
                        <p class="text-sm text-gray-500 mt-1">Nationality and official document details</p>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Nationality</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->nationality ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Document Type</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->identity_document_type ? $student->identityDocumentLabel() : 'Not provided' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Document Number</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->identity_document_number ?: 'Not provided' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="mb-5 border-b border-gray-200 pb-3">
                        <h4 class="text-lg font-semibold text-gray-800">Emergency Contact</h4>
                        <p class="text-sm text-gray-500 mt-1">Contact details to use during emergencies</p>
                    </div>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Name</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->emergency_contact_name ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Relationship</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->emergency_contact_relationship ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Primary Phone</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->emergency_contact_phone ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Alternative Phone</dt>
                            <dd class="mt-1 font-semibold text-gray-900">{{ $student->emergency_contact_alt_phone ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Emergency address</dt>
                            <dd class="mt-1 font-semibold whitespace-pre-line">{{ $student->emergency_contact_address ?: 'Not provided' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Medical Notes / Allergies</dt>
                            <dd class="mt-1 font-semibold text-gray-900 whitespace-pre-line">{{ $student->medical_notes ?: 'None recorded' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div id="family-enrollment" class="profile-section grid grid-cols-1 xl:grid-cols-3 gap-6">
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

            <div id="academic-progress" class="profile-section space-y-6">
                @include('students.profile-performance')
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

            <div id="class-history" class="profile-section bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
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

            <div id="achievements" class="profile-section rounded-2xl border border-amber-200 bg-amber-50/60 p-6 dark:border-amber-800 dark:bg-amber-950/20">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Awards &amp; Achievements</h3>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse($student->awards as $award)
                        <div class="rounded-xl border border-amber-200 bg-white p-4 dark:border-amber-800 dark:bg-brand-800">
                            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-full font-bold text-white" style="background:{{ $award->run->category?->badge_color ?? '#D4AF37' }}">★</span><div><div class="font-bold">{{ $award->award_title }}</div><div class="text-xs text-slate-500">{{ $award->run->academicYear?->year_name }}{{ $award->run->term ? ' · '.$award->run->term->name : '' }}</div></div></div>
                            @if($award->position || $award->main_score !== null)<div class="mt-2 text-sm font-semibold text-amber-700">@if($award->position)Position {{ $award->position }}@endif @if($award->main_score !== null) · {{ $award->main_score }}%@endif</div>@endif
                            <a target="_blank" class="mt-2 inline-block text-sm font-semibold text-sky-700" href="{{ route('admin.awards.certificate', [$award->run, $award]) }}">Print certificate</a>
                        </div>
                    @empty<p class="text-sm text-slate-500">No published awards recorded.</p>@endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-violet-200 bg-violet-50/60 p-6 dark:border-violet-800 dark:bg-violet-950/20">
                <div class="flex items-center justify-between gap-3"><h3 class="text-lg font-bold text-slate-900 dark:text-white">Prefect Leadership</h3><a href="{{ route('admin.prefects.create', ['student_id' => $student->id]) }}" class="text-sm font-semibold text-violet-700">Appoint as prefect</a></div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">@forelse($student->prefectAppointments as $prefect)<div class="rounded-xl border border-violet-200 bg-white p-4 dark:border-violet-800 dark:bg-brand-800"><div class="flex items-center justify-between"><strong>{{ $prefect->title }}</strong><span class="text-xs font-bold uppercase text-violet-700">{{ $prefect->status }}</span></div><div class="mt-1 text-xs text-slate-500">{{ $prefect->academicYear?->year_name }} · Appointed {{ $prefect->appointed_on?->format('j M Y') }}</div><p class="mt-2 whitespace-pre-line text-sm text-slate-600 dark:text-brand-200">{{ $prefect->duties }}</p><div class="mt-3 flex gap-3"><a class="text-sm font-semibold text-amber-700" href="{{ route('admin.prefects.certificate', $prefect) }}">Certificate</a><a class="text-sm font-semibold text-sky-700" href="{{ route('admin.prefects.edit', $prefect) }}">Edit</a></div></div>@empty<p class="text-sm text-slate-500">No prefect appointments recorded.</p>@endforelse</div>
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

                <a href="{{ route('admin.students.edit', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id']))) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm transition">
                    Edit Student
                </a>

                <a href="{{ route('admin.students.index', request()->only(['search', 'class_id', 'page'])) }}"
                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2.5 px-5 rounded-lg shadow-sm transition">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
