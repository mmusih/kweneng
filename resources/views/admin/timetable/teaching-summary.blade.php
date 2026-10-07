<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold">Teaching summary</h2><p class="mt-1 text-sm text-slate-500">Subjects, classes and groups, organised by teacher with daily and cycle totals.</p></x-slot>
    <div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.timetable.partials.report-filters', ['report' => 'teaching-summary'])
        @include('admin.timetable.partials.report-style')
        <div class="rounded-xl border bg-white p-4 shadow-sm dark:border-brand-700 dark:bg-brand-800">
            @include('admin.timetable.partials.report-table', ['report' => 'teaching-summary'])
        </div>
    </div>
</x-app-layout>
