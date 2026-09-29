<x-app-layout>
    <x-slot name="header"><div><h2 class="text-2xl font-bold text-slate-900 dark:text-white">Awards, Certificates &amp; Leadership</h2><p class="text-sm text-slate-500">Published achievements and prefect appointments for your children.</p></div></x-slot>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @forelse($children as $child)
            <section class="mb-7 rounded-xl bg-white p-6 shadow dark:bg-brand-800">
                <h3 class="text-xl font-bold dark:text-white">{{ $child->user?->name }}</h3>
                <p class="mb-5 text-sm text-slate-500">{{ $child->currentClass?->name }} · {{ $child->admission_no }}</p>
                @if($child->prefectAppointments->isNotEmpty())
                    <div class="mb-6"><h4 class="mb-3 text-sm font-bold uppercase tracking-wide text-violet-700">Prefect leadership</h4><div class="grid gap-4 md:grid-cols-2">
                        @foreach($child->prefectAppointments as $prefect)
                            <article class="rounded-xl border border-violet-200 bg-violet-50/50 p-4 dark:border-violet-800 dark:bg-violet-950/20"><div class="flex items-start gap-3"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-violet-700 text-xl font-bold text-white">P</div><div><h5 class="font-bold dark:text-white">{{ $prefect->title }}</h5><p class="text-sm text-slate-500">{{ $prefect->academicYear?->year_name }} · {{ ucfirst($prefect->status) }}</p></div></div><p class="mt-3 whitespace-pre-line text-sm text-slate-600 dark:text-brand-200"><strong>Duties:</strong> {{ $prefect->duties }}</p><a class="mt-4 inline-flex rounded-lg bg-violet-700 px-3 py-2 text-sm font-semibold text-white" href="{{ route('parent.prefects.certificate', $prefect) }}">Download prefect certificate</a></article>
                        @endforeach
                    </div></div>
                @endif
                <h4 class="mb-3 text-sm font-bold uppercase tracking-wide text-amber-700">Awards &amp; achievements</h4>
                <div class="grid gap-4 md:grid-cols-2">
                    @forelse($child->awards as $award)
                        <article class="rounded-xl border p-4 dark:border-brand-600"><div class="flex items-start gap-3"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-xl font-bold text-white" style="background:{{ $award->run->category?->badge_color??'#D4AF37' }}">★</div><div><h5 class="font-bold dark:text-white">{{ $award->award_title }}</h5><p class="text-sm text-slate-500">{{ $award->run->academicYear?->year_name }}{{ $award->run->term?' · '.$award->run->term->name:'' }}</p>@if($award->position||$award->main_score!==null)<p class="mt-1 text-sm font-semibold text-amber-700">@if($award->position)Position {{ $award->position }}@endif @if($award->main_score!==null) · {{ $award->main_score }}%@endif</p>@endif</div></div>@if($award->citation)<p class="mt-3 text-sm text-slate-600 dark:text-brand-200">{{ $award->citation }}</p>@endif<a class="mt-4 inline-flex rounded-lg bg-[#124E66] px-3 py-2 text-sm font-semibold text-white" href="{{ route('parent.awards.certificate',$award) }}">Download A4 certificate</a></article>
                    @empty<p class="text-slate-500">No published awards yet.</p>@endforelse
                </div>
            </section>
        @empty<div class="rounded-xl bg-white p-10 text-center text-slate-500 shadow">No children are linked to this account.</div>@endforelse
    </div>
</x-app-layout>
