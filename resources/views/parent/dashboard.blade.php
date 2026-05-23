<x-app-layout>
    <x-slot name="header">
        <div class="mt-16">
            <h2 class="font-medium text-xl text-gray-800 leading-tight">Parent Dashboard</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Welcome bar --}}
            <div class="rounded-xl p-4 flex items-center justify-between" style="background:#2C3E6B;">
                <div>
                    <p class="font-medium text-white text-base">Welcome, {{ auth()->user()->name }}!</p>
                    <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.6);">
                        @if ($currentTerm && $currentAcademicYear)
                            {{ $currentAcademicYear->year_name }} &middot; {{ $currentTerm->name }}
                            &middot;
                            {{ $currentTerm->start_date->format('M j') }}–{{ $currentTerm->end_date->format('M j, Y') }}
                            @php
                                $daysLeft = now()->diffInDays($currentTerm->end_date, false);
                            @endphp
                            @if ($daysLeft > 0)
                                &middot;
                                <span style="color:rgba(255,255,255,0.85);">
                                    {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} remaining
                                </span>
                            @elseif($daysLeft === 0)
                                &middot; <span style="color:rgba(255,255,255,0.85);">Last day of term</span>
                            @endif
                        @else
                            No active term
                        @endif
                    </p>
                </div>
                <div class="h-9 w-9 rounded-full flex items-center justify-center font-medium text-white text-sm flex-shrink-0"
                    style="background:#3B82C4;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>

            {{-- Important Announcements Banner --}}
            @if (isset($importantAnnouncements) && $importantAnnouncements->count() > 0)
                <div class="rounded-xl border p-4 bg-gradient-to-r from-red-50 to-orange-50 border-red-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm font-bold text-red-800">IMPORTANT NOTICES</p>
                        </div>
                        <a href="{{ route('parent.announcements.index') }}"
                            class="text-xs text-red-600 hover:text-red-800 font-medium">View all →</a>
                    </div>
                    @foreach ($importantAnnouncements as $announcement)
                        <div
                            class="border-l-4 border-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-500 pl-3 py-1 bg-white rounded-r">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {!! \App\Models\Announcement::getTypeIcon($announcement->type) !!} {{ $announcement->title }}
                                    </p>
                                    <p class="text-xs text-gray-600 mt-1">{{ Str::limit($announcement->message, 80) }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                                    {{ $announcement->publish_at?->format('M j') ?? $announcement->created_at->format('M j') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Blocked notice --}}
            @if ($blockedChildren->count() > 0)
                <div class="rounded-xl border p-4 flex gap-3 items-start"
                    style="background:#fcebeb; border-color:#f7c1c1;">
                    <svg class="h-5 w-5 mt-0.5 flex-shrink-0" style="color:#a32d2d;" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <p class="text-sm" style="color:#a32d2d;">
                        @if ($blockedChildren->count() === 1)
                            <strong>{{ $blockedChildren->first()->user->name ?? 'One student' }}</strong>
                            has restricted results access due to an outstanding balance.
                        @else
                            {{ $blockedChildren->count() }} students have restricted results access due to outstanding
                            balances.
                        @endif
                        Please contact the accounts office.
                    </p>
                </div>
            @endif

            {{-- Regular Announcements --}}
            @if (isset($announcements) && $announcements->count() > 0)
                <div class="rounded-xl border p-4 bg-white border-gray-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            <p class="text-sm font-semibold text-gray-800">School Announcements</p>
                        </div>
                        <a href="{{ route('parent.announcements.index') }}"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            {{ $announcements->count() > 3 ? 'View all ' . $announcements->count() . ' →' : 'View all →' }}
                        </a>
                    </div>
                    <div class="space-y-2">
                        @foreach ($announcements->take(3) as $announcement)
                            <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                <div class="flex-shrink-0 mt-0.5">
                                    <span class="text-lg">{!! \App\Models\Announcement::getTypeIcon($announcement->type) !!}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $announcement->title }}</p>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-100 text-{{ \App\Models\Announcement::getTypeColor($announcement->type) }}-800">
                                            {{ ucfirst(str_replace('_', ' ', $announcement->type)) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ Str::limit($announcement->message, 100) }}</p>
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                                        <span>by {{ $announcement->author->name ?? 'Admin' }}</span>
                                        <span>•</span>
                                        <span>{{ $announcement->publish_at?->format('M j, Y') ?? $announcement->created_at->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Upcoming Events --}}
            @if (isset($upcomingEvents) && $upcomingEvents->count() > 0)
                <div class="rounded-xl border p-4 bg-white border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm font-semibold text-gray-800">Upcoming Events</p>
                        </div>
                        <a href="{{ route('parent.events.index') }}"
                            class="text-xs text-purple-600 hover:text-purple-800 font-medium">View calendar →</a>
                    </div>
                    <div class="space-y-3">
                        @foreach ($upcomingEvents->take(3) as $event)
                            <div
                                class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-purple-200 transition">
                                <div class="flex-shrink-0 text-center w-10">
                                    <div class="text-xs font-semibold text-purple-600">
                                        {{ $event->start_datetime->format('M') }}
                                    </div>
                                    <div class="text-lg font-bold text-gray-900 leading-none">
                                        {{ $event->start_datetime->format('d') }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $event->title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        @if ($event->is_all_day)
                                            All day
                                        @else
                                            {{ $event->start_datetime->format('g:i A') }}
                                            @if ($event->end_datetime)
                                                – {{ $event->end_datetime->format('g:i A') }}
                                            @endif
                                        @endif
                                        &middot; {{ \App\Models\Event::getTypeLabel($event->type) }}
                                    </p>
                                    @if ($event->description)
                                        <p class="text-xs text-gray-400 mt-0.5 truncate">
                                            {{ Str::limit($event->description, 60) }}
                                        </p>
                                    @endif
                                </div>
                                @php
                                    $daysUntil = now()->diffInDays($event->start_datetime, false);
                                @endphp
                                <div class="flex-shrink-0 text-right">
                                    @if ($daysUntil === 0)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Today</span>
                                    @elseif ($daysUntil === 1)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Tomorrow</span>
                                    @elseif ($daysUntil <= 7)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">In
                                            {{ $daysUntil }}d</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">In
                                            {{ $daysUntil }}d</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if ($upcomingEvents->count() > 3)
                            <a href="{{ route('parent.events.index') }}"
                                class="block text-center text-xs text-purple-600 hover:text-purple-800 font-medium pt-1">
                                + {{ $upcomingEvents->count() - 3 }} more events →
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Children cards --}}
            @if ($children->count() > 0)
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mb-3">Your children</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($children as $child)
                            @php
                                $isBlocked = $child->fees_blocked;
                                $overview = collect($marksOverview)->firstWhere('admission_no', $child->admission_no);
                                $libSummary = collect($childrenLibrarySummary)->firstWhere(
                                    'student_name',
                                    $child->user->name ?? '',
                                );
                                $initials = strtoupper(substr($child->user->name ?? 'S', 0, 1));
                                $hasMidterm = $overview && $overview['midterm_average'] !== null;
                                $hasEndterm = $overview && $overview['endterm_average'] !== null;
                            @endphp

                            <div class="bg-white rounded-xl overflow-hidden border border-gray-100">

                                {{-- Child header --}}
                                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                                    @if ($child->photo)
                                        <img class="h-9 w-9 rounded-full object-cover flex-shrink-0"
                                            src="{{ Storage::url($child->photo) }}" alt="{{ $child->user->name }}">
                                    @else
                                        <div class="h-9 w-9 rounded-full flex items-center justify-center font-medium text-sm flex-shrink-0"
                                            style="background:#D0E4F5; color:#2C3E6B;">
                                            {{ $initials }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 text-sm truncate">
                                            {{ $child->user->name ?? 'Unknown Student' }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $child->admission_no ?? 'N/A' }} &middot;
                                            {{ $child->currentClass->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                    @if ($isBlocked)
                                        <span class="text-xs font-medium px-2 py-1 rounded-full flex-shrink-0"
                                            style="background:#fcebeb; color:#a32d2d;">Blocked</span>
                                    @else
                                        <span class="text-xs font-medium px-2 py-1 rounded-full flex-shrink-0"
                                            style="background:#D0E4F5; color:#2C3E6B;">Results OK</span>
                                    @endif
                                </div>

                                <div class="px-4 py-3 space-y-3">

                                    {{-- Marks averages --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="rounded-lg p-2.5" style="background:#E8F1FA;">
                                            <p class="text-xs text-gray-400">Midterm avg</p>
                                            @if ($isBlocked)
                                                <p class="text-xs text-gray-400 mt-1">Restricted</p>
                                            @elseif ($hasMidterm)
                                                <p class="text-lg font-medium mt-0.5" style="color:#2C3E6B;">
                                                    {{ number_format($overview['midterm_average'], 1) }}
                                                </p>
                                            @else
                                                <p class="text-xs text-gray-400 mt-1">Not recorded yet</p>
                                            @endif
                                        </div>
                                        <div class="rounded-lg p-2.5" style="background:#E8F1FA;">
                                            <p class="text-xs text-gray-400">Endterm avg</p>
                                            @if ($isBlocked)
                                                <p class="text-xs text-gray-400 mt-1">Restricted</p>
                                            @elseif ($hasEndterm)
                                                <p class="text-lg font-medium mt-0.5" style="color:#2C3E6B;">
                                                    {{ number_format($overview['endterm_average'], 1) }}
                                                </p>
                                            @else
                                                <p class="text-xs text-gray-400 mt-1">Not recorded yet</p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Academic details --}}
                                    @if (!$isBlocked && $overview && $currentTerm)
                                        <div class="space-y-1.5">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-400">Position</span>
                                                <span class="font-medium text-gray-800">
                                                    @php
                                                        $position =
                                                            $overview['endterm_position']['position'] ??
                                                            ($overview['midterm_position']['position'] ?? null);
                                                        $classSize =
                                                            $overview['endterm_position']['class_size'] ??
                                                            ($overview['midterm_position']['class_size'] ?? null);
                                                    @endphp
                                                    @if ($position)
                                                        {{ $position }}/{{ $classSize }}
                                                    @else
                                                        <span class="text-gray-400 text-xs">Not available yet</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-400">Trend</span>
                                                <span class="font-medium" style="color:#3B82C4;">
                                                    @if (isset($overview['trend']) && $overview['trend'] !== 'N/A')
                                                        {{ $overview['trend'] }}
                                                    @else
                                                        <span class="text-gray-400 text-xs">Not enough data</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-400">Attendance</span>
                                                <span class="font-medium text-gray-800">
                                                    @if ($overview['attendance_rate'] !== null)
                                                        {{ $overview['attendance_rate'] }}%
                                                    @else
                                                        <span class="text-gray-400 text-xs">Not recorded yet</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-400">Behaviour</span>
                                                <span class="font-medium text-gray-800">
                                                    {{ $overview['behaviour_label'] ?? 'Good' }}
                                                    @if (isset($overview['behaviour_total']) && $overview['behaviour_total'] > 0)
                                                        ({{ $overview['behaviour_total'] }})
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @elseif ($isBlocked && $overview)
                                        <div class="space-y-1.5">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-400">Attendance</span>
                                                <span class="font-medium text-gray-800">
                                                    @if ($overview['attendance_rate'] !== null)
                                                        {{ $overview['attendance_rate'] }}%
                                                    @else
                                                        <span class="text-gray-400 text-xs">Not recorded yet</span>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-400">Behaviour</span>
                                                <span
                                                    class="font-medium text-gray-800">{{ $overview['behaviour_label'] ?? 'Good' }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Library section — always shown --}}
                                    <div class="border-t border-gray-100 pt-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-400">Library</p>
                                            @if ($libSummary)
                                                <p
                                                    class="text-xs {{ ($libSummary['overdue_books'] ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                                    {{ $libSummary['borrowed_books'] }} borrowed
                                                    @if (($libSummary['overdue_books'] ?? 0) > 0)
                                                        &middot; {{ $libSummary['overdue_books'] }} overdue
                                                    @endif
                                                </p>
                                            @else
                                                <p class="text-xs text-gray-400">0 borrowed</p>
                                            @endif
                                        </div>

                                        @if ($libSummary && $libSummary['borrowings']->count() > 0)
                                            <div class="space-y-1.5">
                                                @foreach ($libSummary['borrowings'] as $borrowing)
                                                    @php $isOverdue = $borrowing->due_at && $borrowing->due_at->isPast(); @endphp
                                                    <div class="flex items-center gap-2">
                                                        <svg class="h-4 w-4 flex-shrink-0 {{ $isOverdue ? 'text-red-400' : 'text-blue-300' }}"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                        </svg>
                                                        <span class="text-sm text-gray-700 flex-1 truncate">
                                                            {{ $borrowing->bookCopy->book->title ?? 'N/A' }}
                                                        </span>
                                                        <span
                                                            class="text-xs flex-shrink-0 {{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                                            {{ $isOverdue ? 'Overdue' : 'Due' }}
                                                            {{ optional($borrowing->due_at)->format('d M') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-400">No books currently borrowed.</p>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl border border-gray-100 p-8 text-center">
                    <p class="text-gray-400">No children linked to your account.</p>
                </div>
            @endif

            {{-- Quick access --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400 mb-3">Quick access</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

                    @if ($accessibleChildren->count() > 0)
                        <a href="{{ route('parent.children.marks.index') }}"
                            class="bg-white rounded-xl border border-gray-100 p-4 hover:border-blue-300 transition">
                            <div class="h-9 w-9 rounded-lg flex items-center justify-center mb-2"
                                style="background:#E8F1FA;">
                                <svg class="h-5 w-5" style="color:#2C3E6B;" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-800">All marks</p>
                            <p class="text-xs text-gray-400 mt-0.5">Detailed results</p>
                        </a>
                    @else
                        <div class="bg-gray-50 rounded-xl border border-gray-100 p-4 opacity-60">
                            <div class="h-9 w-9 rounded-lg flex items-center justify-center mb-2 bg-gray-200">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">All marks</p>
                            <p class="text-xs text-gray-400 mt-0.5">Unavailable</p>
                        </div>
                    @endif

                    <a href="{{ route('parent.children.library.index') }}"
                        class="bg-white rounded-xl border border-gray-100 p-4 hover:border-blue-300 transition">
                        <div class="h-9 w-9 rounded-lg flex items-center justify-center mb-2"
                            style="background:#E8F1FA;">
                            <svg class="h-5 w-5" style="color:#3B82C4;" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-800">Library</p>
                        <p class="text-xs text-gray-400 mt-0.5">Borrowed books</p>
                    </a>

                    <a href="{{ route('parent.events.index') }}"
                        class="bg-white rounded-xl border border-gray-100 p-4 hover:border-purple-300 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="h-9 w-9 rounded-lg flex items-center justify-center"
                                style="background:#F3F0FF;">
                                <svg class="h-5 w-5" style="color:#7C3AED;" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            @if (isset($upcomingEvents) && $upcomingEvents->count() > 0)
                                <span
                                    class="text-xs font-semibold px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                    {{ $upcomingEvents->count() }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-800">Events</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @if (isset($upcomingEvents) && $upcomingEvents->count() > 0)
                                {{ $upcomingEvents->count() }} upcoming
                            @else
                                Calendar & list
                            @endif
                        </p>
                    </a>

                    <a href="{{ route('parent.announcements.index') }}"
                        class="bg-white rounded-xl border border-gray-100 p-4 hover:border-blue-300 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="h-9 w-9 rounded-lg flex items-center justify-center"
                                style="background:#EFF6FF;">
                                <svg class="h-5 w-5" style="color:#2563EB;" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            </div>
                            @if (isset($announcements) && $announcements->count() > 0)
                                <span
                                    class="text-xs font-semibold px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                    {{ $announcements->count() }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-800">Announcements</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @if (isset($announcements) && $announcements->count() > 0)
                                {{ $announcements->count() }} notice{{ $announcements->count() === 1 ? '' : 's' }}
                            @else
                                School notices
                            @endif
                        </p>
                    </a>

                </div>
            </div>

            {{-- Account info --}}
            <div class="bg-white rounded-xl border border-gray-100 p-4">
                <p class="text-xs uppercase tracking-wide text-gray-400 mb-3">Account</p>
                <div class="flex flex-wrap gap-6 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs">Email</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ auth()->user()->email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Status</p>
                        <span class="inline-block mt-0.5 text-xs font-medium px-2 py-0.5 rounded-full"
                            style="background:#D0E4F5; color:#2C3E6B;">Active</span>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">Children</p>
                        <p class="font-medium text-gray-800 mt-0.5">{{ $stats['totalChildren'] }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
