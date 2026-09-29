@php
    $editing = isset($prefect);
@endphp
<x-app-layout>
    <x-slot name="header"><div><h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $editing ? 'Edit prefect appointment' : 'Appoint a prefect' }}</h2><p class="text-sm text-slate-500 dark:text-brand-300">Record the leadership role and the duties assigned to the student.</p></div></x-slot>
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        @if($errors->any())<div class="mb-5 rounded-lg bg-rose-50 p-4 text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $editing ? route(Auth::user()->role.'.prefects.update', $prefect) : route(Auth::user()->role.'.prefects.store') }}" class="space-y-6">@csrf @if($editing)@method('PUT')@endif
            <section class="rounded-xl bg-white p-6 shadow dark:bg-brand-800">
                <div class="grid gap-5 md:grid-cols-2">
                    <label><span class="text-sm font-semibold">Student</span><select required name="student_id" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="">Choose student</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(old('student_id', $prefect->student_id ?? request('student_id'))==$student->id)>{{ $student->user?->name }} · {{ $student->admission_no }} · {{ $student->currentClass?->name }}</option>@endforeach</select></label>
                    <label><span class="text-sm font-semibold">Academic year</span><select required name="academic_year_id" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="">Choose year</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $prefect->academic_year_id ?? null)==$year->id)>{{ $year->year_name }}</option>@endforeach</select></label>
                    <label class="md:col-span-2"><span class="text-sm font-semibold">Prefect title</span><input required name="title" value="{{ old('title', $prefect->title ?? '') }}" placeholder="e.g. Head Boy, Head Girl, Senior Prefect, Library Prefect" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                    <label class="md:col-span-2"><span class="text-sm font-semibold">Assigned duties</span><textarea required maxlength="600" name="duties" rows="5" placeholder="Describe the areas, routines, events, or students this prefect is responsible for." class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700">{{ old('duties', $prefect->duties ?? '') }}</textarea><small class="text-slate-500">These duties will also appear on the certificate.</small></label>
                    <label><span class="text-sm font-semibold">Appointment date</span><input required type="date" name="appointed_on" value="{{ old('appointed_on', isset($prefect) ? $prefect->appointed_on?->toDateString() : now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                    <label><span class="text-sm font-semibold">Service end date</span><input type="date" name="service_ends_on" value="{{ old('service_ends_on', isset($prefect) ? $prefect->service_ends_on?->toDateString() : '') }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><small class="text-slate-500">Optional while service is ongoing.</small></label>
                    <label><span class="text-sm font-semibold">Status</span><select required name="status" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700">@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(old('status', $prefect->status ?? 'active')===$value)>{{ $label }}</option>@endforeach</select></label>
                    <label><span class="text-sm font-semibold">Internal notes</span><textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700" placeholder="Optional notes; not printed on the certificate.">{{ old('notes', $prefect->notes ?? '') }}</textarea></label>
                </div>
            </section>
            <div class="flex justify-end gap-3"><a href="{{ route(Auth::user()->role.'.prefects.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700">Cancel</a><button class="rounded-lg bg-[#124E66] px-6 py-3 font-bold text-white">{{ $editing ? 'Save changes' : 'Appoint prefect' }}</button></div>
        </form>
    </div>
</x-app-layout>
