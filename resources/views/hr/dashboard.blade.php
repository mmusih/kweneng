<x-app-layout>
    <x-slot name="header">
        <div class="ops-hero">
            <div><p class="ops-eyebrow">People & workplace</p><h1>HR Dashboard</h1><p>Keep staff records current, review leave, and plan for the people who keep school running.</p></div>
            <a class="ops-button" href="{{ route('hr.staff.create') }}"><x-icon name="plus" class="w-4 h-4" /> Add staff member</a>
        </div>
    </x-slot>
    <div class="ops-shell">
        <div class="ops-stats">
            <x-operations-stat :href="route('hr.staff.index')" label="Active staff" :value="$activeStaff" hint="Browse staff directory" />
            <x-operations-stat :href="route('hr.leave.index', ['status' => 'submitted'])" label="Leave awaiting review" :value="$pendingLeave" hint="Open approval queue" />
            <x-operations-stat :href="route('hr.staff.index')" label="Documents needing attention" :value="$attentionCount" hint="Review compliance checklist" />
            <x-operations-stat :href="route('hr.leave.index', ['status' => 'approved'])" label="On approved leave today" :value="$awayCount" hint="Includes half-day leave" />
        </div>
        <div class="ops-cards">
            <x-operations-card :href="route('hr.staff.index')" title="Staff & documents" description="Employee profiles, contracts, required documents, and renewals." action="Manage staff" icon="users" />
            <x-operations-card :href="route('hr.leave.index')" title="Leave approvals" description="Review requests, check balances, and record leave decisions." action="Manage leave" icon="calendar" />
            <x-operations-card :href="route('hr.leave.settings')" title="Policies & allowances" description="Set leave types, annual allowances, and non-working dates." action="Configure leave" icon="clipboard" />
        </div>
        <div class="ops-grid">
            <section class="ops-section"><header><div><h2>Leave awaiting review</h2><p>Oldest requests first · {{ $pendingLeave }} pending</p></div><a href="{{ route('hr.leave.index', ['status' => 'submitted']) }}">View all →</a></header>
                @forelse($leaveRequests as $leave)
                    <a class="ops-row" href="{{ route('staff.leave.show', $leave) }}"><div><strong>{{ $leave->staff->name }}</strong><p>{{ $leave->type->name }} · {{ $leave->starts_on->format('d M') }} – {{ $leave->ends_on->format('d M Y') }}</p></div><div class="ops-row-end"><strong>{{ $leave->half_days / 2 }} days</strong><x-workflow-status :status="$leave->status" /></div></a>
                @empty<p class="ops-empty">You're up to date. New leave requests will appear here for review.</p>@endforelse
            </section>
            <section class="ops-section"><header><div><h2>Document follow-up</h2><p>Active staff · urgent requirements first</p></div><a href="{{ route('hr.staff.index') }}">View all →</a></header>
                @forelse($attention as $requirement)
                    <a class="ops-row" href="{{ route('hr.staff.show', $requirement->staff_profile_id) }}"><div><strong>{{ $requirement->staff->name }}</strong><p>{{ $requirement->type->name }} @if($requirement->currentDocument()?->expires_on) · {{ $requirement->currentDocument()->expires_on->format('d M Y') }} @endif</p></div><x-workflow-status :status="$requirement->status()" /></a>
                @empty<p class="ops-empty">No document requirements need attention. Add staff profiles and their required documents to keep this checklist current.</p>@endforelse
            </section>
            <section class="ops-section"><header><div><h2>Staff away today</h2><p>{{ today()->format('l, d M Y') }}</p></div></header>
                @forelse($away as $leave)
                    <a class="ops-row" href="{{ route('staff.leave.show', $leave) }}"><div><strong>{{ $leave->staff->name }}</strong><p>{{ $leave->type->name }} · {{ $leave->portion === 'full' ? 'Full day' : strtoupper($leave->portion) }}</p></div><small>Through {{ $leave->ends_on->format('d M') }}</small></a>
                @empty<p class="ops-empty">No approved leave scheduled for today.</p>@endforelse
            </section>
            <section class="ops-section"><header><div><h2>Contract renewals</h2><p>{{ $contractCount }} ended or ending within 90 days</p></div><a href="{{ route('hr.staff.index') }}">Staff directory →</a></header>
                @forelse($contracts as $person)
                    <a class="ops-row" href="{{ route('hr.staff.show', $person) }}"><div><strong>{{ $person->name }}</strong><p>{{ $person->position }}</p></div><div class="ops-row-end"><strong>{{ $person->contract_ends_on->format('d M Y') }}</strong><small>{{ $person->contract_ends_on->lt(today()) ? 'Contract ended' : 'Renewal approaching' }}</small></div></a>
                @empty<p class="ops-empty">No recorded contracts are due for renewal in the next 90 days.</p>@endforelse
            </section>
        </div>
    </div>
</x-app-layout>
