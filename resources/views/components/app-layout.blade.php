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
    <body @if(request()->routeIs('admin.*')) data-admin-screen @endif class="font-sans antialiased bg-gray-100 text-gray-900 transition-colors dark:bg-brand-900 dark:text-brand-200">
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
                        'hr' => 'hr.dashboard',
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
                            <a href="{{ \App\Support\ScreenNavigation::backUrl(request()) ?? $profileBackUrl ?? $dashboardBackUrl }}"

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
                <x-operations-navigation />
            @endauth
            <main class="flex-1">
                {{ $slot }}
            </main>

            @include('layouts.dashboard-footer')
        </div>
        @stack('scripts')
    </body>
</html>
