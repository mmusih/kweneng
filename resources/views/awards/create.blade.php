@php
    $editing = isset($run);
    $config = $editing ? ($run->calculation_config ?? []) : [];
    $existingCitation = $editing ? $run->awards->first()?->citation : null;
@endphp
<x-app-layout>
    <x-slot name="header"><div><h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $editing ? 'Edit award draft' : 'Create award draft' }}</h2><p class="text-sm text-slate-500 dark:text-brand-300">{{ $editing ? 'Saving recalculates the recipient list from the updated criteria.' : 'Nothing becomes visible to parents until you publish it.' }}</p></div></x-slot>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @if($errors->any())<div class="mb-5 rounded-lg bg-rose-50 p-4 text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $editing ? route(Auth::user()->role.'.awards.update', $run) : route(Auth::user()->role.'.awards.store') }}" class="space-y-6" id="award-form">@csrf @if($editing) @method('PUT') @endif
            <section class="rounded-xl bg-white p-6 shadow dark:bg-brand-800"><h3 class="mb-4 text-lg font-bold dark:text-white">Award details</h3><div class="grid gap-4 md:grid-cols-2">
                <label class="block"><span class="text-sm font-semibold">Award type</span><select name="type" id="award-type" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="academic" @selected(old('type', $run->type ?? 'academic')==='academic')>Academic award</option><option value="custom" @selected(old('type', $run->type ?? 'academic')==='custom')>Custom award</option></select></label>
                <label class="block"><span class="text-sm font-semibold">Category</span><select name="award_category_id" id="category" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="">No preset category</option>@foreach($categories as $category)<option value="{{ $category->id }}" data-title="{{ $category->name }}" data-citation="{{ $category->default_citation }}" @selected(old('award_category_id', $run->award_category_id ?? null)==$category->id)>{{ $category->name }}{{ $category->headmaster_only ? ' (restricted)' : '' }}</option>@endforeach</select></label>
                <label class="block"><span class="text-sm font-semibold">Award title</span><input required name="title" id="title" value="{{ old('title', $run->title ?? 'Academic Excellence') }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                <label class="block"><span class="text-sm font-semibold">Award date</span><input required type="date" name="award_date" value="{{ old('award_date', isset($run) ? $run->award_date?->toDateString() : now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                <label class="block"><span class="text-sm font-semibold">Academic year</span><select required name="academic_year_id" id="academic-year" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700">@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $run->academic_year_id ?? null)==$year->id)>{{ $year->year_name }}</option>@endforeach</select></label>
                <label class="block"><span class="text-sm font-semibold">Selected term</span><select name="term_id" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="">Not term-specific</option>@foreach($academicYears as $year)@foreach($year->terms as $term)<option value="{{ $term->id }}" data-year="{{ $year->id }}" @selected(old('term_id', $run->term_id ?? null)==$term->id)>{{ $year->year_name }} · {{ $term->name }}</option>@endforeach @endforeach</select></label>
                <label class="md:col-span-2"><span class="text-sm font-semibold">Certificate citation</span><textarea name="citation" id="citation" rows="2" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700">{{ old('citation', $existingCitation) }}</textarea></label>
                <label class="flex items-center gap-2"><input type="hidden" name="parent_visible" value="0"><input type="checkbox" name="parent_visible" value="1" @checked(old('parent_visible', $run->parent_visible ?? true)) class="rounded border-slate-300"><span class="text-sm font-semibold">Show after publishing on parent dashboard and API</span></label>
            </div></section>

            <section id="academic-fields" class="rounded-xl bg-white p-6 shadow dark:bg-brand-800"><h3 class="mb-4 text-lg font-bold dark:text-white">Academic calculation</h3><div class="grid gap-4 md:grid-cols-3">
                <label><span class="text-sm font-semibold">Scope</span><select name="scope_type" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700">@foreach(['all_levels'=>'Top students from every form / level','school'=>'Whole school ranking','level'=>'One form / level','class'=>'One class'] as $value=>$label)<option value="{{ $value }}" @selected(old('scope_type', $run->scope_type ?? 'all_levels')===$value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold">Ranking type</span><select name="ranking_basis" id="ranking-basis" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="overall" @selected(old('ranking_basis', $config['ranking_basis'] ?? 'overall') === 'overall')>Overall academic average</option><option value="subject" @selected(old('ranking_basis', $config['ranking_basis'] ?? 'overall') === 'subject')>Top students per subject</option></select></label>
                <label><span class="text-sm font-semibold">Form / level</span><input type="number" min="1" name="level" value="{{ old('level', $run->level ?? null) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                <label><span class="text-sm font-semibold">Class</span><select name="class_id" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="">Select class</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id', $run->class_id ?? null)==$class->id)>{{ $class->name }} · {{ $class->academicYear?->year_name }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold">Top positions</span><input type="number" min="1" max="100" name="positions" value="{{ old('positions', $run->positions ?? 3) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                <label><span class="text-sm font-semibold">Excellence cutoff (%)</span><input type="number" step="0.01" min="0" max="100" name="cutoff_percentage" value="{{ old('cutoff_percentage', $run->cutoff_percentage ?? 75) }}" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"></label>
                <label><span class="text-sm font-semibold">Marks basis</span><select name="calculation_mode" id="calculation-mode" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700">@foreach(['selected_midterm'=>'Selected term midterm','selected_endterm'=>'Selected term end-of-term','selected_term_average'=>'Selected term midterm + end-term average','cumulative_to_term'=>'All terms through selected term','all_terms'=>'All terms in academic year','custom'=>'Custom weighted combination'] as $value=>$label)<option value="{{ $value }}" @selected(old('calculation_mode', $run->calculation_mode ?? 'selected_midterm')===$value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold">Missing marks</span><select name="missing_marks_policy" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="exclude" @selected(old('missing_marks_policy', $run->missing_marks_policy ?? 'exclude')==='exclude')>Exclude incomplete students</option><option value="available" @selected(old('missing_marks_policy', $run->missing_marks_policy ?? 'exclude')==='available')>Average available marks</option></select></label>
                <label><span class="text-sm font-semibold">Unresolved ties</span><select name="tie_policy" class="mt-1 w-full rounded-lg border-slate-300 dark:bg-brand-700"><option value="include_all" @selected(old('tie_policy', $run->tie_policy ?? 'include_all')==='include_all')>Include all tied students</option><option value="manual" @selected(old('tie_policy', $run->tie_policy ?? 'include_all')==='manual')>Joint place, review manually</option><option value="strict_count" @selected(old('tie_policy', $run->tie_policy ?? 'include_all')==='strict_count')>Strict recipient count</option></select></label>
            </div>
            <div id="subject-fields" class="mt-6 hidden rounded-lg border border-slate-200 p-4 dark:border-brand-600">
                <h4 class="font-bold dark:text-white">Subjects to award</h4>
                <p class="mb-3 text-sm text-slate-500 dark:text-brand-300">Leave every box clear to generate prizes for all subjects taught in the selected scope. Positions and cutoff are applied separately to each subject in each form/level.</p>
                <div class="grid max-h-56 gap-2 overflow-y-auto sm:grid-cols-2 md:grid-cols-3">
                    @foreach ($subjects as $subject)
                        <label class="flex items-center gap-2 rounded-md border border-slate-200 p-2 dark:border-brand-600">
                            <input type="checkbox" name="subject_ids[]" value="{{ $subject->id }}" @checked(in_array($subject->id, old('subject_ids', $config['subject_ids'] ?? []))) class="rounded border-slate-300">
                            <span>{{ $subject->name }} <small class="text-slate-500">({{ $subject->code }})</small></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div id="custom-components" class="mt-6 hidden">
                <h4 class="font-bold">Weighted assessments</h4>
                <p class="mb-3 text-sm text-slate-500">Weights are relative and do not need to add to 100.</p>
                <div class="grid gap-3 md:grid-cols-3">
                    @for ($i = 0; $i < 4; $i++)
                        @php($component = old("components.$i", $config['components'][$i] ?? []))
                        <div class="rounded-lg border p-3 dark:border-brand-600">
                            <select name="components[{{ $i }}][term_id]" class="w-full rounded-lg border-slate-300 dark:bg-brand-700">
                                <option value="">Choose term</option>
                                @foreach ($academicYears as $year)
                                    @foreach ($year->terms as $term)
                                        <option value="{{ $term->id }}" @selected(($component['term_id'] ?? null)==$term->id)>{{ $year->year_name }} · {{ $term->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <select name="components[{{ $i }}][assessment]" class="mt-2 w-full rounded-lg border-slate-300 dark:bg-brand-700">
                                <option value="endterm" @selected(($component['assessment'] ?? 'endterm')==='endterm')>End-term</option>
                                <option value="midterm" @selected(($component['assessment'] ?? 'endterm')==='midterm')>Midterm</option>
                            </select>
                            <input name="components[{{ $i }}][weight]" type="number" step="0.01" min="0.01" value="{{ $component['weight'] ?? 1 }}" class="mt-2 w-full rounded-lg border-slate-300 dark:bg-brand-700" placeholder="Weight">
                        </div>
                    @endfor
                </div>
            </div>
            <div class="mt-6">
                <h4 class="font-bold">Tie-breakers in priority order</h4>
                <p id="tie-breaker-help" class="mb-3 text-sm text-slate-500">Leave unused rows blank. A later assessment is compared only when earlier scores are equal.</p>
                <div class="grid gap-3 md:grid-cols-3">
                    @for ($i = 0; $i < 3; $i++)
                        @php($tieBreaker = old("tie_breakers.$i", $config['tie_breakers'][$i] ?? []))
                        <div class="rounded-lg border p-3 dark:border-brand-600">
                            <select name="tie_breakers[{{ $i }}][term_id]" class="w-full rounded-lg border-slate-300 dark:bg-brand-700">
                                <option value="">No tie-breaker</option>
                                @foreach ($academicYears as $year)
                                    @foreach ($year->terms as $term)
                                        <option value="{{ $term->id }}" @selected(($tieBreaker['term_id'] ?? null)==$term->id)>{{ $year->year_name }} · {{ $term->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            <select name="tie_breakers[{{ $i }}][assessment]" class="tie-assessment mt-2 w-full rounded-lg border-slate-300 dark:bg-brand-700">
                                <option value="endterm" @selected(($tieBreaker['assessment'] ?? 'endterm')==='endterm') data-overall="End-term average" data-subject="End-term subject mark">End-term average</option>
                                <option value="midterm" @selected(($tieBreaker['assessment'] ?? 'endterm')==='midterm') data-overall="Midterm average" data-subject="Midterm subject mark">Midterm average</option>
                            </select>
                        </div>
                    @endfor
                </div>
            </div>
            </section>

            <section id="custom-fields" class="hidden rounded-xl bg-white p-6 shadow dark:bg-brand-800"><h3 class="mb-2 text-lg font-bold dark:text-white">Custom award recipients</h3><p class="mb-4 text-sm text-slate-500">Select one or several students.</p><div class="grid max-h-96 gap-2 overflow-y-auto md:grid-cols-2">@foreach($students as $student)<label class="flex items-center gap-3 rounded-lg border p-3 dark:border-brand-600"><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, old('student_ids', $editing ? $run->awards->pluck('student_id')->all() : []))) class="rounded"><span><strong>{{ $student->user?->name }}</strong><small class="block text-slate-500">{{ $student->admission_no }} · {{ $student->currentClass?->name }}</small></span></label>@endforeach</div></section>
            <div class="flex justify-end"><button class="rounded-lg bg-[#124E66] px-6 py-3 font-bold text-white">{{ $editing ? 'Save and recalculate' : 'Generate draft' }}</button></div>
        </form>
    </div>
    @push('scripts')<script>
        const type=document.getElementById('award-type'), academic=document.getElementById('academic-fields'), custom=document.getElementById('custom-fields'), mode=document.getElementById('calculation-mode'), components=document.getElementById('custom-components'), category=document.getElementById('category'), ranking=document.getElementById('ranking-basis'), subjectFields=document.getElementById('subject-fields');
        function sync(){const isAcademic=type.value==='academic', isSubject=isAcademic&&ranking.value==='subject'; academic.classList.toggle('hidden',!isAcademic); custom.classList.toggle('hidden',isAcademic); components.classList.toggle('hidden',mode.value!=='custom'); subjectFields.classList.toggle('hidden',!isSubject); document.getElementById('tie-breaker-help').textContent=isSubject?'For subject prizes, use the same subject’s midterm mark or its mark from a previous term. Tie-breakers are applied in the order shown.':'Leave unused rows blank. A later assessment is compared only when earlier scores are equal.'; document.querySelectorAll('.tie-assessment option').forEach(option=>option.textContent=isSubject?option.dataset.subject:option.dataset.overall);}
        type.addEventListener('change',sync); mode.addEventListener('change',sync); ranking.addEventListener('change',sync); category.addEventListener('change',()=>{const option=category.selectedOptions[0]; if(option?.dataset.title)document.getElementById('title').value=option.dataset.title;if(option?.dataset.citation)document.getElementById('citation').value=option.dataset.citation;}); sync();
    </script>@endpush
</x-app-layout>
