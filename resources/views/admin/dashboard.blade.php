<x-app-layout>
    <x-slot name="header">
        {{-- Main header block: lively warm gradient using base colors --}}
        <div class="mt-16 p-6 rounded-2xl bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] shadow-md">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-white/10 rounded-xl text-[#D3D9D4] shrink-0">
                        <x-icon name="dashboard" class="w-8 h-8" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#D3D9D4]">Administration</p>
                        <h2 class="font-semibold text-2xl text-white leading-tight mt-1">Admin Dashboard</h2>
                        <p class="text-white/95 text-sm mt-1">
                            School operations, academic oversight, communication, and support workflows.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.students.create') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-[#212A31] hover:bg-[#D3D9D4] transition duration-200 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#124E66]">
                        <x-icon name="plus" class="w-4 h-4" />
                        Add Student
                    </a>
                    <a href="{{ route('admin.departments.index') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#124E66]">
                        <x-icon name="bank" class="w-4 h-4" />
                        Departments / HODs
                    </a>
                    <a href="{{ route('admin.reports.index') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#124E66]">
                        <x-icon name="document-report" class="w-4 h-4" />
                        Reports
                    </a>
                    <a href="{{ route('inventory.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/15 transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#124E66]">
                        <x-icon name="archive" class="w-4 h-4" />
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

        // Severity ordering so the scariest items always float to the top.
        $toneOrder = ['rose' => 0, 'amber' => 1, 'indigo' => 2, 'emerald' => 3, 'slate' => 4];

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
            ->sortBy(fn($item) => $toneOrder[$item['tone']] ?? 99)
            ->values();

        // Semantic tones -> high-contrast surface classes.
        $toneClasses = [
            'amber' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/35 dark:text-amber-100',
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-700/60 dark:bg-emerald-950/35 dark:text-emerald-100',
            'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-900 dark:border-indigo-700/60 dark:bg-indigo-950/35 dark:text-indigo-100',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-700/60 dark:bg-rose-950/35 dark:text-rose-100',
            'slate' => 'border-[#D3D9D4] bg-[#D3D9D4]/10 text-[#2E3944] dark:border-brand-600 dark:bg-brand-700/60 dark:text-brand-200',
        ];

        // People & Academic Setup cards. Icons are names resolved by <x-icon>.
        // Metas here are informational counts, so they stay neutral (slate) by design.
        $actionCards = [
            [
                'title' => 'User Management',
                'body' => 'Accounts, roles, activation, and password resets',
                'meta' => ($stats['totalUsers'] ?? 0) . ' users',
                'route' => route('admin.users.index'),
                'icon' => 'users',
                'icon_class' => 'bg-sky-500/10 text-sky-600',
            ],
            [
                'title' => 'Students',
                'body' => 'Enrollment records, classes, and login slips',
                'meta' => ($stats['totalStudents'] ?? 0) . ' students',
                'route' => route('admin.students.index'),
                'icon' => 'academic-cap',
                'icon_class' => 'bg-teal-500/10 text-teal-600',
            ],
            [
                'title' => 'Academic Staff',
                'body' => 'Teachers, headmasters, and assignments',
                'meta' => ($stats['totalTeachers'] ?? 0) . ' teachers',
                'route' => route('admin.teachers.index'),
                'icon' => 'user-group',
                'icon_class' => 'bg-indigo-500/10 text-indigo-600',
            ],
            [
                'title' => 'Parents',
                'body' => 'Parent accounts, family links, and child access',
                'meta' => ($stats['totalParents'] ?? 0) . ' parents',
                'route' => route('admin.parents.index'),
                'icon' => 'heart',
                'icon_class' => 'bg-pink-500/10 text-pink-600',
            ],
            [
                'title' => 'Librarians',
                'body' => 'Library staff accounts and access',
                'meta' => ($stats['totalLibrarians'] ?? 0) . ' librarians',
                'route' => route('admin.librarians.index'),
                'icon' => 'book-open',
                'icon_class' => 'bg-orange-500/10 text-orange-600',
            ],
            [
                'title' => 'Accounts Officers',
                'body' => 'Finance staff accounts and access',
                'meta' => ($stats['totalAccountsOfficers'] ?? 0) . ' officers',
                'route' => route('admin.accounts-officers.index'),
                'icon' => 'bank',
                'icon_class' => 'bg-emerald-500/10 text-emerald-600',
            ],
            [
                'title' => 'Departments / HODs',
                'body' => 'Department membership and HOD responsibility',
                'meta' => ($departmentOverview['totalDepartments'] ?? 0) . ' depts',
                'route' => route('admin.departments.index'),
                'icon' => 'building',
                'icon_class' => 'bg-amber-500/10 text-amber-600',
            ],
            [
                'title' => 'Timetable',
                'body' => 'Cycles, periods, rooms, groups, and lesson scheduling',
                'meta' => 'School-wide planning',
                'route' => route('admin.timetable.index'),
                'icon' => 'table-cells',
                'icon_class' => 'bg-cyan-500/10 text-cyan-700',
            ],
            [
                'title' => 'Classes',
                'body' => 'Class setup, levels, teachers, and rosters',
                'meta' => ($stats['totalClasses'] ?? 0) . ' classes',
                'route' => route('admin.classes.index'),
                'icon' => 'clipboard',
                'icon_class' => 'bg-violet-500/10 text-violet-600',
            ],
            [
                'title' => 'Subjects',
                'body' => 'Subject setup and class/teacher links',
                'meta' => ($subjectOverview['activeSubjects'] ?? 0) . ' active',
                'route' => route('admin.subjects.index'),
                'icon' => 'book-open',
                'icon_class' => 'bg-rose-500/10 text-rose-600',
            ],
            [
                'title' => 'Student Subject Assignments',
                'body' => 'Assign students to subjects and maintain enrollment records',
                'meta' => ($subjectOverview['studentSubjectCount'] ?? 0) . ' records',
                'route' => route('admin.student-subjects.index'),
                'icon' => 'clipboard',
                'icon_class' => 'bg-lime-500/10 text-lime-700',
            ],
        ];

        // Operational workflow cards. Meta tone is now SEMANTIC: it only lights
        // up (amber/rose) when a number actually needs attention, otherwise neutral.
        $operationsCards = [
            [
                'title' => 'Office',
                'body' => 'Profiles, messages, reports, and notices',
                'meta' => ($communicationsOverview['pendingAbsenceNotices'] ?? 0) . ' absence notices',
                'route' => route('office.dashboard'),
                'meta_tone' => ($communicationsOverview['pendingAbsenceNotices'] ?? 0) > 0 ? 'amber' : 'slate',
            ],
            [
                'title' => 'Register Monitor',
                'body' => 'Daily register completion and exports',
                'meta' => ($todayRegisterMissingCount ?? 0) . ' incomplete today',
                'route' => route('register-officer.dashboard'),
                'meta_tone' => ($todayRegisterMissingCount ?? 0) > 0 ? 'rose' : 'emerald',
            ],
            [
                'title' => 'Inventory',
                'body' => 'Items, stock levels, repairs, and procurement',
                'meta' => ($inventoryOverview['attentionCount'] ?? 0) . ' need attention',
                'route' => route('inventory.dashboard'),
                'meta_tone' => ($inventoryOverview['attentionCount'] ?? 0) > 0 ? 'amber' : 'slate',
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
                'meta_tone' => ($inventoryOverview['newRequisitionCount'] ?? 0) > 0 ? 'amber' : 'slate',
            ],
            [
                'title' => 'Absence Notices',
                'body' => 'Review parent-submitted student absence notices',
                'meta' => ($communicationsOverview['pendingAbsenceNotices'] ?? 0) . ' pending',
                'route' => route('admin.absence-notices.index'),
                'meta_tone' => ($communicationsOverview['pendingAbsenceNotices'] ?? 0) > 0 ? 'amber' : 'emerald',
            ],
            [
                'title' => 'Activity Logs',
                'body' => 'Review login activity and important administrative actions',
                'meta' => 'Audit trail',
                'route' => route('admin.activity-logs.index'),
                'meta_tone' => 'slate',
            ],
        ];

        // Chip classes for the operations meta tags (semantic).
        $opChipTones = [
            'slate' => 'bg-[#D3D9D4]/40 text-[#2E3944] dark:bg-brand-700 dark:text-brand-200',
            'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200',
            'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-200',
            'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200',
        ];

        // Tab definitions (single source of truth: also feeds the JS validation whitelist).
        $tabs = [
            'overview' => 'Overview',
            'people' => 'People & Academic Setup',
            'academic' => 'Academic Operations',
            'workflows' => 'Operational Workflows',
            'classes' => 'Classes Overview',
            'communication' => 'Communication',
        ];

        // Counts surfaced as tab badges so admins can scan without switching tabs.
        $tabBadges = [
            'overview' => $attentionItems->count(),
            'communication' => (int) $unreadMessageCount,
        ];
    @endphp

    <div class="py-8 bg-[#D3D9D4]/15 min-h-screen dark:bg-brand-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Always-visible top strip: never buried in a tab --}}
            <section aria-label="Key status">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <x-stat-card
                        icon="calendar"
                        icon-wrap="bg-indigo-50 text-[#124E66] dark:bg-brand-600/20 dark:text-brand-200"
                        label="Academic Year"
                        :value="$currentYear?->year_name ?? 'N/A'"
                        :sub="$currentYear ? ucfirst($currentYear->status) : 'No active academic year'"
                        sub-class="text-indigo-600" />

                    <x-stat-card
                        icon="clock"
                        icon-wrap="bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300"
                        label="Current Term"
                        :value="$currentTerm?->name ?? 'N/A'"
                        :sub="$currentTerm ? ucfirst($currentTerm->status) : 'No active term'"
                        sub-class="text-amber-600 dark:text-amber-300" />

                    <x-stat-card
                        icon="badge-check"
                        icon-wrap="bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300"
                        label="School Average"
                        :value="$schoolAverage !== null ? number_format($schoolAverage, 2) . '%' : 'N/A'"
                        :sub="$totalMarks . ' mark record' . ($totalMarks === 1 ? '' : 's')"
                        sub-class="text-teal-600 dark:text-teal-300" />

                    <x-stat-card
                        icon="promotion"
                        :icon-wrap="$canPromote ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'"
                        label="Promotion"
                        :value="$canPromote ? 'Ready' : 'Not Ready'"
                        :value-class="$canPromote ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'"
                        :sub="$canPromote ? 'Current year is closed' : 'Close the year first'"
                        :sub-class="$canPromote ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'" />
                </div>
            </section>

            {{-- Tabbed content. Tab is persisted to the URL hash + localStorage. --}}
            <div x-data="adminDashboard(@js(array_keys($tabs)))" x-cloak>

                {{-- Sticky tab bar so switching tabs never needs a scroll-up first --}}
                <nav
                    class="sticky top-[67px] xl:top-0 z-20 -mx-4 px-4 sm:mx-0 sm:px-0 bg-[#D3D9D4]/80 backdrop-blur supports-[backdrop-filter]:bg-[#D3D9D4]/70 dark:bg-brand-900/90 py-2 border-b border-[#748D92]/20"
                    aria-label="Dashboard sections">
                    <div class="flex gap-1 overflow-x-auto no-scrollbar" role="tablist" aria-label="Admin sections">
                        @foreach ($tabs as $key => $label)
                            <button type="button"
                                role="tab"
                                id="tab-{{ $key }}"
                                :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                                :tabindex="tab === '{{ $key }}' ? 0 : -1"
                                aria-controls="panel-{{ $key }}"
                                @click="tab = '{{ $key }}'"
                                @keydown.arrow-right.prevent="focusRelativeTab($el, 1)"
                                @keydown.arrow-left.prevent="focusRelativeTab($el, -1)"
                                @keydown.home.prevent="focusBoundaryTab($el, 'first')"
                                @keydown.end.prevent="focusBoundaryTab($el, 'last')"
                                :class="tab === '{{ $key }}'
                                    ? 'bg-[#124E66] text-white shadow-sm'
                                    : 'bg-white text-[#2E3944] border border-[#D3D9D4] hover:bg-[#D3D9D4]/30 dark:bg-brand-800 dark:text-brand-200 dark:border-brand-600'"
                                class="shrink-0 inline-flex items-center gap-2 rounded-md px-3.5 py-2 text-sm font-bold transition duration-150 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:focus-visible:ring-offset-brand-900">
                                {{ $label }}
                                @if (($tabBadges[$key] ?? 0) > 0)
                                    <span
                                        :class="tab === '{{ $key }}' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700'"
                                        class="rounded-full px-2 py-0.5 text-xs font-extrabold">{{ $tabBadges[$key] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </nav>

                {{-- ============ OVERVIEW ============ --}}
                <div x-show="tab === 'overview'" x-cloak id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" tabindex="0" class="space-y-6 pt-6 focus:outline-none">
                    <section class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6">
                        <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 dark:bg-brand-800 dark:border-brand-600">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="p-2 bg-amber-50 rounded-lg text-amber-600">
                                        <x-icon name="bell" class="w-6 h-6" />
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Attention Queue</h3>
                                        <p class="text-sm text-[#748D92] dark:text-brand-400">Tasks requiring immediate administrative oversight.</p>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit rounded-full bg-amber-100 text-amber-800 px-3 py-1 text-xs font-bold ring-1 ring-amber-200">
                                    {{ $attentionItems->count() }} action required
                                </span>
                            </div>

                            <div class="mt-5 space-y-3">
                                @forelse ($attentionItems as $item)
                                    <div class="rounded-lg border-l-4 p-4 shadow-sm {{ $toneClasses[$item['tone']] ?? $toneClasses['slate'] }}">
                                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                            <div class="flex items-start gap-3">
                                                @if ($item['tone'] === 'rose' || $item['tone'] === 'amber')
                                                    <span class="relative mt-0.5 shrink-0 text-rose-500">
                                                        <x-icon name="warning-solid" class="w-5 h-5" />
                                                        <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                                                            <span class="absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75 animate-ping motion-reduce:hidden"></span>
                                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                                                        </span>
                                                    </span>
                                                @else
                                                    <span class="mt-0.5 shrink-0 text-[#124E66]">
                                                        <x-icon name="info-solid" class="w-5 h-5" />
                                                    </span>
                                                @endif
                                                <div>
                                                    <h4 class="font-bold">{{ $item['title'] }}</h4>
                                                    <p class="text-sm opacity-90 mt-0.5">{{ $item['body'] }}</p>
                                                </div>
                                            </div>
                                            <a href="{{ $item['route'] }}"
                                                class="inline-flex shrink-0 items-center justify-center rounded-md bg-white px-3 py-1.5 text-sm font-bold text-[#2E3944] ring-1 ring-[#748D92]/30 hover:bg-[#D3D9D4]/20 hover:text-[#124E66] transition duration-200 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66]">
                                                {{ $item['action'] }}
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 flex items-start gap-3">
                                        <span class="text-emerald-600">
                                            <x-icon name="check-solid" class="w-5 h-5" />
                                        </span>
                                        <div>
                                            <h4 class="font-bold">No critical alerts</h4>
                                            <p class="text-sm mt-0.5">The main administrative areas currently look healthy.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 dark:bg-brand-800 dark:border-brand-600">
                            <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Schemes of Work</h3>
                            <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">Coverage and review status for the active academic year.</p>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-[#2E3944] dark:text-brand-200">Average completion</span>
                                        <span class="font-bold text-teal-600">{{ $schemeOverview['averageCompletion'] ?? 0 }}%</span>
                                    </div>
                                    <div class="mt-2 h-2.5 rounded-full bg-[#D3D9D4]/50 overflow-hidden"
                                        role="progressbar"
                                        aria-label="Average scheme completion"
                                        aria-valuenow="{{ (int) ($schemeOverview['averageCompletion'] ?? 0) }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-2.5 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-500"
                                            style="width: {{ min($schemeOverview['averageCompletion'] ?? 0, 100) }}%"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-sky-50 border border-sky-100 rounded-lg p-3 dark:border-sky-800 dark:bg-sky-950/40">
                                        <p class="text-xs font-semibold text-sky-700 dark:text-sky-300">Total</p>
                                        <p class="text-xl font-bold text-sky-900 dark:text-sky-100">{{ $schemeOverview['total'] ?? 0 }}</p>
                                    </div>
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-3 dark:border-emerald-800 dark:bg-emerald-950/40">
                                        <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Approved</p>
                                        <p class="text-xl font-bold text-emerald-900 dark:text-emerald-100">{{ $schemeOverview['approved'] ?? 0 }}</p>
                                    </div>
                                    <div class="bg-amber-50 border border-amber-100 rounded-lg p-3 dark:border-amber-800 dark:bg-amber-950/40">
                                        <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">Submitted</p>
                                        <p class="text-xl font-bold text-amber-900 dark:text-amber-100">{{ $schemeOverview['submitted'] ?? 0 }}</p>
                                    </div>
                                    <div class="bg-rose-50 border border-rose-100 rounded-lg p-3 dark:border-rose-800 dark:bg-rose-950/40">
                                        <p class="text-xs font-semibold text-rose-700 dark:text-rose-300">Behind</p>
                                        <p class="text-xl font-bold text-rose-900 dark:text-rose-100">{{ $schemeOverview['behind'] ?? 0 }}</p>
                                    </div>
                                </div>

                                <a href="{{ route('admin.schemes.index') }}"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-[#124E66] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#2E3944] transition duration-200 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2">
                                    <x-icon name="clipboard" class="w-4 h-4" />
                                    Open HOD scheme review
                                </a>
                            </div>
                        </div>
                    </section>

                    @if ($recentInterests->count() > 0)
                        <section class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm overflow-hidden dark:bg-brand-800 dark:border-brand-600">
                            <div class="p-6 border-b border-[#D3D9D4]/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="p-2 bg-rose-50 rounded-lg text-rose-600">
                                        <x-icon name="heart" class="w-6 h-6" />
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Recent Alumni Interests</h3>
                                        <p class="text-sm text-[#748D92] dark:text-brand-400">Newest unprocessed alumni outreach submissions.</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.alumni.interests') }}"
                                    class="rounded-md bg-[#124E66] px-4 py-2 text-sm font-bold text-white hover:bg-[#2E3944] transition duration-200 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2">View All Requests</a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[#D3D9D4]/40 text-sm">
                                    <thead class="bg-[#D3D9D4]/25 text-left text-xs font-bold uppercase tracking-wider text-[#2E3944] dark:bg-brand-700 dark:text-brand-200">
                                        <tr>
                                            <th class="px-6 py-3">Name</th>
                                            <th class="px-6 py-3">Email</th>
                                            <th class="px-6 py-3">Graduation Year</th>
                                            <th class="px-6 py-3">Submitted</th>
                                            <th class="px-6 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#D3D9D4]/40 bg-white dark:bg-brand-800">
                                        @foreach ($recentInterests as $interest)
                                            <tr class="hover:bg-[#D3D9D4]/10 transition-colors dark:hover:bg-brand-700/40">
                                                <td class="px-6 py-4 font-bold text-[#212A31] dark:text-white">{{ $interest->full_name }}</td>
                                                <td class="px-6 py-4 text-[#748D92] dark:text-brand-400">{{ $interest->email }}</td>
                                                <td class="px-6 py-4 text-[#748D92] dark:text-brand-400">{{ $interest->graduation_year }}</td>
                                                <td class="px-6 py-4 text-[#748D92] dark:text-brand-400">{{ $interest->created_at->diffForHumans() }}</td>
                                                <td class="px-6 py-4 text-right">
                                                    <form action="{{ route('admin.alumni.process-interest', $interest) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="text-sm font-bold text-[#124E66] hover:text-[#2E3944] transition rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66]">Mark processed</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif
                </div>

                {{-- ============ PEOPLE & ACADEMIC SETUP ============ --}}
                <div x-show="tab === 'people'" x-cloak id="panel-people" role="tabpanel" aria-labelledby="tab-people" tabindex="0" class="space-y-6 pt-6 focus:outline-none">
                    <section>
                        <div class="flex items-end justify-between gap-4 mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">People &amp; Academic Setup</h3>
                                <p class="text-sm text-[#748D92] dark:text-brand-400">Core records and structural assignments.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($actionCards as $card)
                                <x-action-card
                                    :title="$card['title']"
                                    :body="$card['body']"
                                    :meta="$card['meta']"
                                    :route="$card['route']"
                                    :icon="$card['icon']"
                                    :icon-class="$card['icon_class']" />
                            @endforeach
                        </div>
                    </section>

                    <section class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 dark:bg-brand-800 dark:border-brand-600">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                            <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Subject Overview</h3>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.subjects.manage-classes') }}"
                                    class="rounded-md bg-[#D3D9D4]/40 hover:bg-[#D3D9D4]/80 px-3 py-1.5 text-xs font-bold text-[#2E3944] transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66]">Class links</a>
                                <a href="{{ route('admin.subjects.manage-teachers') }}"
                                    class="rounded-md bg-[#D3D9D4]/40 hover:bg-[#D3D9D4]/80 px-3 py-1.5 text-xs font-bold text-[#2E3944] transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66]">Teacher links</a>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3 dark:bg-brand-700/50 dark:border-brand-600">
                                <p class="text-xs text-[#748D92]">Total</p>
                                <p class="text-lg font-bold text-[#212A31] dark:text-white">{{ $subjectOverview['totalSubjects'] ?? 0 }}</p>
                            </div>
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3 dark:bg-brand-700/50 dark:border-brand-600">
                                <p class="text-xs text-[#748D92]">Active</p>
                                <p class="text-lg font-bold text-[#212A31] dark:text-white">{{ $subjectOverview['activeSubjects'] ?? 0 }}</p>
                            </div>
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3 dark:bg-brand-700/50 dark:border-brand-600">
                                <p class="text-xs text-[#748D92]">Core</p>
                                <p class="text-lg font-bold text-[#212A31] dark:text-white">{{ $subjectOverview['coreSubjects'] ?? 0 }}</p>
                            </div>
                            <div class="bg-[#D3D9D4]/25 border border-[#748D92]/20 rounded-lg p-3 dark:bg-brand-700/50 dark:border-brand-600">
                                <p class="text-xs text-[#748D92]">Class Links</p>
                                <p class="text-lg font-bold text-[#212A31] dark:text-white">{{ $subjectOverview['classAssignments'] ?? 0 }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ============ ACADEMIC OPERATIONS ============ --}}
                <div x-show="tab === 'academic'" x-cloak id="panel-academic" role="tabpanel" aria-labelledby="tab-academic" tabindex="0" class="pt-6 focus:outline-none">
                    <section class="grid grid-cols-1 xl:grid-cols-[1.2fr_.8fr] gap-6">
                        <div>
                            <div class="flex items-end justify-between gap-4 mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Academic Operations</h3>
                                    <p class="text-sm text-[#748D92] dark:text-brand-400">Marks, reports, summaries, years, and terms.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <a href="{{ route('admin.academic-years.index') }}"
                                    class="group bg-white border border-[#D3D9D4] hover:border-indigo-400 hover:bg-indigo-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:bg-brand-700/50 dark:focus-visible:ring-offset-brand-900">
                                    <div class="p-2.5 rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-300 dark:group-hover:bg-indigo-500/25">
                                        <x-icon name="calendar" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31] group-hover:text-indigo-900 dark:text-white">Academic Years</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">Open, lock, and close school years.</p>
                                    </div>
                                </a>
                                <a href="{{ route('admin.terms.index') }}"
                                    class="group bg-white border border-[#D3D9D4] hover:border-amber-400 hover:bg-amber-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:bg-brand-700/50 dark:focus-visible:ring-offset-brand-900">
                                    <div class="p-2.5 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:group-hover:bg-amber-500/25">
                                        <x-icon name="clock" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31] group-hover:text-amber-900 dark:text-white">Terms</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">Create, activate, finalize, and lock terms.</p>
                                    </div>
                                </a>
                                <a href="{{ route('admin.marks.index') }}"
                                    class="group bg-white border border-[#D3D9D4] hover:border-violet-400 hover:bg-violet-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:bg-brand-700/50 dark:focus-visible:ring-offset-brand-900">
                                    <div class="p-2.5 rounded-lg bg-violet-50 text-violet-600 group-hover:bg-violet-100 dark:bg-violet-500/15 dark:text-violet-300 dark:group-hover:bg-violet-500/25">
                                        <x-icon name="chart-bar" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31] group-hover:text-violet-900 dark:text-white">Marks</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">
                                            {{ $averageMarksCompletion !== null ? number_format($averageMarksCompletion, 1) . '% completion' : 'No marks completion data yet' }}
                                        </p>
                                    </div>
                                </a>
                                <a href="{{ route('admin.reports.index') }}"
                                    class="group bg-white border border-[#D3D9D4] hover:border-sky-400 hover:bg-sky-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:bg-brand-700/50 dark:focus-visible:ring-offset-brand-900">
                                    <div class="p-2.5 rounded-lg bg-sky-50 text-sky-600 group-hover:bg-sky-100 dark:bg-sky-500/15 dark:text-sky-300 dark:group-hover:bg-sky-500/25">
                                        <x-icon name="document-report" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31] group-hover:text-sky-900 dark:text-white">Reports</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">Generate and download report cards.</p>
                                    </div>
                                </a>
                                <a href="{{ route('admin.exam-summaries.index') }}"
                                    class="group bg-white border border-[#D3D9D4] hover:border-teal-400 hover:bg-teal-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:bg-brand-700/50 dark:focus-visible:ring-offset-brand-900">
                                    <div class="p-2.5 rounded-lg bg-teal-50 text-teal-600 group-hover:bg-teal-100 dark:bg-teal-500/15 dark:text-teal-300 dark:group-hover:bg-teal-500/25">
                                        <x-icon name="exam" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31] group-hover:text-teal-900 dark:text-white">Exam Summaries</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">Midterm and endterm summaries.</p>
                                    </div>
                                </a>
                                <a href="{{ route('admin.promotions.index') }}"
                                    class="group bg-white border border-[#D3D9D4] hover:border-emerald-400 hover:bg-emerald-50/20 transition-all duration-200 p-5 rounded-xl shadow-sm flex items-start gap-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:bg-brand-700/50 dark:focus-visible:ring-offset-brand-900">
                                    <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-300 dark:group-hover:bg-emerald-500/25">
                                        <x-icon name="promotion" class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#212A31] group-hover:text-emerald-900 dark:text-white">Promotions</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">
                                            {{ $canPromote ? 'Ready to promote students' : 'Promotion tools and history' }}
                                        </p>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 flex flex-col justify-between dark:bg-brand-800 dark:border-brand-600">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                        <x-icon name="presentation-chart" class="w-6 h-6" />
                                    </span>
                                    <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Performance Snapshot</h3>
                                </div>
                                <div class="mt-5 space-y-4">
                                    <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                        <span class="text-sm text-[#748D92] dark:text-brand-400">Best class</span>
                                        <span class="text-sm font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md dark:bg-emerald-950/50 dark:text-emerald-200">{{ $bestClass?->name ?? 'N/A' }}
                                            {{ $bestClass && $bestClass->average_score !== null ? '(' . number_format($bestClass->average_score, 1) . '%)' : '' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                        <span class="text-sm text-[#748D92] dark:text-brand-400">Weakest class</span>
                                        <span class="text-sm font-bold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-md dark:bg-rose-950/50 dark:text-rose-200">{{ $weakestClass?->name ?? 'N/A' }}
                                            {{ $weakestClass && $weakestClass->average_score !== null ? '(' . number_format($weakestClass->average_score, 1) . '%)' : '' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                        <span class="text-sm text-[#748D92] dark:text-brand-400">Top subject</span>
                                        <span class="text-sm font-bold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-md dark:bg-sky-950/50 dark:text-sky-200">{{ $topSubject?->name ?? 'N/A' }}
                                            {{ $topSubject && $topSubject->average_score !== null ? '(' . number_format($topSubject->average_score, 1) . '%)' : '' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-b border-[#D3D9D4]/50 pb-3">
                                        <span class="text-sm text-[#748D92] dark:text-brand-400">Weakest subject</span>
                                        <span class="text-sm font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md dark:bg-amber-950/50 dark:text-amber-200">{{ $weakestSubject?->name ?? 'N/A' }}
                                            {{ $weakestSubject && $weakestSubject->average_score !== null ? '(' . number_format($weakestSubject->average_score, 1) . '%)' : '' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-sm text-[#748D92] dark:text-brand-400">At-risk students</span>
                                        <span class="text-sm font-extrabold text-rose-600 bg-rose-50/80 border border-rose-100 px-3 py-1 rounded-full dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200">{{ $atRiskStudentsCount }} students</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ============ OPERATIONAL WORKFLOWS ============ --}}
                <div x-show="tab === 'workflows'" x-cloak id="panel-workflows" role="tabpanel" aria-labelledby="tab-workflows" tabindex="0" class="pt-6 focus:outline-none">
                    <section>
                        <div class="flex items-end justify-between gap-4 mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Operational Workflows</h3>
                                <p class="text-sm text-[#748D92] dark:text-brand-400">Office, register, inventory, and teacher requests.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            @foreach ($operationsCards as $card)
                                <a href="{{ $card['route'] }}"
                                    class="border border-[#D3D9D4] rounded-xl transition-all duration-200 p-5 shadow-sm flex flex-col justify-between bg-white hover:shadow-md hover:-translate-y-0.5 hover:border-[#124E66]/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 motion-reduce:transform-none">
                                    <div>
                                        <h4 class="font-bold text-[#212A31] dark:text-white">{{ $card['title'] }}</h4>
                                        <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">{{ $card['body'] }}</p>
                                    </div>
                                    <p class="mt-4 text-xs font-bold uppercase tracking-wider px-2.5 py-1 w-fit rounded {{ $opChipTones[$card['meta_tone']] ?? $opChipTones['slate'] }}">
                                        {{ $card['meta'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    </section>
                </div>

                {{-- ============ CLASSES OVERVIEW ============ --}}
                <div x-show="tab === 'classes'" x-cloak id="panel-classes" role="tabpanel" aria-labelledby="tab-classes" tabindex="0" class="pt-6 focus:outline-none">
                    <section class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 dark:bg-brand-800 dark:border-brand-600">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                            <div class="flex items-center gap-3">
                                <span class="p-2 bg-violet-50 rounded-lg text-violet-600">
                                    <x-icon name="academic-cap" class="w-6 h-6" />
                                </span>
                                <div>
                                    <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Classes Overview</h3>
                                    <p class="text-sm text-[#748D92] dark:text-brand-400">A quick scan of active sections and enrolment counts.</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.classes.create') }}"
                                class="rounded-md bg-[#124E66] px-4 py-2 text-sm font-bold text-white hover:bg-[#2E3944] transition duration-200 shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2">Add class</a>
                        </div>

                        @if ($classes->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                @foreach ($classes as $class)
                                    <div class="bg-[#D3D9D4]/15 border border-[#748D92]/20 rounded-xl p-4 flex flex-col justify-between dark:bg-brand-700/40">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h4 class="font-bold text-[#212A31] dark:text-white">{{ $class->name }}</h4>
                                                <p class="text-sm text-[#748D92] dark:text-brand-400">Level {{ $class->level }}</p>
                                            </div>
                                            <a href="{{ route('admin.classes.edit', $class) }}"
                                                class="text-xs font-bold text-[#124E66] hover:text-[#2E3944] bg-white border border-[#D3D9D4] px-2.5 py-1.5 rounded-md transition shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] dark:border-brand-600 dark:bg-brand-800 dark:text-brand-200 dark:hover:text-white">Edit</a>
                                        </div>
                                        <p class="mt-4 text-xs font-bold text-[#2E3944] bg-white border border-[#D3D9D4]/40 w-fit px-2.5 py-1 rounded-md dark:bg-brand-800 dark:text-brand-200">
                                            {{ $class->students->count() }}
                                            student{{ $class->students->count() === 1 ? '' : 's' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                                No classes found. <a href="{{ route('admin.classes.create') }}"
                                    class="font-bold underline text-[#124E66]">Create your first class</a>.
                            </div>
                        @endif
                    </section>
                </div>

                {{-- ============ COMMUNICATION ============ --}}
                <div x-show="tab === 'communication'" x-cloak id="panel-communication" role="tabpanel" aria-labelledby="tab-communication" tabindex="0" class="pt-6 focus:outline-none">
                    <section class="bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-6 dark:bg-brand-800 dark:border-brand-600">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                            <div class="flex items-center gap-3">
                                <span class="p-2 bg-sky-50 rounded-lg text-sky-600">
                                    <x-icon name="chat" class="w-6 h-6" />
                                </span>
                                <div>
                                    <h3 class="text-lg font-semibold text-[#212A31] dark:text-white">Communication</h3>
                                    <p class="text-sm text-[#748D92] dark:text-brand-400">Events, announcements, messages, and documents.</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('admin.events.create') }}"
                                    class="text-sm font-bold text-[#124E66] hover:text-[#2E3944] hover:underline transition rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] dark:text-sky-300 dark:hover:text-sky-200">Create event</a>
                                <a href="{{ route('admin.announcements.index') }}"
                                    class="text-sm font-bold text-[#124E66] hover:text-[#2E3944] hover:underline transition rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] dark:text-sky-300 dark:hover:text-sky-200">Manage announcements</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <a href="{{ route('admin.events.index') }}"
                                class="bg-sky-50 border border-sky-100 hover:border-sky-300 transition p-4 rounded-xl flex flex-col justify-between focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:border-sky-800 dark:bg-sky-950/40 dark:focus-visible:ring-offset-brand-900">
                                <div>
                                    <p class="text-xs font-semibold text-sky-700 uppercase">Events</p>
                                    <p class="mt-2 text-3xl font-extrabold text-[#212A31] dark:text-white">{{ $communicationsOverview['totalEvents'] ?? 0 }}</p>
                                </div>
                                <p class="text-xs text-sky-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit dark:bg-sky-900/70 dark:text-sky-200">
                                    {{ $communicationsOverview['upcomingEvents'] ?? 0 }} upcoming</p>
                            </a>
                            <a href="{{ route('admin.events.calendar') }}"
                                class="bg-violet-50 border border-violet-100 hover:border-violet-300 transition p-4 rounded-xl flex flex-col justify-between focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:border-violet-800 dark:bg-violet-950/40 dark:focus-visible:ring-offset-brand-900">
                                <div>
                                    <p class="text-xs font-semibold text-violet-700 uppercase">Calendar</p>
                                    <p class="mt-2 text-base font-bold text-[#212A31] dark:text-white">Visual Schedule</p>
                                </div>
                                <p class="text-xs text-violet-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit dark:bg-violet-900/70 dark:text-violet-200">
                                    Events &amp; Holidays</p>
                            </a>
                            <a href="{{ route('admin.messages.index') }}"
                                class="bg-amber-50 border border-amber-100 hover:border-amber-300 transition p-4 rounded-xl flex flex-col justify-between focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:border-amber-800 dark:bg-amber-950/40 dark:focus-visible:ring-offset-brand-900">
                                <div>
                                    <p class="text-xs font-semibold text-amber-700 uppercase">Messages</p>
                                    <p class="mt-2 text-3xl font-extrabold text-[#212A31] dark:text-white">{{ $unreadMessageCount }}</p>
                                </div>
                                <p class="text-xs text-amber-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit dark:bg-amber-900/70 dark:text-amber-200">
                                    Unread from Parents</p>
                            </a>
                            <a href="{{ route('admin.documents.index') }}"
                                class="bg-emerald-50 border border-emerald-100 hover:border-emerald-300 transition p-4 rounded-xl flex flex-col justify-between focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:border-emerald-800 dark:bg-emerald-950/40 dark:focus-visible:ring-offset-brand-900">
                                <div>
                                    <p class="text-xs font-semibold text-emerald-700 uppercase">Documents</p>
                                    <p class="mt-2 text-3xl font-extrabold text-[#212A31] dark:text-white">{{ $activeDocumentCount }}</p>
                                </div>
                                <p class="text-xs text-emerald-800 font-semibold mt-3 bg-white/60 px-2 py-0.5 rounded w-fit dark:bg-emerald-900/70 dark:text-emerald-200">
                                    Published Files</p>
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    {{-- Alpine component: persistent + validated tab state. --}}
    @push('scripts')
        <script>
            window.adminDashboard = function(validTabs) {
                return {
                    tab: 'overview',
                    syncingHistory: false,
                    historyHandler: null,

                    init() {
                        const fromHash = window.location.hash.replace('#', '');
                        let stored = null;

                        try {
                            stored = localStorage.getItem('adminTab');
                        } catch (error) {
                            // Persistence is optional when browser storage is blocked.
                        }

                        this.tab = validTabs.includes(fromHash)
                            ? fromHash
                            : (validTabs.includes(stored) ? stored : 'overview');

                        history.replaceState(null, '', '#' + this.tab);

                        this.$watch('tab', (value) => {
                            if (!validTabs.includes(value)) {
                                return;
                            }

                            try {
                                localStorage.setItem('adminTab', value);
                            } catch (error) {
                                // The URL remains the fallback source of truth.
                            }

                            if (this.syncingHistory) {
                                this.syncingHistory = false;
                                return;
                            }

                            if (window.location.hash !== '#' + value) {
                                history.pushState(null, '', '#' + value);
                            }
                        });

                        this.historyHandler = () => {
                            const historyTab = window.location.hash.replace('#', '');

                            if (validTabs.includes(historyTab) && historyTab !== this.tab) {
                                this.syncingHistory = true;
                                this.tab = historyTab;
                            }
                        };

                        window.addEventListener('popstate', this.historyHandler);
                    },

                    destroy() {
                        if (this.historyHandler) {
                            window.removeEventListener('popstate', this.historyHandler);
                        }
                    },

                    tabButtons(element) {
                        return Array.from(element.closest('[role="tablist"]').querySelectorAll('[role="tab"]'));
                    },

                    activateButton(button) {
                        button.click();
                        button.focus();
                    },

                    focusRelativeTab(element, offset) {
                        const buttons = this.tabButtons(element);
                        const currentIndex = buttons.indexOf(element);
                        const nextIndex = (currentIndex + offset + buttons.length) % buttons.length;

                        this.activateButton(buttons[nextIndex]);
                    },

                    focusBoundaryTab(element, boundary) {
                        const buttons = this.tabButtons(element);
                        this.activateButton(boundary === 'first' ? buttons[0] : buttons[buttons.length - 1]);
                    },
                };
            };
        </script>
    @endpush
</x-app-layout>
