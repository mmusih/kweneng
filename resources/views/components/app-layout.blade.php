<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'School ERP') }}</title>

        <x-theme-init />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900 transition-colors dark:bg-brand-900 dark:text-brand-200">
        <div class="min-h-screen bg-gray-100 transition-colors dark:bg-brand-900 flex flex-col">
            <!-- Page Navigation -->
            @include('layouts.navigation')

            @auth
                <div class="fixed right-4 top-20 z-30 rounded-full bg-[#124E66] px-3 py-1.5 text-xs font-bold text-white shadow-lg ring-1 ring-white/30"
                    aria-label="Current school cycle day">
                    {{ $schoolDayLabel }}
                </div>
            @endauth

            <!-- Page Heading -->
            @if (isset($header))
                <header class="mt-16 bg-white shadow transition-colors dark:bg-brand-900 dark:shadow-none">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            @auth
                @php
                    $dashboardBackRoute = match (Auth::user()->role) {
                        'admin' => 'admin.dashboard',
                        'teacher' => 'teacher.dashboard',
                        'headmaster' => 'headmaster.dashboard',
                        'librarian' => 'librarian.dashboard',
                        'accounts_officer' => 'accounts-officer.dashboard',
                        'office' => 'office.dashboard',
                        'register_officer' => 'register-officer.dashboard',
                        'inventory' => 'inventory.dashboard',
                        'student' => 'student.dashboard',
                        'parent' => 'parent.dashboard',
                        default => null,
                    };
                    $dashboardBackUrl = $dashboardBackRoute && Route::has($dashboardBackRoute)
                        ? route($dashboardBackRoute)
                        : null;
                    $showDashboardBackLink = $dashboardBackUrl
                        && ! request()->routeIs($dashboardBackRoute);
                    $profileBackUrl = $showDashboardBackLink && class_exists(\App\Support\StudentProfileNavigation::class)
                        ? \App\Support\StudentProfileNavigation::backUrl(request())
                        : null;
                @endphp

                @if ($showDashboardBackLink)
                    <nav aria-label="Backward navigation" data-back-navigation
                        class="{{ isset($header) ? 'pt-4' : 'pt-20' }} px-4 sm:px-6 lg:px-8">
                        <div class="mx-auto max-w-7xl">
                            <a href="{{ $profileBackUrl ?? $dashboardBackUrl }}"
                                @if(! $profileBackUrl) onclick="if (document.referrer) { try { if (new URL(document.referrer).origin === window.location.origin) { window.history.back(); return false; } } catch (error) {} }" @endif
                                aria-label="Go back to the previous page"
                                class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:border-brand-600 dark:bg-brand-800 dark:text-brand-100 dark:hover:bg-brand-700">
                                <span aria-hidden="true">←</span>
                                <span>Back</span>
                            </a>
                        </div>
                    </nav>
                @endif
            @endauth

            <!-- Page Content -->
            @auth
                @if(auth()->user()->canManageHr() || in_array(auth()->user()->role, array_merge(\App\Support\UserRoles::manageableStaff(), ['parent'])))
                    <nav aria-label="Staff and finance" class="px-4 py-3 {{ isset($header) ? '' : 'mt-16' }}">
                        <div class="mx-auto max-w-7xl flex flex-wrap gap-4 text-sm font-semibold">
                            @if(auth()->user()->canManageHr())<a class="underline" href="{{ route('hr.staff.index') }}">Staff & documents</a>@endif
                            @if(in_array(auth()->user()->role, ['admin','accounts_officer']))<a class="underline" href="{{ route('finance.payments.index') }}">Payments & receipts</a><a class="underline" href="{{ route('finance.accounts.index') }}">Fee ledgers</a>@endif
                            @if(auth()->user()->role==='parent')<a class="underline" href="{{ route('parent.receipts.index') }}">Payments & receipts</a>@endif
                            @if(in_array(auth()->user()->role, \App\Support\UserRoles::manageableStaff()))<a class="underline" href="{{ route('staff.leave.index') }}">My leave</a><a class="underline" href="{{ route('staff.expenses.index') }}">My expenses</a>@endif
                            @if(auth()->user()->canManageHr())<a class="underline" href="{{ route('hr.leave.index') }}">Leave approvals</a>@endif
                            @if(in_array(auth()->user()->role, ['admin','headmaster','accounts_officer']))<a class="underline" href="{{ route('finance.expenses.index') }}">Expense approvals</a>@endif
                            @if(in_array(auth()->user()->role, ['admin','headmaster','accounts_officer','office','inventory']))<a class="underline" href="{{ route('finance.purchasing.index') }}">Purchasing</a>@endif
                        </div>
                    </nav>
                @endif
            @endauth
            <main class="flex-1">
                {{ $slot }}
            </main>

            @include('layouts.dashboard-footer')
        </div>
        @stack('scripts')
    </body>
</html>
