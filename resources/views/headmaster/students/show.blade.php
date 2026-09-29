<x-app-layout>
    <x-slot name="header">
        <div class="bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] text-white mt-16 rounded-2xl p-6">
            <h2 class="text-2xl font-bold text-white">Student Profile</h2>
            <p>Personal information, emergency contacts, and academic progress</p>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <a class="font-semibold text-[#124E66] dark:text-brand-200" href="{{ route('headmaster.students.index', request()->only(['search', 'page', 'term_id'])) }}">← Back to student profiles</a>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 p-6">
            <div class="flex items-center gap-5">
                <x-student-photo :student="$student" />
                <div><h3 class="text-2xl font-bold">{{ $student->user?->name }}</h3><p>{{ $student->currentClass?->name ?? 'Unassigned' }} · {{ $student->admission_no }}</p><p>{{ $student->user?->email }}</p></div>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(['Date of birth' => $student->date_of_birth?->format('d M Y'), 'Gender' => ucfirst($student->gender ?? ''), 'Nationality' => $student->nationality, 'Identity document' => $student->identityDisplay(), 'Account status' => ucfirst($student->user?->status ?? '')] as $label => $value)
                    <div><dt class="text-sm text-slate-500 dark:text-brand-300">{{ $label }}</dt><dd class="font-semibold">{{ $value ?: 'Not provided' }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 p-6">
            <h3 class="text-xl font-bold">Emergency contact</h3>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach(['Contact name' => $student->emergency_contact_name, 'Relationship' => $student->emergency_contact_relationship, 'Primary phone' => $student->emergency_contact_phone, 'Alternative phone' => $student->emergency_contact_alt_phone, 'Emergency address' => $student->emergency_contact_address, 'Medical notes' => $student->medical_notes] as $label => $value)
                    <div><dt class="text-sm text-slate-500 dark:text-brand-300">{{ $label }}</dt><dd class="whitespace-pre-line font-semibold">{{ $value ?: 'Not provided' }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 p-6">
            <h3 class="text-xl font-bold">Parents and guardians</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">@forelse($student->parents as $parent)<div><p class="font-semibold">{{ $parent->user?->name }} · {{ $parent->pivot->relationship ?: 'Guardian' }}</p><p>{{ $parent->phone ?: 'Phone not provided' }}</p><p>{{ $parent->user?->email }}</p><p>{{ $parent->address }}</p></div>@empty<p>No linked parent or guardian.</p>@endforelse</div>
        </section>
        @include('students.profile-performance')
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 p-6"><h3 class="text-xl font-bold">Class history</h3><div class="mt-4 space-y-2">@forelse($student->classHistory->sortByDesc('academic_year_id') as $history)<p>{{ $history->academicYear?->year_name }} · {{ $history->class?->name ?? 'Class not recorded' }}</p>@empty<p>No class history recorded.</p>@endforelse</div></section>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800 p-6"><h3 class="text-xl font-bold">Awards and leadership</h3><div class="mt-4 space-y-3">@forelse($student->awards as $award)<p><strong>{{ $award->award_title }}</strong> · {{ $award->run->academicYear?->year_name }} · {{ $award->run->term?->name }}</p>@empty<p>No published awards.</p>@endforelse @forelse($student->prefectAppointments as $appointment)<div><p><strong>{{ $appointment->title }}</strong> · {{ $appointment->academicYear?->year_name }} · {{ ucfirst($appointment->status) }}</p><p>{{ $appointment->duties }}</p></div>@empty<p>No prefect appointments.</p>@endforelse</div></section>
    </div>
</x-app-layout>
