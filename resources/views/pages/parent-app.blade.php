<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Kweneng International App Downloads</title>
    <meta name="description"
        content="Download the official Kweneng International Parent App and Teacher App APK files.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
    </style>
</head>

<body class="font-sans antialiased bg-white text-slate-900 min-h-screen flex flex-col">
    @include('layouts.navigation')

    @php
        $downloadPageUrl = 'https://kwenenginternational.com/parent-app';
        $parentApkUrl = 'https://kwenenginternational.com/downloads/kweneng-parent.apk';
        $teacherApkUrl = 'https://kwenenginternational.com/downloads/kweneng-teacher.apk';
        $webLoginUrl = 'https://kwenenginternational.com/login';
    @endphp

    <main class="flex-grow pt-20 w-full bg-slate-50">
        {{-- Hero --}}
        <section class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900">
            <div class="absolute inset-0 opacity-25 pointer-events-none">
                <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-sky-400 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 h-80 w-80 rounded-full bg-emerald-400 blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    <div class="lg:col-span-7">
                        <span
                            class="inline-flex items-center rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-blue-100 ring-1 ring-white/20">
                            Official Kweneng International School Apps
                        </span>

                        <h1 class="mt-6 text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                            Parent App and Teacher App Downloads
                        </h1>

                        <p class="mt-5 text-lg md:text-xl text-blue-100 leading-8 max-w-3xl">
                            The apps are not yet on the Play Store. Please install them using the official school production links below.
                        </p>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="rounded-3xl bg-white/10 ring-1 ring-white/20 p-6 backdrop-blur-sm text-white shadow-xl">
                            <h2 class="text-xl font-bold">Parent installation message</h2>
                            <p class="mt-4 text-sm leading-7 text-blue-50">
                                Good day Parents/Guardians,<br><br>
                                The Kweneng International Parent App is now available for installation. The app is not yet on the Play Store, so please install it using the official school link below.
                            </p>
                            <div class="mt-5 rounded-2xl bg-white/10 p-4 text-sm text-blue-50 break-all">
                                {{ $downloadPageUrl }}<br>
                                {{ $parentApkUrl }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Downloads --}}
        <section class="py-14 md:py-20 w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 xl:gap-10 items-stretch">
                    {{-- Parent app --}}
                    <article class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div class="p-7 md:p-8 flex-grow">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 100-8 4 4 0 000 8zM9 10a4 4 0 100-8 4 4 0 000 8z" />
                                        </svg>
                                    </div>

                                    <h2 class="mt-5 text-2xl md:text-3xl font-bold text-slate-900">Parent App</h2>
                                    <p class="mt-3 text-slate-600 leading-7">
                                        For parents and guardians. View marks, notices, messages, homework, documents, attendance information and report absences.
                                    </p>
                                </div>

                                <span class="shrink-0 rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Parents</span>
                            </div>

                            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-700">
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Marks and reports</div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Notices and messages</div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Homework thread</div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Absence reporting</div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <a href="{{ $parentApkUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-blue-800 transition">
                                    Download Parent APK
                                </a>

                                <a href="{{ $webLoginUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                                    Use Web Login
                                </a>
                            </div>

                            <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900 leading-6">
                                Direct download link:<br>
                                <span class="font-semibold break-all">{{ $parentApkUrl }}</span>
                            </div>
                        </div>
                    </article>

                    {{-- Teacher app --}}
                    <article class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                        <div class="p-7 md:p-8 flex-grow">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118 14.5c0 2.485-2.686 4.5-6 4.5s-6-2.015-6-4.5c0-1.343.47-2.6 1.28-3.636L12 14z" />
                                        </svg>
                                    </div>

                                    <h2 class="mt-5 text-2xl md:text-3xl font-bold text-slate-900">Teacher App</h2>
                                    <p class="mt-3 text-slate-600 leading-7">
                                        For teachers. Take attendance, enter marks, send homework photos, and manage assigned learners from a mobile phone.
                                    </p>
                                </div>

                                <span class="shrink-0 rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">Teachers</span>
                            </div>

                            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-700">
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Attendance register</div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Assigned marks only</div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Homework photo upload</div>
                                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">Delete wrong uploads</div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <a href="{{ $teacherApkUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-800 transition">
                                    Download Teacher APK
                                </a>

                                <a href="{{ $webLoginUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                                    Teacher Web Login
                                </a>
                            </div>

                            <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900 leading-6">
                                Direct download link:<br>
                                <span class="font-semibold break-all">{{ $teacherApkUrl }}</span>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- Installation guide --}}
                <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
                        <h3 class="text-lg font-bold">Installation note</h3>
                        <p class="mt-2 leading-7">
                            If Android asks for permission to install from your browser or file manager, allow it for this installation and continue. These APK files are provided through the official school website.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 text-slate-700">
                        <h3 class="text-lg font-bold text-slate-900">Need the web portal?</h3>
                        <p class="mt-2 text-sm leading-6">
                            Parents and teachers may also use the web login where available.
                        </p>
                        <a href="{{ $webLoginUrl }}" class="mt-4 inline-flex rounded-xl bg-slate-800 px-5 py-3 text-sm font-bold text-white hover:bg-slate-900 transition">
                            Open Web Login
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')
</body>

</html>
