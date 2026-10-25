<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold">Classes &amp; new year</h2>
            <a href="{{ route('admin.timetable.index', ['setting' => $setting->id]) }}" class="text-sm underline">Back to timetable</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 text-slate-800 dark:text-brand-100">
        <p>{{ $setting->name }} · {{ $setting->academicYear?->year_name }} · {{ $setting->typeLabel() }}</p>
        @if(session('success'))
            <div role="status" class="rounded-lg bg-emerald-50 p-4 text-emerald-900">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-lg bg-rose-50 p-4 text-rose-900">
                <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-brand-700 dark:bg-brand-800">
            <h3 class="text-lg font-semibold">Temporarily remove classes</h3>
            <p class="mt-2 text-sm">Tick classes that have stopped attending lessons, for example during final exams. Their saved lessons and placements are kept. Untick them to restore teaching.</p>
            <p class="mt-2 text-sm">This applies to this schedule only. Shared lessons continue for the remaining classes. @if($setting->is_published) Changes take effect immediately in published timetables and teacher loads. @endif</p>
            <form method="POST" action="{{ route('admin.timetable.classes.update', $setting) }}" class="mt-4">
                @csrf
                <input type="hidden" name="version" value="{{ $setting->preparation_version }}">
                <fieldset class="grid gap-3 sm:grid-cols-2">
                    <legend class="mb-3 font-medium">Classes to remove from this timetable</legend>
                    @forelse($classes as $class)
                        <label class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-brand-700">
                            <input type="checkbox" name="excluded_class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, $setting->excluded_class_ids ?? [])) class="rounded border-slate-300">
                            <span>{{ $class->name }}</span>
                            @if(in_array($class->id, $setting->excluded_class_ids ?? []))<span class="ml-auto text-xs font-semibold text-amber-700 dark:text-amber-300">Removed</span>@endif
                        </label>
                    @empty
                        <p>No classes in this academic year.</p>
                    @endforelse
                </fieldset>
                <button class="mt-4 min-h-11 rounded-md bg-[#124E66] px-4 py-2 font-semibold text-white">Save class participation</button>
                <p class="mt-2 text-xs">Restoring a class checks its saved placements. If a freed period has been reused, resolve the reported clash and try again.</p>
            </form>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-brand-700 dark:bg-brand-800">
            <h3 class="text-lg font-semibold">Copy timetable to a new academic year</h3>
            <p class="mt-2 text-sm">Choose what each class becomes, such as Form 1A → Form 2B. A separate, unpublished draft keeps the lesson times, subjects, teachers, rooms and group structure for mapped classes, including classes temporarily removed above.</p>
            <p class="mt-2 text-sm">Student promotions are handled separately. Review the copied curriculum and group membership before publishing. Student group overrides and custom constraints must be set up again.</p>
            <form method="GET" action="{{ route('admin.timetable.classes', $setting) }}" class="mt-4 flex flex-wrap items-end gap-3">
                <label class="text-sm">Destination academic year
                    <select name="target_year_id" class="mt-1 block min-h-11 rounded-md border-slate-300 dark:bg-brand-900">
                        @foreach($years as $year)<option value="{{ $year->id }}" @selected($targetYear?->id === $year->id)>{{ $year->year_name }}</option>@endforeach
                    </select>
                </label>
                <button class="min-h-11 rounded-md border border-slate-300 px-4 text-sm">Load destination classes</button>
            </form>
            @if($targetYear && $targetClasses->isNotEmpty() && $classes->isNotEmpty())
                <form method="POST" action="{{ route('admin.timetable.rollover', $setting) }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="target_year_id" value="{{ $targetYear->id }}">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <label class="text-sm">New timetable name<input name="name" value="{{ old('name', $targetYear->year_name.' '.$setting->typeLabel().' timetable') }}" maxlength="120" required class="mt-1 block min-h-11 w-full rounded-md border-slate-300 dark:bg-brand-900"></label>
                        <label class="text-sm">Cycle anchor date<input type="date" name="cycle_anchor_date" value="{{ old('cycle_anchor_date') }}" required class="mt-1 block min-h-11 w-full rounded-md border-slate-300 dark:bg-brand-900"></label>
                        <label class="text-sm">Day on anchor<input type="number" name="cycle_anchor_day" value="{{ old('cycle_anchor_day', 1) }}" min="1" max="{{ $setting->cycle_length }}" required class="mt-1 block min-h-11 w-full rounded-md border-slate-300 dark:bg-brand-900"></label>
                    </div>
                    <p class="text-sm font-medium">Choose every mapping explicitly. Select “Do not copy” for graduating classes.</p>
                    @foreach($classes as $class)
                        <div class="grid items-center gap-2 border-b border-slate-100 py-3 sm:grid-cols-2 dark:border-brand-700">
                            <label for="mapping-{{ $class->id }}" class="font-medium">{{ $class->name }} becomes</label>
                            <select id="mapping-{{ $class->id }}" name="mapping[{{ $class->id }}]" required class="min-h-11 rounded-md border-slate-300 dark:bg-brand-900">
                                <option value="">Choose destination</option>
                                <option value="0" @selected((string) old('mapping.'.$class->id, '') === '0')>Do not copy (leaving / graduating)</option>
                                @foreach($targetClasses as $target)<option value="{{ $target->id }}" @selected((string) old('target_year_id') === (string) $targetYear->id && (string) old('mapping.'.$class->id) === (string) $target->id)>{{ $target->name }}</option>@endforeach
                            </select>
                        </div>
                    @endforeach
                    <button class="min-h-11 rounded-md bg-[#124E66] px-4 py-2 font-semibold text-white">Create new-year draft</button>
                </form>
            @else
                <p class="mt-4 text-sm">Create an open destination academic year and its classes before copying the timetable.</p>
                <a href="{{ route('admin.academic-years.index') }}" class="mt-2 inline-block text-sm underline">Manage academic years</a>
            @endif
        </section>
    </div>
</x-app-layout>
