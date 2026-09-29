<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-xl bg-gradient-to-r from-indigo-700 to-blue-700 px-6 py-5 text-white shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-200">Administrator marks override</p>
            <h2 class="mt-1 text-2xl font-semibold">{{ $class->name }} · {{ $subject->name }}</h2>
            <p class="mt-1 text-sm text-indigo-100">{{ $teacher->user?->name ?? 'Teacher' }} · {{ $term->academicYear?->year_name }} {{ $term->name }}</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('admin.marks.index', ['academic_year_id' => $data['academic_year_id'], 'term_id' => $data['term_id']]) }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">← Back to teacher progress</a>
                @if($term->isLocked())<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">Term locked · administrator override enabled</span>@endif
            </div>

            @if(session('success'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="POST" action="{{ route('admin.marks.group.update') }}">
                @csrf @method('PUT')
                @foreach(['academic_year_id', 'term_id', 'class_id', 'subject_id', 'teacher_id'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $data[$field] }}">@endforeach
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-bold text-slate-900">Learner marks</h3><p class="mt-1 text-sm text-slate-500">Enter or correct marks for this teacher’s assigned learners.</p></div>
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Learner</th><th class="px-4 py-3 text-left">Midterm</th><th class="px-4 py-3 text-left">End-term</th><th class="px-4 py-3 text-left">Remarks</th></tr></thead><tbody class="divide-y divide-slate-100">
                        @forelse($studentAssignments as $assignment)
                            @php $student = $assignment->student; $mark = $marks->get($assignment->student_id); @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $student?->user?->name ?? 'Learner' }}<div class="text-xs font-normal text-slate-500">{{ $student?->admission_no }}</div></td>
                                <td class="px-4 py-3"><input aria-label="Midterm mark for {{ $student?->user?->name }}" type="number" name="marks[{{ $student->id }}][midterm]" value="{{ old('marks.'.$student->id.'.midterm', $mark?->midterm_score) }}" min="0" max="100" step="0.01" class="w-28 rounded-md border-slate-300"></td>
                                <td class="px-4 py-3"><input aria-label="End-term mark for {{ $student?->user?->name }}" type="number" name="marks[{{ $student->id }}][endterm]" value="{{ old('marks.'.$student->id.'.endterm', $mark?->endterm_score) }}" min="0" max="100" step="0.01" class="w-28 rounded-md border-slate-300"></td>
                                <td class="px-4 py-3"><input aria-label="Remarks for {{ $student?->user?->name }}" type="text" name="marks[{{ $student->id }}][remarks]" value="{{ old('marks.'.$student->id.'.remarks', $mark?->remarks) }}" maxlength="500" class="min-w-56 w-full rounded-md border-slate-300"></td>
                            </tr>
                        @empty <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No active learners are assigned to this teacher for this class and subject.</td></tr> @endforelse
                    </tbody></table></div>
                </div>
                @if($studentAssignments->isNotEmpty())<div class="mt-5 flex justify-end"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 font-bold text-white hover:bg-indigo-700">Save all marks</button></div>@endif
            </form>
        </div>
    </div>
</x-app-layout>
