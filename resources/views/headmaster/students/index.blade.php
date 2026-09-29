<x-app-layout>
    <x-slot name="header"><div class="bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] text-white mt-16 rounded-2xl p-6"><h2 class="text-2xl font-bold">Student profiles</h2></div></x-slot>
    <div class="mx-auto max-w-7xl space-y-4 p-6">
        <a href="{{ route('headmaster.dashboard', request()->only('term_id')) }}" class="text-blue-700">Back to overview</a>
        <form method="GET" class="flex gap-3">
            <input type="hidden" name="term_id" value="{{ request('term_id') }}">
            <input name="search" aria-label="Student name or admission number" placeholder="Name or admission number" value="{{ request('search') }}" class="rounded border-slate-300">
            <button class="rounded bg-[#124E66] px-4 py-2 text-white">Search</button>
        </form>
        <div class="rounded-xl border bg-white p-4 dark:bg-brand-800">
            @forelse($students as $student)
                <a class="flex items-center gap-4 border-b p-3 hover:bg-blue-50 dark:hover:bg-brand-700" href="{{ route('headmaster.students.show', array_merge(['student' => $student], request()->only(['term_id', 'search', 'page']))) }}">
                    <x-student-photo :student="$student" /><strong>{{ $student->user?->name }}</strong> · {{ $student->admission_no }} · {{ $student->currentClass?->name ?? 'No current class' }}
                </a>
            @empty <p>No students found.</p> @endforelse
        </div>
        {{ $students->links() }}
    </div>
</x-app-layout>
