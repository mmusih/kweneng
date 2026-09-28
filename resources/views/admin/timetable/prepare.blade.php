<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><p class="text-sm text-slate-500 dark:text-brand-300">{{ $setting->name }} · {{ $setting->academicYear?->year_name }}</p><h1 class="text-2xl font-bold">Prepare class lessons</h1></div>
            <a href="{{ route('admin.timetable.grid', ['setting' => $setting->id]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-brand-600">Back to timetable</a>
        </div>
    </x-slot>
    @include('admin.timetable.partials.preparation-workspace')
</x-app-layout>
