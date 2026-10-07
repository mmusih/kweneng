<x-app-layout>
    <x-slot name="header"><div class="ops-hero"><div><p class="ops-eyebrow">Staff self-service</p><h1>My workspace</h1><p>Your leave, expense claims, and decisions from HR and finance.</p></div></div></x-slot>
    <div class="ops-shell">
        @unless($staff)<div class="ops-notice">Ask HR to link an active staff profile to your account before submitting a request. You can still view your existing requests below.</div>@endunless
        <div class="ops-grid">
            <x-operations-card :href="route('staff.leave.index')" title="My leave" description="Check your annual balance, request time off, and follow approval decisions." :action="$pendingLeave.' requests awaiting review'" icon="calendar" />
            <x-operations-card :href="route('staff.expenses.index')" title="My expenses" description="Submit supporting receipts and track claims through approval and reimbursement." :action="'P'.number_format($pendingExpenses / 100, 2).' awaiting approval or payment'" icon="document-report" />
            <section class="ops-section"><header><h2>Recent leave requests</h2><a href="{{ route('staff.leave.index') }}">View all →</a></header>
                @forelse($leaveRequests as $leave)<a class="ops-row" href="{{ route('staff.leave.show', $leave) }}"><div><strong>{{ $leave->type->name }}</strong><p>{{ $leave->starts_on->format('d M Y') }} – {{ $leave->ends_on->format('d M Y') }} · {{ $leave->half_days / 2 }} days</p></div><x-workflow-status :status="$leave->status" /></a>@empty<p class="ops-empty">No leave requests yet. Open My leave to check allowances and request time off.</p>@endforelse
            </section>
            <section class="ops-section"><header><h2>Recent expense claims</h2><a href="{{ route('staff.expenses.index') }}">View all →</a></header>
                @forelse($expenses as $claim)<a class="ops-row" href="{{ route('staff.expenses.show', $claim) }}"><div><strong>{{ $claim->description }}</strong><p>{{ $claim->reference() }} · P{{ number_format($claim->amount_minor / 100, 2) }}</p></div><x-workflow-status :status="$claim->status" /></a>@empty<p class="ops-empty">No expense claims yet. Open My expenses to submit a claim with its receipt.</p>@endforelse
            </section>
        </div>
    </div>
</x-app-layout>
