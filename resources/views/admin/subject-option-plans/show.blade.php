<x-app-layout>
    <x-slot name="header"><div class="ops-hero"><div><p class="ops-eyebrow">Subject Combination Planner · {{ ucfirst($plan->status) }}</p><h1>{{ $plan->name }}</h1><p>Move groups between blocks, rescore, then accept the arrangement you prefer.</p></div><a class="ops-button" href="{{ route('admin.subject-option-plans.index', ['setting' => $plan->tt_setting_id]) }}">All suggestions</a></div></x-slot>
    <div class="ops-shell">
        <p><strong>Selected classes:</strong> {{ $cohort }} · {{ $plan->configuration['block_count'] }} option blocks</p>
        @if(session('success'))<p class="rounded-lg bg-emerald-50 p-4 text-emerald-900" role="status">{{ session('success') }}</p>@endif
        @if($errors->any())<div class="rounded-lg bg-red-50 p-4 text-red-900" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <div class="ops-stats">
            <x-operations-stat label="Learner choices that fit" :value="$assessment['score'] === null ? 'Unscored' : $assessment['score'].'%'" hint="Based on recorded enrolments" />
            <x-operations-stat label="Learners with clashes" :value="$assessment['clashing_learners']" :hint="$assessment['learner_count'].' learners with option records'" />
            <x-operations-stat label="Resource / block conflicts" :value="$assessment['hard_conflicts']" hint="Resolve before accepting" />
        </div>
        <section class="ops-section p-5"><h2 class="text-xl font-bold">Checks and assumptions</h2><p class="my-2">Assessment uses current school records. Resource allocations are provisional. Seat demand uses the larger of estimated group size and a suggested learner allocation; an alternative allocation may improve capacity.</p>
            @foreach($assessment['issues'] as $issue)<p class="my-2 text-red-700 dark:text-red-300">{{ $issue }}</p>@endforeach
            @foreach($assessment['warnings'] as $warning)<p class="my-2 text-amber-800 dark:text-amber-200">{{ $warning }}</p>@endforeach
            <p class="my-2">{{ $plan->configuration['compulsory_ids'] ? 'Compulsory subjects are excluded from these blocks.' : 'No compulsory subjects were selected.' }} A score is a planning estimate, not a finished timetable.</p>
        </section>
        <form method="POST" action="{{ route('admin.subject-option-plans.update', $plan) }}" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid gap-5 lg:grid-cols-2">
            @for($block = 0; $block < $plan->configuration['block_count']; $block++)
                <section class="ops-section p-5"><h2 class="text-xl font-bold">Block {{ chr(65 + $block) }}</h2><p class="mb-3">These groups run simultaneously.</p>
                @foreach($plan->arrangement as $key => $assigned)
                    @if($assigned === $block)
                    @php($allocation = $assessment['allocations'][$key])
                    <div class="border-t py-3"><strong>{{ $data['groups'][$key]['name'] }}</strong><p class="my-1 text-sm">{{ $teachers[$allocation['teacher_id']] ?? 'Teacher unresolved' }} · {{ $data['rooms'][$allocation['room_id']]['name'] ?? 'Room unresolved' }} · {{ $allocation['learners'] }} learners estimated</p>
                        <label class="text-sm">Move to <select name="arrangement[{{ $key }}]" class="rounded border p-2 dark:bg-brand-800">@for($destination = 0; $destination < $plan->configuration['block_count']; $destination++)<option value="{{ $destination }}" @selected((int) old('arrangement.'.$key, $assigned) === $destination)>Block {{ chr(65 + $destination) }}</option>@endfor</select></label>
                    </div>
                    @endif
                @endforeach
                </section>
            @endfor
            </div>
            <section class="ops-section p-5 space-y-4">
                <a class="ops-button" href="{{ route('admin.subject-option-plans.index', ['from' => $plan->id]) }}">Adjust subjects, groups or rooms & regenerate</a>
                <p>Save and rescore after moving groups. Acceptance always checks the submitted arrangement again. Saving an accepted plan returns it to draft.</p>
                <label class="flex items-start gap-3"><input type="checkbox" name="acknowledge" value="1"><span>I have reviewed learner clashes and the assumptions above. I understand this accepts a planning structure only; it does not publish a timetable or change learner enrolments.</span></label>
                <div class="flex flex-wrap gap-3"><button name="action" value="save" class="ops-button">Save draft & rescore</button><button name="action" value="accept" class="ops-button">Accept arrangement</button></div>
            </section>
        </form>
    </div>
</x-app-layout>
