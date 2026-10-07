<x-app-layout>
    <x-slot name="header"><div class="ops-hero"><div><p class="ops-eyebrow">Academic planning · Administrator</p><h1>Subject Combination Planner</h1><p>Compare option blocks for your chosen classes. Subjects within a block run at the same time.</p></div></div></x-slot>
    <div class="ops-shell">
        @if(session('success'))<p class="rounded-lg bg-emerald-50 p-4 text-emerald-900" role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-900" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="ops-section p-5">
            <h2 class="text-xl font-bold">1. Choose the school year and resources</h2>
            <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
                <label>Timetable / academic year<select name="setting" class="block rounded border p-2 dark:bg-brand-800">@foreach($settings as $item)<option value="{{ $item->id }}" @selected($setting?->id === $item->id)>{{ $item->academicYear?->year_name }} · {{ $item->name }} · {{ $item->schedule_type }}</option>@endforeach</select></label>
                <button class="ops-button">Load resources</button>
            </form>
        </section>
        @if($setting)
        <form method="POST" action="{{ route('admin.subject-option-plans.generate') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="tt_setting_id" value="{{ $setting->id }}">
            <section class="ops-section p-5 space-y-4">
                <h2 class="text-xl font-bold">2. Choose classes and block count</h2>
                <p>Select one class or combine classes into a planning cohort. Teacher assignments and enrolments are scoped to these classes and this academic year.</p>
                <div class="flex flex-wrap gap-4">@forelse($classes as $class)<label class="flex items-center gap-2"><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, old('class_ids', $defaults['class_ids'] ?? [])))> {{ $class->name }}</label>@empty<p>No classes are available for this year.</p>@endforelse</div>
                <div class="flex flex-wrap gap-4">
                    <label>Plan name<input name="name" value="{{ old('name', $source ? preg_replace('/ · Alternative \d+$/u', '', $source->name) : 'Upper classes option plan') }}" required maxlength="120" class="block rounded border p-2 dark:bg-brand-800"></label>
                    <label>Number of option blocks<input type="number" name="block_count" min="2" max="8" value="{{ old('block_count', $defaults['block_count'] ?? 3) }}" required class="block rounded border p-2 dark:bg-brand-800"></label>
                </div>
            </section>
            <section class="ops-section p-5">
                <h2 class="text-xl font-bold">3. Select subjects and confirm resources</h2>
                <p class="my-3">Compulsory subjects are excluded from option blocks. Add more groups to offer a popular subject in multiple blocks or split a large class. Teachers come from existing teaching assignments. Demand estimates supplement existing learner enrolments.</p>
                <p class="my-3">Select all suitable rooms for each option, including specialist rooms where required. Unselected room requirements are flagged as provisional. Hold Ctrl (Windows) or Command (Mac) to select several rooms.</p>
                <div class="overflow-x-auto"><table class="w-full text-left text-sm"><caption class="sr-only">Option subjects and resource requirements</caption><thead><tr class="border-b"><th class="p-2">Subject</th><th class="p-2">Use</th><th class="p-2">Groups</th><th class="p-2">Estimated learners</th><th class="p-2">Suitable rooms</th></tr></thead><tbody>
                    @foreach($subjects as $index => $subject)
                    @php
                        $savedOption = collect($defaults['options'] ?? [])->firstWhere('subject_id', $subject->id);
                        $savedUse = $savedOption ? 'option' : (in_array($subject->id, $defaults['compulsory_ids'] ?? []) || (!$source && $subject->is_core) ? 'core' : 'exclude');
                    @endphp
                    <tr class="border-b align-top">
                        <td class="p-2 font-semibold">{{ $subject->name }}<input type="hidden" name="subjects[{{ $index }}][subject_id]" value="{{ $subject->id }}"></td>
                        <td class="p-2"><select aria-label="Use for {{ $subject->name }}" name="subjects[{{ $index }}][use]" class="rounded border p-2 dark:bg-brand-800">@foreach(['exclude' => 'Not included', 'core' => 'Compulsory', 'option' => 'Option subject'] as $value => $label)<option value="{{ $value }}" @selected(old("subjects.$index.use", $savedUse) === $value)>{{ $label }}</option>@endforeach</select></td>
                        <td class="p-2"><input aria-label="Groups for {{ $subject->name }}" name="subjects[{{ $index }}][groups]" type="number" min="1" max="4" required value="{{ old("subjects.$index.groups", $savedOption['groups'] ?? 1) }}" class="w-20 rounded border p-2 dark:bg-brand-800"></td>
                        <td class="p-2"><input aria-label="Estimated learners for {{ $subject->name }}" name="subjects[{{ $index }}][demand]" type="number" min="0" max="2000" value="{{ old("subjects.$index.demand", $savedOption['demand'] ?? null) }}" placeholder="Use enrolments" class="w-36 rounded border p-2 dark:bg-brand-800"></td>
                        <td class="p-2"><select aria-label="Suitable rooms for {{ $subject->name }}" name="subjects[{{ $index }}][room_ids][]" multiple class="min-w-48 rounded border p-2 dark:bg-brand-800">@foreach($rooms as $room)<option value="{{ $room->id }}" @selected(in_array($room->id, old("subjects.$index.room_ids", $savedOption['room_ids'] ?? [])))>{{ $room->name }} · {{ $room->capacity ?: 'unknown' }} seats</option>@endforeach</select></td>
                    </tr>
                    @endforeach
                </tbody></table></div>
                <div class="mt-5 flex flex-wrap items-center gap-4"><button class="ops-button" @disabled($classes->isEmpty())>Generate up to 3 best suggestions</button><span class="text-sm">Saves drafts for review. No timetable or learner changes.</span></div>
            </section>
        </form>
        @else<p class="ops-empty">Create a timetable with an academic year and rooms before generating suggestions.</p>@endif
        <section class="ops-section p-5"><h2 class="text-xl font-bold">Suggested option structures</h2><p class="my-2">Ranked by resource conflicts, learners with clashes, then load balance. Percentages show learners whose recorded choices fit distinct blocks; they are not feasibility guarantees.</p>
            @forelse($plans as $plan)
                <div>
                <a href="{{ route('admin.subject-option-plans.show', $plan) }}" class="ops-row"><div><strong>{{ $plan->name }}</strong><p>{{ ucfirst($plan->status) }} · {{ $plan->created_at->format('d M Y H:i') }} · {{ $plan->configuration['block_count'] }} blocks</p></div><div class="text-right"><strong>{{ $plan->assessment['score'] === null ? 'No learner data' : $plan->assessment['score'].'% choices fit' }}</strong><p>{{ $plan->assessment['hard_conflicts'] }} resource / block conflicts · Open & edit →</p></div></a>
                <details class="px-4 pb-4" @if($loop->index < 3) open @endif><summary class="cursor-pointer text-sm font-semibold">Compare blocks</summary>
                    @for($block = 0; $block < $plan->configuration['block_count']; $block++)
                        <p class="mt-2 text-sm"><strong>Block {{ chr(65 + $block) }}:</strong>
                            @foreach($plan->arrangement as $key => $assigned)
                                @if($assigned === $block)<span class="mr-2 inline-block">{{ $subjects->firstWhere('id', (int) explode('-', $key)[0])?->name ?? 'Unavailable subject' }} (group {{ explode('-', $key)[1] }})</span>@endif
                            @endforeach
                        </p>
                    @endfor
                </details>
                </div>
            @empty<p class="ops-empty">No suggestions yet. Choose your cohort and subjects above.</p>@endforelse
        </section>
    </div>
</x-app-layout>
