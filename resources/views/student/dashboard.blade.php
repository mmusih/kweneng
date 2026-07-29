<x-app-layout>
    <x-slot name="header"></x-slot>

    <style>
        .sd-wrap {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            font-family: 'Figtree', sans-serif;
        }

        /* Greeting */
        .sd-greeting {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.75rem;
            padding: 1.25rem 1.5rem;
            background: #fff;
            border-radius: 14px;
            border: 0.5px solid rgba(0, 0, 0, .08);
        }

        .sd-greeting-text h1 {
            font-size: 1.375rem;
            font-weight: 700;
            color: #1a1a18;
        }

        .sd-greeting-text p {
            font-size: 0.875rem;
            color: #888780;
            margin-top: 3px;
        }

        .sd-avatar img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .12);
            flex-shrink: 0;
        }

        .sd-avatar-fallback {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #EEEDFE;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #fff;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .12);
            flex-shrink: 0;
        }

        .sd-avatar-fallback span {
            font-size: 1.375rem;
            font-weight: 700;
            color: #3C3489;
        }

        /* Alert */
        .sd-alert {
            border-radius: 10px;
            padding: 13px 18px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .sd-alert.warn {
            background: #FAEEDA;
            color: #633806;
        }

        .sd-alert.info {
            background: #E6F1FB;
            color: #0C447C;
        }

        .sd-alert.danger {
            background: #FCEBEB;
            color: #501313;
        }

        .sd-alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        /* Stat row */
        .sd-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 1.75rem;
        }

        .sd-stat {
            border-radius: 14px;
            padding: 20px 22px;
        }

        .sd-stat-label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            opacity: .75;
        }

        .sd-stat-val {
            font-size: 1.875rem;
            font-weight: 700;
            margin-top: 6px;
            line-height: 1;
        }

        .sd-stat-sub {
            font-size: 0.75rem;
            margin-top: 5px;
            opacity: .7;
        }

        .sd-stat-sub.alert {
            opacity: 1 !important;
            font-weight: 600;
            color: #791F1F !important;
        }

        .s-blue {
            background: #E6F1FB;
            color: #0C447C;
        }

        .s-purple {
            background: #EEEDFE;
            color: #3C3489;
        }

        .s-amber {
            background: #FAEEDA;
            color: #633806;
        }

        .s-teal {
            background: #E1F5EE;
            color: #085041;
        }

        /* Section header */
        .sd-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a18;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sd-section {
            margin-bottom: 2rem;
        }

        /* CTA Buttons replacing tiny links */
        .sd-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: opacity .15s;
        }

        .sd-btn:hover {
            opacity: .85;
        }

        .sd-btn-primary {
            background: #185FA5;
            color: #fff;
        }

        .sd-btn-teal {
            background: #0F6E56;
            color: #fff;
        }

        .sd-btn svg {
            width: 14px;
            height: 14px;
        }

        /* Performance cards */
        .sd-perf-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }

        .sd-perf-card {
            background: #fff;
            border: 0.5px solid rgba(0, 0, 0, .1);
            border-radius: 14px;
            padding: 18px 20px;
        }

        .sd-perf-card .pk {
            font-size: 0.6875rem;
            color: #888780;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .sd-perf-card .pv {
            font-size: 1.625rem;
            font-weight: 700;
            margin-top: 8px;
        }

        .sd-perf-card .pm {
            font-size: 0.75rem;
            color: #888780;
            margin-top: 3px;
        }

        .pv-blue {
            color: #0C447C;
        }

        .pv-green {
            color: #27500A;
        }

        .pv-purple {
            color: #3C3489;
        }

        /* Badges */
        .sd-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 0.6875rem;
            font-weight: 600;
            margin-top: 6px;
        }

        .b-green {
            background: #EAF3DE;
            color: #27500A;
        }

        .b-amber {
            background: #FAEEDA;
            color: #633806;
        }

        .b-red {
            background: #FCEBEB;
            color: #501313;
        }

        .b-blue {
            background: #E6F1FB;
            color: #0C447C;
        }

        .b-purple {
            background: #EEEDFE;
            color: #3C3489;
        }

        .b-gray {
            background: #F1EFE8;
            color: #444441;
        }

        /* Marks table */
        .sd-table-wrap {
            background: #fff;
            border: 0.5px solid rgba(0, 0, 0, .1);
            border-radius: 14px;
            overflow: hidden;
        }

        .sd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .sd-table th {
            padding: 12px 18px;
            text-align: left;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #888780;
            background: #F1EFE8;
        }

        .sd-table td {
            padding: 14px 18px;
            border-top: 0.5px solid rgba(0, 0, 0, .07);
            vertical-align: middle;
        }

        .sd-table tr:hover td {
            background: #faf9f5;
        }

        .sd-sub-name {
            font-weight: 500;
            color: #1a1a18;
        }

        .sd-sub-code {
            font-size: 0.6875rem;
            color: #888780;
        }

        .score {
            font-weight: 700;
            font-size: 0.9375rem;
        }

        .score-hi {
            color: #27500A;
        }

        .score-mid {
            color: #633806;
        }

        .score-lo {
            color: #791F1F;
        }

        .score-na {
            color: #888780;
            font-weight: 400;
        }

        .sd-teacher {
            color: #888780;
        }

        /* Library */
        .sd-lib-wrap {
            background: #fff;
            border: 0.5px solid rgba(0, 0, 0, .1);
            border-radius: 14px;
            overflow: hidden;
        }

        .sd-lib-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px 18px;
            border-top: 0.5px solid rgba(0, 0, 0, .07);
            font-size: 0.875rem;
        }

        .sd-lib-row:first-child {
            border-top: none;
        }

        .sd-lib-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #E1F5EE;
            color: #085041;
            flex-shrink: 0;
        }

        .sd-lib-icon svg {
            width: 19px;
            height: 19px;
        }

        .sd-lib-meta {
            flex: 1;
            min-width: 0;
        }

        .sd-lib-meta .t {
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #1a1a18;
        }

        .sd-lib-meta .s {
            font-size: 0.75rem;
            color: #888780;
            margin-top: 1px;
        }

        .sd-lib-status {
            text-align: right;
        }

        .sd-lib-due {
            font-size: 0.75rem;
            color: #888780;
            margin-top: 4px;
            white-space: nowrap;
        }

        /* Student life */
        .sd-life-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }

        .sd-life-card {
            background: #fff;
            border: 0.5px solid rgba(0, 0, 0, .1);
            border-radius: 14px;
            padding: 18px 20px;
        }

        .sd-life-card h3 {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #888780;
            margin-bottom: 12px;
        }

        .sd-life-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            padding: 4px 0;
        }

        .sd-life-row span:last-child {
            font-weight: 600;
        }

        .sd-bar-wrap {
            height: 6px;
            background: #F1EFE8;
            border-radius: 99px;
            margin-top: 10px;
            overflow: hidden;
        }

        .sd-bar-fill {
            height: 100%;
            border-radius: 99px;
        }

        /* Empty state */
        .sd-empty {
            background: #F1EFE8;
            border-radius: 14px;
            padding: 2.5rem;
            text-align: center;
        }

        .sd-empty svg {
            width: 40px;
            height: 40px;
            color: #B4B2A9;
            margin: 0 auto;
        }

        .sd-empty p {
            font-size: 0.875rem;
            color: #888780;
            margin-top: 8px;
        }

        @media (max-width: 640px) {
            .sd-stat-val {
                font-size: 1.5rem;
            }

            .sd-table th:nth-child(4),
            .sd-table td:nth-child(4) {
                display: none;
            }

            .sd-btn span {
                display: none;
            }
        }
    </style>

    <div class="sd-wrap">

        {{-- ───── GREETING ───── --}}
        <div class="sd-greeting">
            <div class="sd-avatar">
                @if ($student && $student->photo)
                    <img src="{{ Storage::url($student->photo) }}" alt="{{ auth()->user()->name }}">
                @else
                    <div class="sd-avatar-fallback">
                        <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="sd-greeting-text">
                <h1>Hello, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
                <p>
                    {{ $student?->currentClass?->name ?? 'No class assigned' }}
                    @if ($currentAcademicYear)
                        · {{ $currentAcademicYear->year_name }}
                    @endif
                    @if ($currentTerm)
                        · {{ $currentTerm->name }}
                    @endif
                </p>
            </div>
        </div>

        @if (!$student)

            {{-- ───── NO STUDENT RECORD ───── --}}
            <div class="sd-alert warn">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                Your student record could not be found. Please contact the school administration.
            </div>
        @else
            {{-- ───── ALERTS ───── --}}
            @if (($overdueBorrowingsCount ?? 0) > 0)
                <div class="sd-alert danger">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    You have <strong>{{ $overdueBorrowingsCount }} overdue
                        {{ Str::plural('book', $overdueBorrowingsCount) }}</strong> — please return
                    {{ $overdueBorrowingsCount === 1 ? 'it' : 'them' }} to the library.
                </div>
            @endif

            @if ($stats['feesBlocked'] ?? false)
                <div class="sd-alert warn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    Your results access is <strong>blocked</strong>. Please contact the accounts office.
                </div>
            @endif

            @if (!$currentTerm)
                <div class="sd-alert info">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z" />
                    </svg>
                    There is no active term at the moment. Marks will appear once a term is activated.
                </div>
            @endif

            {{-- ───── STAT ROW ───── --}}
            <div class="sd-stats">
                <div class="sd-stat s-blue">
                    <div class="sd-stat-label">Class</div>
                    <div class="sd-stat-val">{{ $student->currentClass?->name ?? '—' }}</div>
                    <div class="sd-stat-sub">{{ $currentAcademicYear?->year_name ?? 'No year set' }}</div>
                </div>
                <div class="sd-stat s-purple">
                    <div class="sd-stat-label">Term</div>
                    <div class="sd-stat-val">{{ $currentTerm?->name ?? '—' }}</div>
                    <div class="sd-stat-sub">
                        @if ($currentTerm)
                            {{ $currentTerm->start_date->format('d M') }} –
                            {{ $currentTerm->end_date->format('d M') }}
                        @else
                            No active term
                        @endif
                    </div>
                </div>
                <div class="sd-stat s-amber">
                    <div class="sd-stat-label">Subjects</div>
                    <div class="sd-stat-val">{{ $stats['subjectsAssigned'] ?? 0 }}</div>
                    <div class="sd-stat-sub">{{ $stats['subjectsWithMarks'] ?? 0 }} with marks</div>
                </div>
                <div class="sd-stat s-teal">
                    <div class="sd-stat-label">Books out</div>
                    <div class="sd-stat-val">{{ $borrowingsCount ?? 0 }}</div>
                    @if (($overdueBorrowingsCount ?? 0) > 0)
                        <div class="sd-stat-sub alert">{{ $overdueBorrowingsCount }} overdue</div>
                    @else
                        <div class="sd-stat-sub">none overdue</div>
                    @endif
                </div>
            </div>

            <a href="{{ route('student.timetable') }}"
                class="flex items-center justify-between rounded-xl border border-sky-200 bg-sky-50 px-5 py-4 transition hover:border-sky-400">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-sky-700">Timetable</div>
                    <div class="mt-1 font-semibold text-sky-950">See today’s lessons and your full week</div>
                </div>
                <svg class="h-6 w-6 text-sky-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5A1.5 1.5 0 0020.25 19.5V6.75a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5V19.5a1.5 1.5 0 001.5 1.5z" />
                </svg>
            </a>

            {{-- ───── PERFORMANCE ───── --}}
            <div class="sd-section">
                <div class="sd-section-title">
                    Performance
                    <a href="{{ route('student.marks.index') }}" class="sd-btn sd-btn-primary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <span>View full marks</span>
                    </a>
                </div>
                <div class="sd-perf-grid">
                    <div class="sd-perf-card">
                        <div class="pk">Midterm avg</div>
                        <div class="pv pv-blue">
                            {{ ($stats['midtermAverage'] ?? null) !== null ? number_format($stats['midtermAverage'], 1) : 'N/A' }}
                        </div>
                        <div class="pm">Across {{ $stats['subjectsWithMarks'] ?? 0 }} subjects</div>
                    </div>
                    <div class="sd-perf-card">
                        <div class="pk">Endterm avg</div>
                        <div class="pv pv-green">
                            {{ ($stats['endtermAverage'] ?? null) !== null ? number_format($stats['endtermAverage'], 1) : 'N/A' }}
                        </div>
                        <div class="pm">Across {{ $stats['subjectsWithMarks'] ?? 0 }} subjects</div>
                        @if (($performance['trend'] ?? null) === 'Improving')
                            <span class="sd-badge b-green">↑ Improving</span>
                        @elseif (($performance['trend'] ?? null) === 'Declining')
                            <span class="sd-badge b-red">↓ Declining</span>
                        @elseif ($performance['trend'] ?? null)
                            <span class="sd-badge b-gray">{{ $performance['trend'] }}</span>
                        @endif
                    </div>
                    @if ($performance ?? null)
                        <div class="sd-perf-card">
                            <div class="pk">Class position</div>
                            <div class="pv pv-purple">
                                @if ($performance['endterm_position']['position'] ?? null)
                                    {{ $performance['endterm_position']['position'] }}/{{ $performance['endterm_position']['class_size'] }}
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="pm">Endterm standing</div>
                            @if ($performance['performance_label'] ?? null)
                                <span class="sd-badge b-purple">{{ $performance['performance_label'] }}</span>
                            @endif
                        </div>
                    @endif
                    <div class="sd-perf-card">
                        <div class="pk">Results access</div>
                        @if ($stats['feesBlocked'] ?? false)
                            <div class="pv" style="color:#791F1F;font-size:1.125rem;margin-top:10px">Blocked</div>
                            <span class="sd-badge b-red">Contact accounts</span>
                        @else
                            <div class="pv" style="color:#27500A;font-size:1.125rem;margin-top:10px">Allowed</div>
                            <span class="sd-badge b-green">Fees clear</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ───── MARKS TABLE ───── --}}
            <div class="sd-section">
                <div class="sd-section-title">Latest marks</div>
                @if ($currentTerm && $latestMarks->count() > 0)
                    <div class="sd-table-wrap">
                        <table class="sd-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Midterm</th>
                                    <th>Endterm</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestMarks as $mark)
                                    @php
                                        $mid = $mark->midterm_score;
                                        $end = $mark->endterm_score;
                                        $midClass = is_numeric($mid)
                                            ? ($mid >= 70
                                                ? 'score-hi'
                                                : ($mid >= 50
                                                    ? 'score-mid'
                                                    : 'score-lo'))
                                            : 'score-na';
                                        $endClass = is_numeric($end)
                                            ? ($end >= 70
                                                ? 'score-hi'
                                                : ($end >= 50
                                                    ? 'score-mid'
                                                    : 'score-lo'))
                                            : 'score-na';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="sd-sub-name">{{ $mark->subject->name ?? 'Unknown' }}</div>
                                            <div class="sd-sub-code">{{ $mark->subject->code ?? '' }}</div>
                                        </td>
                                        <td><span class="score {{ $midClass }}">{{ $mid ?? 'N/A' }}</span></td>
                                        <td><span class="score {{ $endClass }}">{{ $end ?? 'N/A' }}</span></td>
                                        <td class="sd-teacher">{{ $mark->teacher?->user?->name ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="sd-empty">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p>{{ $currentTerm ? 'No marks have been entered yet for this term.' : 'No active term — marks will appear here once a term is activated.' }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- ───── LIBRARY ───── --}}
            <div class="sd-section">
                <div class="sd-section-title">
                    Library
                    <a href="{{ route('student.library.index') }}" class="sd-btn sd-btn-teal">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>View all books</span>
                    </a>
                </div>
                @if (($borrowings ?? collect())->count() > 0)
                    <div class="sd-lib-wrap">
                        @foreach ($borrowings as $borrowing)
                            @php
                                $isReturned = !is_null($borrowing->returned_at);
                                $isOverdue =
                                    !$isReturned &&
                                    !empty($borrowing->due_at) &&
                                    \Carbon\Carbon::parse($borrowing->due_at)->isPast();
                            @endphp
                            <div class="sd-lib-row">
                                <div class="sd-lib-icon">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div class="sd-lib-meta">
                                    <div class="t">{{ $borrowing->bookCopy->book->title ?? 'N/A' }}</div>
                                    <div class="s">
                                        {{ $borrowing->bookCopy->book->author ?? '' }}{{ ($borrowing->bookCopy->book->author ?? null) && ($borrowing->bookCopy->barcode ?? null) ? ' · ' : '' }}{{ $borrowing->bookCopy->barcode ?? '' }}
                                    </div>
                                </div>
                                <div class="sd-lib-status">
                                    @if ($isReturned)
                                        <span class="sd-badge b-gray">Returned</span>
                                        <div class="sd-lib-due">
                                            {{ \Carbon\Carbon::parse($borrowing->returned_at)->format('d M Y') }}</div>
                                    @elseif ($isOverdue)
                                        <span class="sd-badge b-red">Overdue</span>
                                        <div class="sd-lib-due">Was due
                                            {{ \Carbon\Carbon::parse($borrowing->due_at)->format('d M Y') }}</div>
                                    @else
                                        <span class="sd-badge b-green">Borrowed</span>
                                        @if ($borrowing->due_at)
                                            <div class="sd-lib-due">Due
                                                {{ \Carbon\Carbon::parse($borrowing->due_at)->format('d M Y') }}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sd-empty">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <p>No books currently issued. Your borrowed books will appear here.</p>
                    </div>
                @endif
            </div>

            {{-- ───── STUDENT LIFE ───── --}}
            @if ($attendanceSummary || $punctualitySummary || $behaviourSummary)
                <div class="sd-section">
                    <div class="sd-section-title">Student life</div>
                    <div class="sd-life-grid">

                        @if ($attendanceSummary)
                            <div class="sd-life-card">
                                <h3>Attendance</h3>
                                <div class="sd-life-row"><span>Present</span><span
                                        style="color:#27500A">{{ $attendanceSummary['present'] }}</span></div>
                                <div class="sd-life-row"><span>Absent</span><span
                                        style="color:#791F1F">{{ $attendanceSummary['absent'] }}</span></div>
                                <div class="sd-life-row"><span>Late</span><span
                                        style="color:#633806">{{ $attendanceSummary['late'] }}</span></div>
                                <div class="sd-life-row"><span>Excused</span><span
                                        style="color:#888780">{{ $attendanceSummary['excused'] }}</span></div>
                                @if ($attendanceSummary['rate'] !== null)
                                    <div class="sd-bar-wrap">
                                        <div class="sd-bar-fill"
                                            style="width:{{ min($attendanceSummary['rate'], 100) }}%; background:#3B6D11;">
                                        </div>
                                    </div>
                                    <div style="font-size:.6875rem;color:#888780;margin-top:5px">
                                        {{ $attendanceSummary['rate'] }}% attendance rate</div>
                                @endif
                            </div>
                        @endif

                        @if ($punctualitySummary)
                            <div class="sd-life-card">
                                <h3>Punctuality</h3>
                                <div class="sd-life-row"><span>On time</span><span
                                        style="color:#27500A">{{ $punctualitySummary['on_time'] }}</span></div>
                                <div class="sd-life-row"><span>Late</span><span
                                        style="color:#633806">{{ $punctualitySummary['late'] }}</span></div>
                                <div class="sd-life-row"><span>Very late</span><span
                                        style="color:#791F1F">{{ $punctualitySummary['very_late'] }}</span></div>
                                <div class="sd-life-row"><span>Absent</span><span
                                        style="color:#888780">{{ $punctualitySummary['absent'] }}</span></div>
                                @php
                                    $pTotal =
                                        $punctualitySummary['on_time'] +
                                        $punctualitySummary['late'] +
                                        $punctualitySummary['very_late'] +
                                        $punctualitySummary['absent'];
                                    $pRate = $pTotal > 0 ? round(($punctualitySummary['on_time'] / $pTotal) * 100) : 0;
                                @endphp
                                <div class="sd-bar-wrap">
                                    <div class="sd-bar-fill" style="width:{{ $pRate }}%; background:#854F0B;">
                                    </div>
                                </div>
                                <div style="font-size:.6875rem;color:#888780;margin-top:5px">{{ $pRate }}%
                                    on-time rate</div>
                            </div>
                        @endif

                        @if ($behaviourSummary)
                            <div class="sd-life-card">
                                <h3>Behaviour</h3>
                                <div class="sd-life-row"><span>Total records</span><span
                                        style="color:#2C2C2A">{{ $behaviourSummary['total'] }}</span></div>
                                <div class="sd-life-row"><span>Minor</span><span
                                        style="color:#633806">{{ $behaviourSummary['minor'] }}</span></div>
                                <div class="sd-life-row"><span>Moderate</span><span
                                        style="color:#E24B4A">{{ $behaviourSummary['moderate'] }}</span></div>
                                <div class="sd-life-row"><span>Major</span><span
                                        style="color:#791F1F">{{ $behaviourSummary['major'] }}</span></div>
                                @if ($behaviourSummary['label'] ?? null)
                                    <span
                                        class="sd-badge {{ $behaviourSummary['total'] == 0 ? 'b-green' : ($behaviourSummary['major'] > 0 ? 'b-red' : 'b-amber') }}"
                                        style="margin-top:10px">
                                        {{ $behaviourSummary['label'] }}
                                    </span>
                                @endif
                                @if ($behaviourSummary['latest'] ?? null)
                                    <div style="font-size:.6875rem;color:#888780;margin-top:6px">
                                        Latest: {{ ucfirst($behaviourSummary['latest']->category) }} –
                                        {{ ucfirst($behaviourSummary['latest']->severity) }}
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                </div>
            @endif

        @endif
    </div>
</x-app-layout>
