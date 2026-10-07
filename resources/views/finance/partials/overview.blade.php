    <div class="ops-shell">
        <div class="ops-stats">
            <x-operations-stat :href="route('finance.payments.index', ['status' => 'confirmed'])" label="Collected this month" :value="'P'.number_format($collected / 100, 2)" hint="Confirmed receipts only" />
            <x-operations-stat :href="route('finance.accounts.index')" label="Outstanding ledger fees" :value="'P'.number_format($outstandingFees / 100, 2)" :hint="$ledgerCount.' ledgers · credits excluded'" />
            <x-operations-stat :href="route('finance.expenses.index', ['status' => 'approved'])" label="Ready to reimburse" :value="'P'.number_format($approvedExpenses / 100, 2)" hint="Approved staff claims" />
            <x-operations-stat :href="route('finance.purchasing.index')" label="Supplier commitments" :value="'P'.number_format($supplierOutstanding / 100, 2)" hint="Approved / received orders, less payments" />
        </div>
        <div class="ops-cards">
            <x-operations-card :href="route('finance.payments.index')" title="Payments & receipts" description="Record payments, confirm collections, and issue or resend receipts." :action="$pendingPayments.' awaiting confirmation'" icon="bank" />
            <x-operations-card :href="route('finance.accounts.index')" title="Student fee ledgers" description="Open accounts, post charges, and trace each student's fee balance." action="Manage fee accounts" icon="academic-cap" />
            <x-operations-card :href="route('finance.purchasing.index')" title="Purchasing & suppliers" description="Turn approved requisitions into orders, deliveries, and supplier payments." :action="$readyRequisitions.' requisitions ready to order'" icon="archive" />
        </div>
        <div class="ops-grid">
            <section class="ops-section"><header><div><h2>Finance work queue</h2><p>Open a queue to complete the next step</p></div></header>
                <a class="ops-row" href="{{ route('finance.payments.index', ['status' => 'pending']) }}"><div><strong>Confirm incoming payments</strong><p>Verify payment details before issuing a receipt</p></div><span class="ops-badge" data-status="pending">{{ $pendingPayments }} pending</span></a>
                <a class="ops-row" href="{{ route('finance.expenses.index', ['status' => 'submitted']) }}"><div><strong>Expense approvals</strong><p>Administrator or headmaster review</p></div><span class="ops-badge" data-status="submitted">{{ $pendingExpenses }} pending</span></a>
                <a class="ops-row" href="{{ route('finance.purchasing.index', ['status' => 'submitted']) }}"><div><strong>Purchase order approvals</strong><p>Approve spending before ordering</p></div><span class="ops-badge" data-status="submitted">{{ $pendingOrders }} pending</span></a>
                <a class="ops-row" href="{{ route('finance.expenses.index', ['status' => 'approved']) }}"><div><strong>Record staff reimbursements</strong><p>Approved claims awaiting payment</p></div><strong>P{{ number_format($approvedExpenses / 100, 2) }}</strong></a>
            </section>
            <section class="ops-section"><header><div><h2>Month to date</h2><p>{{ today()->startOfMonth()->format('d M') }} – {{ today()->format('d M Y') }} · BWP</p></div></header>
                @forelse($methods as $method)<div class="ops-row"><strong>{{ ucwords(str_replace('_', ' ', $method->method)) }} collections</strong><strong>P{{ number_format($method->total / 100, 2) }}</strong></div>@empty<p class="ops-empty">No confirmed collections this month. Record a payment to begin.</p>@endforelse
                <div class="ops-row"><div><strong>Recorded outgoings</strong><p>Paid staff claims and supplier payments</p></div><strong>P{{ number_format($outgoing / 100, 2) }}</strong></div>
                <div class="ops-row"><div><strong>Collections less recorded outgoings</strong><p>Activity in this system; excludes other bank movements</p></div><strong>P{{ number_format(($collected - $outgoing) / 100, 2) }}</strong></div>
            </section>
            <section class="ops-section"><header><div><h2>Recent payments</h2><p>Latest six payment records</p></div><a href="{{ route('finance.payments.index') }}">View all →</a></header>
                @forelse($payments as $payment)<a class="ops-row" href="{{ route('finance.payments.show', $payment) }}"><div><strong>{{ $payment->payer_name }}</strong><p>{{ $payment->receipt_number ?? 'Receipt pending' }} · {{ $payment->paid_on->format('d M Y') }}</p></div><div class="ops-row-end"><strong>P{{ number_format($payment->amount_minor / 100, 2) }}</strong><x-workflow-status :status="$payment->status" /></div></a>@empty<p class="ops-empty">No payments recorded yet. Use “Record payment” to capture your first collection.</p>@endforelse
            </section>
            <section class="ops-section"><header><div><h2>Expenses awaiting action</h2><p>Submitted and approved claims · oldest first</p></div><a href="{{ route('finance.expenses.index') }}">View all →</a></header>
                @forelse($expenses as $claim)<a class="ops-row" href="{{ route('staff.expenses.show', $claim) }}"><div><strong>{{ $claim->staff->name }}</strong><p>{{ $claim->description }}</p></div><div class="ops-row-end"><strong>P{{ number_format($claim->amount_minor / 100, 2) }}</strong><x-workflow-status :status="$claim->status" /></div></a>@empty<p class="ops-empty">No claims need attention. Staff submit claims from My workspace.</p>@endforelse
            </section>
        </div>
        <section class="ops-section"><header><div><h2>Purchasing in progress</h2><p>From approval to delivery and payment · oldest six orders</p></div><a href="{{ route('finance.purchasing.index') }}">View all →</a></header>
            @forelse($orders as $order)<a class="ops-row" href="{{ route('finance.purchasing.show', $order) }}"><div><strong>{{ $order->reference() }} · {{ $order->title }}</strong><p>{{ $order->supplier_name }}</p></div><div class="ops-row-end"><strong>P{{ number_format($order->total_minor / 100, 2) }}</strong><x-workflow-status :status="$order->status" /></div></a>@empty<p class="ops-empty">No open purchase orders. Approved requisitions are available in Purchasing.</p>@endforelse
        </section>
        <p class="ops-empty">Fee totals cover opened ledgers only. Imported balances remain available in the accounts dashboard.</p>
    </div>
