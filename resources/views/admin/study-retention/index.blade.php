<x-app-layout>
    <x-slot name="header">
        <div class="kw-page-header mt-16 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-700 p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-white">After-school Study List</h2>
                <a data-admin-back href="{{ route('admin.terms.index') }}" class="text-sm font-medium text-white hover:text-emerald-100">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <label class="block min-w-64"><span class="text-sm font-semibold text-slate-700">Study attendance term</span>
                        <select name="term_id" class="mt-1 w-full rounded-md border-slate-300" onchange="this.form.submit()">
                            @foreach($terms as $option)
                                <option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->academicYear?->year_name }} · {{ $option->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if($term)
                        <a href="{{ route('admin.study-retention.print', ['term_id' => $term->id]) }}" target="_blank" class="rounded-md bg-slate-800 px-4 py-2.5 font-semibold text-white hover:bg-slate-700">Print Study List</a>
                    @endif
                </form>
            </div>

            @if($term)
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-bold">Results used for study decisions</h3>
                    <p class="mt-1 text-sm text-slate-500">Use mid-term or end-of-term results from this or an earlier term. Missing scores are not treated as zero. The same rules determine departure time in the parent app.</p>
                    <form method="POST" action="{{ route('admin.study-retention.selection') }}" class="mt-4 flex flex-wrap items-end gap-4">
                        @csrf <input type="hidden" name="term_id" value="{{ $term->id }}">
                        <label><span class="block text-sm font-semibold">Results term</span>
                            <select name="source_term_id" class="mt-1 rounded-md border-slate-300">
                                @foreach($terms->filter(fn($option) => $option->start_date <= $term->start_date) as $option)
                                    <option value="{{ $option->id }}" @selected((int)old('source_term_id', $selection?->source_term_id ?? $term->id) === $option->id)>{{ $option->academicYear?->year_name }} · {{ $option->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label><span class="block text-sm font-semibold">Assessment</span>
                            <select name="assessment" class="mt-1 rounded-md border-slate-300">
                                <option value="midterm" @selected(old('assessment', $selection?->assessment ?? 'endterm') === 'midterm')>Mid-term</option>
                                <option value="endterm" @selected(old('assessment', $selection?->assessment ?? 'endterm') === 'endterm')>End of term</option>
                            </select>
                        </label>
                        <button class="rounded-md bg-emerald-600 px-4 py-2 font-semibold text-white">Save results selection</button>
                    </form>
                    <p class="mt-3 text-sm text-slate-600">School hours: 07:30–13:10. Students remaining for study: 07:30–15:15.</p>
                </div>
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-bold">Voluntary study</h3>
                    <p class="mt-1 text-sm text-slate-500">Record students who opt to stay, even when their marks do not require it. Enrolment applies only to the selected study attendance term.</p>
                    <form method="POST" action="{{ route('admin.study-retention.enrol') }}" class="mt-4 flex flex-wrap items-end gap-4">
                        @csrf <input type="hidden" name="term_id" value="{{ $term->id }}">
                        <label class="min-w-64"><span class="block text-sm font-semibold">Student</span>
                            <select name="student_id" required class="mt-1 w-full rounded-md border-slate-300">
                                <option value="">Choose a student</option>
                                @foreach($students as $student)<option value="{{ $student->id }}">{{ $student->user?->name }} · {{ $student->currentClass?->name }} · {{ $student->admission_no }}</option>@endforeach
                            </select>
                        </label>
                        <button class="rounded-md bg-emerald-600 px-4 py-2 font-semibold text-white">Add to voluntary study</button>
                    </form>
                    @foreach($enrolments as $enrolment)
                        <div class="mt-3 flex items-center justify-between border-t pt-3 text-sm">
                            <span>{{ $enrolment->student?->user?->name }} · Voluntary</span>
                            <form method="POST" action="{{ route('admin.study-retention.unenrol', $enrolment) }}">@csrf @method('DELETE')<button class="text-red-600">Remove voluntary enrolment</button></form>
                        </div>
                    @endforeach
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-xl bg-white p-6 shadow-sm lg:col-span-1">
                        <h3 class="text-lg font-bold text-slate-900">Set study conditions</h3>
                        <p class="mt-1 text-sm text-slate-500">A class-specific rule overrides its form rule. Enable either condition or both.</p>
                        <form method="POST" action="{{ route('admin.study-retention.store') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="term_id" value="{{ $term->id }}">
                            <label class="block"><span class="text-sm font-semibold">Apply to</span>
                                <select name="scope_type" class="mt-1 w-full rounded-md border-slate-300" id="scope-type">
                                    <option value="form">Entire form</option><option value="class">One class</option>
                                </select>
                            </label>
                            <label class="block" id="form-scope"><span class="text-sm font-semibold">Form</span>
                                <select class="mt-1 w-full rounded-md border-slate-300" data-scope="form">
                                    @foreach($classes->pluck('level')->unique() as $level)<option value="{{ $level }}">Form {{ $level }}</option>@endforeach
                                </select>
                            </label>
                            <label class="hidden" id="class-scope"><span class="text-sm font-semibold">Class</span>
                                <select class="mt-1 w-full rounded-md border-slate-300" data-scope="class">
                                    @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
                                </select>
                            </label>
                            <input type="hidden" name="scope_value" id="scope-value">
                            <div class="rounded-lg border border-slate-200 p-4">
                                <label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="overall_enabled" value="1" checked class="rounded"> Overall average below</label>
                                <div class="mt-2 flex items-center gap-2"><input type="number" name="overall_threshold" value="60" min="0" max="100" step="0.1" class="w-28 rounded-md border-slate-300"><span>%</span></div>
                            </div>
                            <div class="rounded-lg border border-slate-200 p-4">
                                <label class="flex items-center gap-2 font-semibold"><input type="checkbox" name="subject_enabled" value="1" checked class="rounded"> Any subject below</label>
                                <div class="mt-2 flex items-center gap-2"><input type="number" name="subject_threshold" value="60" min="0" max="100" step="0.1" class="w-28 rounded-md border-slate-300"><span>%</span></div>
                            </div>
                            <button class="w-full rounded-md bg-emerald-600 px-4 py-2.5 font-bold text-white hover:bg-emerald-700">Save Conditions</button>
                        </form>
                    </div>

                    <div class="space-y-6 lg:col-span-2">
                        <div class="rounded-xl bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-bold">Configured rules</h3>
                            <div class="mt-3 space-y-2">
                                @forelse($rules as $rule)
                                    @php $className = $rule->scope_type === 'class' ? $classes->firstWhere('id', (int)$rule->scope_value)?->name : 'Form '.$rule->scope_value; @endphp
                                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm">
                                        <span><strong>{{ $className }}</strong> · {{ $rule->overall_enabled ? 'Overall below '.floatval($rule->overall_threshold).'%' : '' }}{{ $rule->overall_enabled && $rule->subject_enabled ? ' + ' : '' }}{{ $rule->subject_enabled ? 'Any subject below '.floatval($rule->subject_threshold).'%' : '' }}</span>
                                        <form method="POST" action="{{ route('admin.study-retention.destroy', $rule) }}">@csrf @method('DELETE')<button class="text-red-600 hover:text-red-800" onclick="return confirm('Remove these study conditions?')">Remove</button></form>
                                    </div>
                                @empty <p class="text-sm text-slate-500">No study conditions have been set for this term.</p> @endforelse
                            </div>
                        </div>
                        <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b p-5"><h3 class="text-lg font-bold">Students remaining for study</h3><span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">{{ $rows->count() }}</span></div>
                            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Student</th><th class="px-4 py-3 text-left">Class</th><th class="px-4 py-3 text-left">Average</th><th class="px-4 py-3 text-left">Study reason / subjects</th></tr></thead><tbody class="divide-y">
                                @forelse($rows as $row)<tr><td class="px-4 py-3 font-semibold">{{ $row['student']?->user?->name ?? 'Student' }}<div class="text-xs font-normal text-slate-500">{{ $row['student']?->admission_no }}</div></td><td class="px-4 py-3">{{ $row['class']?->name }}</td><td class="px-4 py-3">{{ $row['average'] === null ? 'Not recorded' : $row['average'].'%' }}</td><td class="px-4 py-3">@if($row['voluntary'])<span class="mb-1 inline-block rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-900">Voluntary study</span>@endif @if($row['overall'])<span class="mb-1 inline-block rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-900">Overall below {{ floatval($row['overall_threshold']) }}%</span>@endif @foreach($row['subjects'] as $subject)<span class="mb-1 mr-1 inline-block rounded bg-red-100 px-2 py-1 text-xs text-red-800">{{ $subject['name'] }} {{ $subject['score'] }}%</span>@endforeach</td></tr>
                                @empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No students currently meet the configured study conditions.</td></tr>@endforelse
                            </tbody></table></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const type = document.getElementById('scope-type'), value = document.getElementById('scope-value');
            const sync = () => {
                const isClass = type.value === 'class';
                document.getElementById('form-scope').classList.toggle('hidden', isClass);
                document.getElementById('class-scope').classList.toggle('hidden', !isClass);
                value.value = document.querySelector(`[data-scope="${type.value}"]`)?.value || '';
            };
            type?.addEventListener('change', sync);
            document.querySelectorAll('[data-scope]').forEach(el => el.addEventListener('change', sync)); sync();
        });
    </script>
</x-app-layout>
