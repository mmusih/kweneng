@php
    $user = auth()->user();
    $finance = \App\Support\StaffWorkflows::finance($user);
    $staffAccess = $user->isActive() && in_array($user->role, \App\Support\UserRoles::manageableStaff(), true);
    $purchasing = $user->isActive() && in_array($user->role, ['admin', 'headmaster', 'accounts_officer', 'office', 'inventory'], true);
    $leaveRecord = request()->route()?->parameter('leave');
    $expenseRecord = request()->route()?->parameter('claim');
    $hrContext = request()->routeIs('hr.*') || ($user->canManageHr() && $leaveRecord instanceof \App\Models\StaffLeaveRequest && $leaveRecord->staff->user_id !== $user->id);
    $financeContext = request()->routeIs('finance.*', 'accounts-officer.*') || (($finance || \App\Support\StaffWorkflows::approvesSpending($user)) && $expenseRecord instanceof \App\Models\StaffExpenseClaim && $expenseRecord->staff->user_id !== $user->id);
    $staffContext = request()->routeIs('staff.*') && ! $hrContext && ! $financeContext;
    $modules = [];
    $financeHome = $user->role === 'accounts_officer' ? 'accounts-officer.dashboard' : 'finance.dashboard';
    if ($user->canManageHr()) $modules[] = ['hr.dashboard', 'Human resources', $hrContext, 'users'];
    if ($finance) $modules[] = [$financeHome, 'Finance', $financeContext, 'bank'];
    if ($staffAccess) $modules[] = ['staff.dashboard', 'My workspace', $staffContext, 'badge-check'];
    if (!$finance && \App\Support\StaffWorkflows::approvesSpending($user)) $modules[] = ['finance.expenses.index', 'Expense approvals', request()->routeIs('finance.expenses.*'), 'document-report'];
    if (!$finance && $purchasing) $modules[] = ['finance.purchasing.index', 'Purchasing', request()->routeIs('finance.purchasing.*'), 'archive'];
    if ($user->role === 'parent') $modules[] = ['parent.receipts.index', 'Payments & receipts', request()->routeIs('parent.receipts.*'), 'document-report'];
    $tabs = [];
    if ($hrContext) {
        $tabs = [
            ['hr.dashboard', 'Overview', request()->routeIs('hr.dashboard')],
            ['hr.staff.index', 'Staff & documents', request()->routeIs('hr.staff.*')],
            ['hr.leave.index', 'Leave approvals', request()->routeIs('hr.leave.index', 'staff.leave.show')],
            ['hr.leave.settings', 'Leave settings', request()->routeIs('hr.leave.settings')],
            ['hr.types', 'Document settings', request()->routeIs('hr.types')],
        ];
    } elseif ($financeContext) {
        if ($finance) {
            $tabs[] = [$financeHome, 'Overview', request()->routeIs($financeHome)];
            $tabs[] = ['finance.payments.index', 'Payments & receipts', request()->routeIs('finance.payments.*')];
            $tabs[] = ['finance.accounts.index', 'Fee ledgers', request()->routeIs('finance.accounts.*')];
        }
        if ($finance || \App\Support\StaffWorkflows::approvesSpending($user)) $tabs[] = ['finance.expenses.index', 'Expenses & reimbursements', request()->routeIs('finance.expenses.*', 'staff.expenses.show')];
        if ($purchasing) {
            $tabs[] = ['finance.purchasing.index', 'Purchasing', request()->routeIs('finance.purchasing.*') && !request()->routeIs('finance.purchasing.suppliers')];
            $tabs[] = ['finance.purchasing.suppliers', 'Suppliers', request()->routeIs('finance.purchasing.suppliers')];
        }
        if ($user->role === 'accounts_officer') $tabs[] = ['accounts-officer.fees.index', 'Fee imports', request()->routeIs('accounts-officer.fees.*')];
    } elseif ($staffContext) {
        $tabs = [
            ['staff.dashboard', 'Overview', request()->routeIs('staff.dashboard')],
            ['staff.leave.index', 'My leave', request()->routeIs('staff.leave.*')],
            ['staff.expenses.index', 'My expenses', request()->routeIs('staff.expenses.*')],
        ];
    }
@endphp
@if(count($modules) && ! request()->routeIs('admin.*'))
    <div class="ops-nav">
        <nav aria-label="School workspaces" class="ops-modules">
            @foreach($modules as [$destination, $label, $active, $icon])
                <a href="{{ route($destination) }}" @if($active) aria-current="{{ request()->routeIs($destination) ? 'page' : 'true' }}" @endif><x-icon :name="$icon" class="w-4 h-4" />{{ $label }}</a>
            @endforeach
        </nav>
        @if(count($tabs))
            <nav aria-label="Workspace sections" class="ops-tabs">
                @foreach($tabs as [$destination, $label, $active])<a href="{{ route($destination) }}" @if($active) aria-current="{{ request()->routeIs($destination) ? 'page' : 'true' }}" @endif>{{ $label }}</a>@endforeach
            </nav>
        @endif
    </div>
@endif
