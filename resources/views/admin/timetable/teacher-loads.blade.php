<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold">Teacher loads</h2><p class="mt-1 text-sm text-slate-500">Compare scheduled periods by teacher and cycle day.</p></x-slot>
    <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.timetable.partials.report-filters', ['report' => 'teacher-loads'])
        @include('admin.timetable.partials.report-style')
        <div class="rounded-xl border bg-white p-4 shadow-sm dark:border-brand-700 dark:bg-brand-800">
            @include('admin.timetable.partials.report-table', ['report' => 'teacher-loads'])
        </div>
    </div>
</x-app-layout>
