<x-app-layout>
    <x-slot name="header">
        <!-- Main header block: lively warm gradient using base colors -->
        <div class="mt-16 p-6 rounded-2xl bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] shadow-md">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div class="flex items-start gap-4">
                    <!-- Dashboard icon -->
                    <div class="p-3 bg-white/10 rounded-xl text-[#D3D9D4] shrink-0">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.656 48.656 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l-3 3m3-3l3 3" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#D3D9D4]/85">Administration</p>
                        <h2 class="font-semibold text-2xl text-white leading-tight mt-1">Admin Dashboard</h2>
                        <p class="text-[#D3D9D4]/90 text-sm mt-1">
                            School operations, academic oversight, communication, and support workflows.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.students.create') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-[#212A31] hover:bg-[#D3D9D4] transition duration-200 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Student
                    </a>
                    <a href="{{ route('admin.departments.index') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 21h19.5m-18-10.5h16.5M2.25 7.5h19.5M4.5 21v-12m15 12v-12" />
                        </svg>
                        Departments / HODs
                    </a>
                    <a href="{{ route('admin.reports.index') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        Reports
                    </a>
                    <a href="{{ route('inventory.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        Inventory
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $currentYear = $activeAcademicYear ?? null;
        $canPromote = $currentYear && $currentYear->isClosed();

        $schoolAverage = $schoolOverview['schoolAverage'] ?? null;
        $bestClass = $schoolOverview['bestClass'] ?? null;
        $weakestClass = $schoolOverview['weakestClass'] ?? null;
        $topSubject = $schoolOverview['topSubject'] ?? null;
        $weakestSubject = $schoolOverview['weakestSubject'] ?? null;
        $averageMarksCompletion = $schoolOverview['averageMarksCompletion'] ?? null;
        $atRiskStudentsCount = $schoolOverview['atRiskStudentsCount'] ?? 0;
        $totalMarks = $schoolOverview['totalMarks'] ?? 0;

        $attentionItems = collect([
            [
                'active' => !$currentYear,
                'tone' => 'amber',
                'title' => 'No active academic year',
                'body' => 'Activate an academic year so academic operations can proceed normally.',
                'route' => route('admin.academic-years.index'),
                'action' => 'Open academic years',
            ],
            [
                'active' => $currentYear && !$currentTerm,
                'tone' => 'amber',
                'title' => 'No active term',
                'body' => 'An academic year is active, but no term is currently active.',
                'route' => route('admin.terms.index'),
                'action' => 'Open terms',
            ],
            [
                'active' => ($pendingInterests ?? 0) > 0,
                'tone' => 'rose',
                'title' => 'Alumni interest requests waiting',
                'body' => ($pendingInterests ?? 0) . ' request(s) need review.',
                'route' => route('admin.alumni.interests'),
                'action' => 'Review requests',
            ],
            [
                'active' => $averageMarksCompletion !== null && $averageMarksCompletion < 80,
                'tone' => 'indigo',
                'title' => 'Marks completion below target',
                'body' => 'Current average completion is ' . number_format($averageMarksCompletion ?? 0, 1) . '%.',
                'route' => route('admin.marks.index'),
                'action' => 'Open marks',
            ],
            [
                'active' => ($todayRegisterMissingCount ?? 0) > 0,
                'tone' => 'amber',
                'title' => 'Registers need attention today',
                'body' => ($todayRegisterMissingCount ?? 0) . ' class register(s) appear incomplete.',
                'route' => route('register-officer.dashboard'),
                'action' => 'Check registers',
            ],
            [
                'active' => ($schemeOverview['submitted'] ?? 0) > 0,
                'tone' => 'emerald',
                'title' => 'Schemes awaiting HOD review',
                'body' => ($schemeOverview['submitted'] ?? 0) . ' submitted scheme(s) are ready for review.',
                'route' => route('admin.schemes.index'),
                'action' => 'Open scheme review',
            ],
            [
                'active' => ($schemeOverview['behind'] ?? 0) > 0,
                'tone' => 'rose',
                'title' => 'Scheme pacing risk',
                'body' => ($schemeOverview['behind'] ?? 0) . ' scheme(s) are behind or critical.',
                'route' => route('admin.schemes.index'),
                'action' => 'Review coverage',
            ],
            [
                'active' => ($stats['totalLibrarians'] ?? 0) === 0,
                'tone' => 'emerald',
                'title' => 'No librarian accounts',
                'body' => 'Create at least one librarian account for library operations.',
                'route' => route('admin.librarians.create'),
                'action' => 'Create librarian',
            ],
        ])
            ->where('active')
            ->values();

        // High contrast tones for visibility and warmth
        $toneClasses = [
            'amber' => 'border-amber-200 bg-amber-50 text-amber-900',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
            'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-900',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-950',
            'slate' => 'border-[#D3D9D4] bg-[#D3D9D4]/10 text-[#2E3944]',
        ];

        // Action card structure containing metadata and custom icons
        $actionCards = [
            [
                'title' => 'User Management',
                'body' => 'Accounts, roles, activation, and password resets',
                'meta' => ($stats['totalUsers'] ?? 0) . ' users',
                'route' => route('admin.users.index'),
                'bg' => 'bg-sky-50 border-sky-100 hover:border-sky-300',
                'icon_color' => 'bg-sky-500/10 text-sky-600',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0112.75 21.5h-1.5a2.25 2.25 0 01-2.25-2.263V19.13m4.75-9.75a3 3 0 11-6 0 3 3 0 016 0zM12.75 21.5v-.293c0-1.163-.523-2.243-1.408-2.978a9.014 9.014 0 00-6.155-2.423M12.75 21.5H11.25m1.5 0h-1.5M11.25 21.5H9" /></svg>',
            ],
            [
                'title' => 'Students',
                'body' => 'Enrollment records, classes, and login slips',
                'meta' => ($stats['totalStudents'] ?? 0) . ' students',
                'route' => route('admin.students.index'),
                'bg' => 'bg-teal-50 border-teal-100 hover:border-teal-300',
                'icon_color' => 'bg-teal-500/10 text-teal-600',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.9c2.785 0 5.44-.233 8.025-.68a60.431 60.431 0 00-.49-6.348L12 14l-7.74-3.853zm0 0a30.17 30.17 0 012.484-4.386M12 4.25c2.974 0 5.88.22 8.725.644M12 4.25A48.566 48.566 0 003.275 4.894M12 4.25v13.5m8.725-12.856a48.554 48.554 0 01.375 7.425m-18.1 0a48.554 48.554 0 01.375-7.425M12 17.75c-2.974 0-5.88.22-8.725.644M12 17.75c2.974 0 5.88.22 8.725.644m-12 1.375h12" /></svg>',
            ],
            [
                'title' => 'Academic Staff',
                'body' => 'Teachers, headmasters, and assignments',
                'meta' => ($stats['totalTeachers'] ?? 0) . ' teachers',
                'route' => route('admin.teachers.index'),
                'bg' => 'bg-indigo-50 border-indigo-100 hover:border-indigo-300',
                'icon_color' => 'bg-indigo-500/10 text-indigo-600',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94-3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>',
            ],
            [
                'title' => 'Departments / HODs',
                'body' => 'Department membership and HOD responsibility',
                'meta' => ($departmentOverview['totalDepartments'] ?? 0) . ' depts',
                'route' => route('admin.departments.index'),
                'bg' => 'bg-amber-50 border-amber-100 hover:border-amber-300',
                'icon_color' => 'bg-amber-500/10 text-amber-600',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.203 0-4.361.147-6.478.432V21m12.956-11.25V5.25a2.25 2.25 0 00-2.25-2.25h-9a2.25 2.25 0 00-2.25 2.25v4.5" /></svg>',
            ],
            [
                'title' => 'Timetable',
                'body' => 'Cycles, periods, rooms, groups, and lesson scheduling',
                'meta' => 'School-wide planning',
                'route' => route('admin.timetable.index'),
                'bg' => 'bg-cyan-50 border-cyan-100 hover:border-cyan-300',
                'icon_color' => 'bg-cyan-500/10 text-cyan-700',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5A1.5 1.5 0 0020.25 19.5V6.75a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5V19.5a1.5 1.5 0 001.5 1.5zM7.5 13.5h3v3h-3v-3z" /></svg>',
            ],
            [
                'title' => 'Classes',
                'body' => 'Class setup, levels, teachers, and rosters',
                'meta' => ($stats['totalClasses'] ?? 0) . ' classes',
                'route' => route('admin.classes.index'),
                'bg' => 'bg-violet-50 border-violet-100 hover:border-violet-300',
                'icon_color' => 'bg-violet-500/10 text-violet-600',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375M9 18h3.375m7.5-13.5v15c0 .621-.504 1.125-1.125 1.125H3.375c-.621 0-1.125-.504-1.125-1.125v-15c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125zM9 8.25h.008v.008H9V8.25zm.008 2.25H9v.008h.008V10.5zm0 2.25H9v.008h.008v-.008zm0 2.25H9v.008h.008V15z" /></svg>',
            ],
            [
                'title' => 'Subjects',
                'body' => 'Subject setup and class/teacher links',
                'meta' => ($subjectOverview['activeSubjects'] ?? 0) . ' active',
                'route' => route('admin.subjects.index'),
                'bg' => 'bg-rose-50 border-rose-100 hover:border-rose-300',
                'icon_color' => 'bg-rose-500/10 text-rose-600',
                'icon' =>
                    '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>',
            ],
        ];

        $operationsCards = [
            [
                'title' => 'Office',
                'body' => 'Profiles, messages, reports, and notices',
                'meta' => ($communicationsOverview['pendingAbsenceNotices'] ?? 0) . ' absence notices',
                'route' => route('office.dashboard'),
                'bg' => 'bg-sky-50 hover:bg-sky-100/60 border-sky-100',
                'tag_color' => 'bg-sky-100 text-sky-800',
            ],
            [
                'title' => 'Register Monitor',
                'body' => 'Daily register completion and exports',
                'meta' => ($todayRegisterMissingCount ?? 0) . ' incomplete today',
                'route' => route('register-officer.dashboard'),
                'bg' => 'bg-amber-50 hover:bg-amber-100/60 border-amber-100',
                'tag_color' => 'bg-amber-100 text-amber-800',
            ],
            [
                'title' => 'Inventory',
                'body' => 'Items, stock levels, repairs, and procurement',
                'meta' => ($inventoryOverview['attentionCount'] ?? 0) . ' need attention',
                'route' => route('inventory.dashboard'),
                'bg' => 'bg-teal-50 hover:bg-teal-100/60 border-teal-100',
                'tag_color' => 'bg-teal-100 text-teal-800',
            ],
            [
                'title' => 'Requisitions',
                'body' => 'Teacher requests and fulfillment workflow',
                'meta' =>
                    ($inventoryOverview['newRequisitionCount'] ?? 0) .
                    ' new, ' .
                    ($inventoryOverview['openRequisitionCount'] ?? 0) .
                    ' open',
                'route' => route('inventory.requisitions.index'),
                'bg' => 'bg-violet-50 hover:bg-violet-100/60 border-violet-100',
                'tag_color' => 'bg-violet-100 text-violet-800',
            ],
        ];
    @endphp

    <div class="py-8 bg-[#D3D9D4]/15 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- 1. Stats Grid: Warm and colorful markers -->
            <section>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 bg-indigo-50 text-[#124E66] rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-[#748D92]">Academic Year</p>
                            <h3 class="mt-1 text-2xl font-bold text-[#212A31]">{{ $currentYear?->year_name ?? 'N/A' }}
                            </h3>
                            <p class="text-xs font-semibold text-indigo-600 mt-0.5">
                                {{ $currentYear ? ucfirst($currentYear->status) : 'No active academic year' }}</p>
                        </div>
                    </div>

                    <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-[#748D92]">Current Term</p>
                            <h3 class="mt-1 text-2xl font-bold text-[#212A31]">{{ $currentTerm?->name ?? 'N/A' }}</h3>
                            <p class="text-xs font-semibold text-amber-600 mt-0.5">
                                {{ $currentTerm ? ucfirst($currentTerm->status) : 'No active term' }}</p>
                        </div>
                    </div>

                    <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-5 flex items-center gap-4">
                        <div class="p-3 bg-teal-50 text-teal-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-[#748D92]">School Average</p>
                            <h3 class="mt-1 text-2xl font-bold text-[#212A31]">
                                {{ $schoolAverage !== null ? number_format($schoolAverage, 2) . '%' : 'N/A' }}</h3>
                            <p class="text-xs font-semibold text-teal-600 mt-0.5">{{ $totalMarks }} mark
                                record{{ $totalMarks === 1 ? '' : 's' }}</p>
                        </div>
                    </div>

                    <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-5 flex items-center gap-4">
                        <div
                            class="p-3 rounded-xl {{ $canPromote ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.64 8.38m6.14 3.4a48.98 48.98 0 00-5.82-.48M9.63 8.38a9 9 0 015.82.48M9.63 8.38L4.05 13.96a2.4 2.4 0 00-.7 1.7v4.74a1.2 1.2 0 001.2 1.2h4.74c.64 0 1.25-.25 1.7-.7l5.58-5.58M9.63 8.38a14.98 14.98 0 01-6.16 12.12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-[#748D92]">Promotion</p>
                            <h3
                                class="mt-1 text-2xl font-bold {{ $canPromote ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $canPromote ? 'Ready' : 'Not Ready' }}
                            </h3>
                            <p
                                class="text-xs font-semibold mt-0.5 {{ $canPromote ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $canPromote ? 'Current year is closed' : 'Close the year first' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Attention Queue & Schemes of Work: Visually inviting details -->
            <section class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6">
                <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="p-2 bg-amber-50 rounded-lg text-amber-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-[#212A31]">Attention Queue</h3>
                                <p class="text-sm text-[#748D92]">Tasks requiring immediate administrative oversight.
                                </p>
                            </div>
                        </div>
                        <span
                            class="inline-flex w-fit rounded-full bg-amber-100 text-amber-800 px-3 py-1 text-xs font-bold ring-1 ring-amber-200">
                            {{ $attentionItems->count() }} action required
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($attentionItems as $item)
                            <div
                                class="rounded-lg border-l-4 p-4 shadow-sm {{ $toneClasses[$item['tone']] ?? $toneClasses['slate'] }}">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <!-- Dynamic warning indicators -->
                                        @if ($item['tone'] === 'rose' || $item['tone'] === 'amber')
                                            <span class="mt-0.5 shrink-0 text-rose-500">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="mt-0.5 shrink-0 text-[#124E66]">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @endif
                                        <div>
                                            <h4 class="font-bold">{{ $item['title'] }}</h4>
                                            <p class="text-sm opacity-90 mt-0.5">{{ $item['body'] }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ $item['route'] }}"
                                        class="inline-flex shrink-0 items-center justify-center rounded-md bg-white px-3 py-1.5 text-sm font-bold text-[#2E3944] ring-1 ring-[#748D92]/30 hover:bg-[#D3D9D4]/20 hover:text-[#124E66] transition duration-200 shadow-sm">
                                        {{ $item['action'] }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div
                                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 flex items-start gap-3">
                                <span class="text-emerald-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <div>
                                    <h4 class="font-bold">No critical alerts</h4>
                                    <p class="text-sm mt-0.5">The main administrative areas currently look healthy.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-[#212A31]">Schemes of Work</h3>
                    <p class="text-sm text-[#748D92] mt-1">Coverage and review status for the active academic year.</p>

                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-[#2E3944]">Average completion</span>
                                <span
                                    class="font-bold text-teal-600">{{ $schemeOverview['averageCompletion'] ?? 0 }}%</span>
                            </div>
                            <div class="mt-2 h-2.5 rounded-full bg-[#D3D9D4]/50 overflow-hidden">
                                <div class="h-2.5 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500"
                                    style="width: {{ min($schemeOverview['averageCompletion'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-sky-50 border border-sky-100 rounded-lg p-3">
                                <p class="text-xs font-semibold text-sky-700">Total</p>
                                <p class="text-xl font-bold text-sky-900">{{ $schemeOverview['total'] ?? 0 }}</p>
                            </div>
                            <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-3">
                                <p class="text-xs font-semibold text-emerald-700">Approved</p>
                                <p class="text-xl font-bold text-emerald-900">{{ $schemeOverview['approved'] ?? 0 }}
                                </p>
                            </div>
                            <div class="bg-amber-50 border border-amber-100 rounded-lg p-3">
                                <p class="text-xs font-semibold text-amber-700">Submitted</p>
                                <p class="text-xl font-bold text-amber-900">{{ $schemeOverview['submitted'] ?? 0 }}
                                </p>
                            </div>
                            <div class="bg-rose-50 border border-rose-100 rounded-lg p-3">
                                <p class="text-xs font-semibold text-rose-700">Behind</p>
                                <p class="text-xl font-bold text-rose-900">{{ $schemeOverview['behind'] ?? 0 }}</p>
                            </div>
                        </div>

                        <a href="{{ route('admin.schemes.index') }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-[#124E66] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#2E3944] transition duration-200 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.375M9 18h3.375m7.5-13.5v15c0 .621-.504 1.125-1.125 1.125H3.375c-.621 0-1.125-.504-1.125-1.125v-15c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125zM9 8.25h.008v.008H9V8.25zm.008 2.25H9v.008h.008V10.5zm0 2.25H9v.008h.008v-.008zm0 2.25H9v.008h.008V15z" />
                            </svg>
                            Open HOD scheme review
                        </a>
                    </div>
                </div>
            </section>

            <!-- 3. People & Academic Setup: Colored and iconified card index -->
            <section>
                <div class="flex items-end justify-between gap-4 mb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-[#212A31]">People & Academic Setup</h3>
                        <p class="text-sm text-[#748D92]">Core records and structural assignments.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($actionCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="rounded-xl border transition-all duration-200 p-5 shadow-sm block bg-white hover:shadow-md hover:border-[#124E66]/40">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="p-2.5 rounded-lg shrink-0 {{ $card['icon_color'] }}">
                                        {!! $card['icon'] !!}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31]">{{ $card['title'] }}</h4>
                                        <p class="text-sm text-[#748D92] mt-1">{{ $card['body'] }}</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full bg-[#D3D9D4]/40 text-[#2E3944] px-3 py-1 text-xs font-bold ring-1 ring-[#748D92]/20 shrink-0">{{ $card['meta'] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- 4. Academic Operations & Snapshot -->
            <section class="grid grid-cols-1 xl:grid-cols-[1.2fr_.8fr] gap-6">
                <div>
                    <div class="flex items-end justify-between gap-4 mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-[#212A31]">Academic Operations</h3>
                            <p class="text-sm text-[#748D92]">Marks, reports, summaries, years, and terms.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="{{ route('admin.academic-years.index') }}"
                            class="group bg-white border border-[#D3D9D4] hover:border-indigo-400 hover:bg-indigo-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4">
                            <div class="p-2.5 rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#212A31] group-hover:text-indigo-900">Academic Years</h4>
                                <p class="text-sm text-[#748D92] mt-1">Open, lock, and close school years.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.terms.index') }}"
                            class="group bg-white border border-[#D3D9D4] hover:border-amber-400 hover:bg-amber-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4">
                            <div class="p-2.5 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#212A31] group-hover:text-amber-900">Terms</h4>
                                <p class="text-sm text-[#748D92] mt-1">Create, activate, finalize, and lock terms.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.marks.index') }}"
                            class="group bg-white border border-[#D3D9D4] hover:border-violet-400 hover:bg-violet-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4">
                            <div class="p-2.5 rounded-lg bg-violet-50 text-violet-600 group-hover:bg-violet-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#212A31] group-hover:text-violet-900">Marks</h4>
                                <p class="text-sm text-[#748D92] mt-1">
                                    {{ $averageMarksCompletion !== null ? number_format($averageMarksCompletion, 1) . '% completion' : 'No marks completion data yet' }}
                                </p>
                            </div>
                        </a>
                        <a href="{{ route('admin.reports.index') }}"
                            class="group bg-white border border-[#D3D9D4] hover:border-sky-400 hover:bg-sky-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4">
                            <div class="p-2.5 rounded-lg bg-sky-50 text-sky-600 group-hover:bg-sky-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#212A31] group-hover:text-sky-900">Reports</h4>
                                <p class="text-sm text-[#748D92] mt-1">Generate and download report cards.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.exam-summaries.index') }}"
                            class="group bg-white border border-[#D3D9D4] hover:border-teal-400 hover:bg-teal-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4">
                            <div class="p-2.5 rounded-lg bg-teal-50 text-teal-600 group-hover:bg-teal-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#212A31] group-hover:text-teal-900">Exam Summaries</h4>
                                <p class="text-sm text-[#748D92] mt-1">Midterm and endterm summaries.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.promotions.index') }}"
                            class="group bg-white border border-[#D3D9D4] hover:border-emerald-400 hover:bg-emerald-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4">
                            <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.64 8.38m6.14 3.4a48.98 48.98 0 00-5.82-.48M9.63 8.38a9 9 0 015.82.48M9.63 8.38L4.05 13.96a2.4 2.4 0 00-.7 1.7v4.74a1.2 1.2 0 001.2 1.2h4.74c.64 0 1.25-.25 1.7-.7l5.58-5.58M9.63 8.38a14.98 14.98 0 01-6.16 12.12" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#212A31] group-hover:text-emerald-900">Promotions</h4>
                                <p class="text-sm text-[#748D92] mt-1">
                                    {{ $canPromote ? 'Ready to promote students' : 'Promotion tools and history' }}</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020 18V6a2.25 2.25 0 00-2-2H6a2.25 2.25 0 00-2 2v12a2.25 2.25 0 002 2.25z" />
                                </svg>
                            </span>
                            <h3 class="text-lg font-semibold text-[#212A31]">Performance Snapshot</h3>
                        </div>
                        <div class="mt-5 space-y-4">
                            <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                <span class="text-sm text-[#748D92]">Best class</span>
                                <span
                                    class="text-sm font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md">{{ $bestClass?->name ?? 'N/A' }}
                                    {{ $bestClass && $bestClass->average_score !== null ? '(' . number_format($bestClass->average_score, 1) . '%)' : '' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                <span class="text-sm text-[#748D92]">Weakest class</span>
                                <span
                                    class="text-sm font-bold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-md">{{ $weakestClass?->name ?? 'N/A' }}
                                    {{ $weakestClass && $weakestClass->average_score !== null ? '(' . number_format($weakestClass->average_score, 1) . '%)' : '' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                <span class="text-sm text-[#748D92]">Top subject</span>
                                <span
                                    class="text-sm font-bold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-md">{{ $topSubject?->name ?? 'N/A' }}
                                    {{ $topSubject && $topSubject->average_score !== null ? '(' . number_format($topSubject->average_score, 1) . '%)' : '' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                <span class="text-sm text-[#748D92]">Weakest subject</span>
                                <span
                                    class="text-sm font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md">{{ $weakestSubject?->name ?? 'N/A' }}
                                    {{ $weakestSubject && $weakestSubject->average_score !== null ? '(' . number_format($weakestSubject->average_score, 1) . '%)' : '' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-[#748D92]">At-risk students</span>
                                <span
                                    class="text-sm font-extrabold text-rose-600 bg-rose-50/80 border border-rose-100 px-3 py-1 rounded-full">{{ $atRiskStudentsCount }}
                                    students</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Operational Workflows: Balanced bright blocks -->
            <section>
                <div class="flex items-end justify-between gap-4 mb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-[#212A31]">Operational Workflows</h3>
                        <p class="text-sm text-[#748D92]">Office, register, inventory, and teacher requests.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    @foreach ($operationsCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="border rounded-xl transition-all duration-200 p-5 shadow-sm flex flex-col justify-between bg-white hover:shadow-md hover:border-[#124E66]/40">
                            <div>
                                <h4 class="font-bold text-[#212A31]">{{ $card['title'] }}</h4>
                                <p class="text-sm text-[#748D92] mt-1">{{ $card['body'] }}</p>
                            </div>
                            <p
                                class="mt-4 text-xs font-bold uppercase tracking-wider px-2.5 py-1 w-fit rounded {{ $card['tag_color'] }}">
                                {{ $card['meta'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- 6. Communication & Subject Overview: Highly engaging and lively sections -->
            <section class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6">
                <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                        <div class="flex items-center gap-3">
                            <span class="p-2 bg-sky-50 rounded-lg text-sky-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-[#212A31]">Communication</h3>
                                <p class="text-sm text-[#748D92]">Events, announcements, messages, and documents.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.announcements.index') }}"
                            class="text-sm font-bold text-[#124E66] hover:text-[#2E3944] hover:underline transition">Manage
                            announcements</a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <a href="{{ route('admin.events.index') }}"
                            class="bg-sky-50 border border-sky-100 hover:border-sky-300 transition p-4 rounded-xl flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-semibold text-sky-700 uppercase">Events</p>
                                <p class="mt-2 text-3xl font-extrabold text-[#212A31]">
                                    {{ $communicationsOverview['totalEvents'] ?? 0 }}</p>
                            </div>
                            <p class="text-xs text-sky-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit">
                                {{ $communicationsOverview['upcomingEvents'] ?? 0 }} upcoming</p>
                        </a>
                        <a href="{{ route('admin.events.calendar') }}"
                            class="bg-violet-50 border border-violet-100 hover:border-violet-300 transition p-4 rounded-xl flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-semibold text-violet-700 uppercase">Calendar</p>
                                <p class="mt-2 text-base font-bold text-[#212A31]">Visual Schedule</p>
                            </div>
                            <p
                                class="text-xs text-violet-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit">
                                Events & Holidays</p>
                        </a>
                        <a href="{{ route('admin.messages.index') }}"
                            class="bg-amber-50 border border-amber-100 hover:border-amber-300 transition p-4 rounded-xl flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-semibold text-amber-700 uppercase">Messages</p>
                                <p class="mt-2 text-3xl font-extrabold text-[#212A31]">{{ $unreadMessageCount }}</p>
                            </div>
                            <p class="text-xs text-amber-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit">
                                Unread from Parents</p>
                        </a>
                        <a href="{{ route('admin.documents.index') }}"
                            class="bg-emerald-50 border border-emerald-100 hover:border-emerald-300 transition p-4 rounded-xl flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-semibold text-emerald-700 uppercase">Documents</p>
                                <p class="mt-2 text-3xl font-extrabold text-[#212A31]">{{ $activeDocumentCount }}</p>
                            </div>
                            <p
                                class="text-xs text-emerald-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit">
                                Published Files</p>
                        </a>
                    </div>
                </div>

                <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-[#212A31]">Subject Overview</h3>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3">
                                <p class="text-xs text-[#748D92]">Total</p>
                                <p class="text-lg font-bold text-[#212A31]">
                                    {{ $subjectOverview['totalSubjects'] ?? 0 }}</p>
                            </div>
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3">
                                <p class="text-xs text-[#748D92]">Active</p>
                                <p class="text-lg font-bold text-[#212A31]">
                                    {{ $subjectOverview['activeSubjects'] ?? 0 }}</p>
                            </div>
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3">
                                <p class="text-xs text-[#748D92]">Core</p>
                                <p class="text-lg font-bold text-[#212A31]">
                                    {{ $subjectOverview['coreSubjects'] ?? 0 }}</p>
                            </div>
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3">
                                <p class="text-xs text-[#748D92]">Class Links</p>
                                <p class="text-lg font-bold text-[#212A31]">
                                    {{ $subjectOverview['classAssignments'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 pt-4 border-t border-[#D3D9D4]/40">
                        <a href="{{ route('admin.subjects.manage-classes') }}"
                            class="rounded-md bg-[#D3D9D4]/40 hover:bg-[#D3D9D4]/80 px-3 py-1.5 text-xs font-bold text-[#2E3944] transition duration-200">Class
                            links</a>
                        <a href="{{ route('admin.subjects.manage-teachers') }}"
                            class="rounded-md bg-[#D3D9D4]/40 hover:bg-[#D3D9D4]/80 px-3 py-1.5 text-xs font-bold text-[#2E3944] transition duration-200">Teacher
                            links</a>
                    </div>
                </div>
            </section>

            <!-- 7. Recent Alumni Interests: Clean details -->
            @if ($recentInterests->count() > 0)
                <section class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm overflow-hidden">
                    <div
                        class="p-6 border-b border-[#D3D9D4]/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="p-2 bg-rose-50 rounded-lg text-rose-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-[#212A31]">Recent Alumni Interests</h3>
                                <p class="text-sm text-[#748D92]">Newest unprocessed alumni outreach submissions.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.alumni.interests') }}"
                            class="rounded-md bg-[#124E66] px-4 py-2 text-sm font-bold text-white hover:bg-[#2E3944] transition duration-200 shadow-sm">View
                            All Requests</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#D3D9D4]/40 text-sm">
                            <thead
                                class="bg-[#D3D9D4]/25 text-left text-xs font-bold uppercase tracking-wider text-[#2E3944]">
                                <tr>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">Graduation Year</th>
                                    <th class="px-6 py-3">Submitted</th>
                                    <th class="px-6 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#D3D9D4]/40 bg-white">
                                @foreach ($recentInterests as $interest)
                                    <tr class="hover:bg-[#D3D9D4]/10">
                                        <td class="px-6 py-4 font-bold text-[#212A31]">{{ $interest->full_name }}</td>
                                        <td class="px-6 py-4 text-[#748D92]">{{ $interest->email }}</td>
                                        <td class="px-6 py-4 text-[#748D92]">{{ $interest->graduation_year }}</td>
                                        <td class="px-6 py-4 text-[#748D92]">
                                            {{ $interest->created_at->diffForHumans() }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <form action="{{ route('admin.alumni.process-interest', $interest) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="text-sm font-bold text-[#124E66] hover:text-[#2E3944] transition">Mark
                                                    processed</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <!-- 8. Classes Overview Section -->
            <section class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-violet-50 rounded-lg text-violet-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.9c2.785 0 5.44-.233 8.025-.68a60.431 60.431 0 00-.49-6.348L12 14l-7.74-3.853zm0 0a30.17 30.17 0 012.484-4.386M12 4.25c2.974 0 5.88.22 8.725.644M12 4.25A48.566 48.566 0 003.275 4.894M12 4.25v13.5m8.725-12.856a48.554 48.554 0 01.375 7.425m-18.1 0a48.554 48.554 0 01.375-7.425M12 17.75c-2.974 0-5.88.22-8.725.644M12 17.75c2.974 0 5.88.22 8.725.644m-12 1.375h12" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-[#212A31]">Classes Overview</h3>
                            <p class="text-sm text-[#748D92]">A quick scan of active sections and enrolment counts.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.classes.create') }}"
                        class="rounded-md bg-[#124E66] px-4 py-2 text-sm font-bold text-white hover:bg-[#2E3944] transition duration-200 shadow-sm">Add
                        class</a>
                </div>

                @if ($classes->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach ($classes as $class)
                            <div
                                class="bg-[#D3D9D4]/15 border border-[#748D92]/20 rounded-xl p-4 flex flex-col justify-between">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-bold text-[#212A31]">{{ $class->name }}</h4>
                                        <p class="text-sm text-[#748D92]">Level {{ $class->level }}</p>
                                    </div>
                                    <a href="{{ route('admin.classes.edit', $class) }}"
                                        class="text-xs font-bold text-[#124E66] hover:text-[#2E3944] bg-white border border-[#D3D9D4] px-2.5 py-1.5 rounded-md transition shadow-sm">Edit</a>
                                </div>
                                <p
                                    class="mt-4 text-xs font-bold text-[#2E3944] bg-white border border-[#D3D9D4]/40 w-fit px-2.5 py-1 rounded-md">
                                    {{ $class->students->count() }}
                                    student{{ $class->students->count() === 1 ? '' : 's' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900">
                        No classes found. <a href="{{ route('admin.classes.create') }}"
                            class="font-bold underline text-[#124E66]">Create your first class</a>.
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
