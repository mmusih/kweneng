# HR, finance, and staff workspaces

The workspace bar replaces the loose staff and finance links. It only exposes areas the current user can access, and is hidden on admin screens so the admin dashboard concentrates on account management and academic setup. A second row shows sections within the active workspace.

- **Human resources** (`/hr`): active staff, leave awaiting review, document follow-up, staff away today, and contracts ended or ending within 90 days. Administrators create HR personnel through User Management by selecting the HR role. HR personnel land here after login and password changes. Active administrators and existing explicitly delegated HR staff retain access.
- **Accounts officer dashboard** (`/accounts-officer/dashboard`): includes the finance overview, confirmed collections, fee ledgers, reimbursements, supplier commitments, payment confirmation, purchasing, fee imports, and student results access. Accounts officers visiting `/finance` are redirected here. Administrators retain existing finance and spending-approval permissions, without finance cards on the admin dashboard.
- **My workspace** (`/staff`): personal leave and expense requests, current decisions, and links to submit requests. An active linked staff profile is needed for new submissions. Existing requests remain visible to their owner.

Headmasters retain expense approval and purchasing access. Office and inventory users retain purchasing access. The accounts officer's existing fee-import and result-access tools remain available directly on the integrated accounts officer dashboard. Parent receipt access is unchanged.

Leave, expense, and purchase-order lists support status filters. Review screens return managers to their management queue; personal requests return to self-service. Existing rules against self-approval and existing document permissions continue to apply.

## Interpreting finance figures

Amounts are shown in Botswana pula. Collections exclude pending and reversed payments. Month-to-date outgoings include recorded staff reimbursements and supplier payments. Their difference is activity recorded in this system, not a reconciled bank balance.

Outstanding ledger fees sum positive balances independently; a credit on one student's account does not reduce another student's debt. Only confirmed fee allocations on or after a ledger's opening date reduce that ledger. Imported balances are not added to the ledger total.

Supplier commitments include approved and received orders, less recorded payments. Submitted, rejected, cancelled, and fully paid orders do not contribute to this figure.

Apply `2026_10_07_000001_add_hr_role_and_subject_option_plans.php` to enable the HR role and planner storage. Build assets with `npm run build`. The migration passed the isolated SQLite tests and was subsequently applied successfully to the local school MySQL database on 7 October 2026.

## Verification

`OperationsDashboardTest` covers dashboard rendering, role boundaries, personal request isolation, HR counts, finance totals, and status-filter behavior. Existing `HrFinanceTest` and `StaffWorkflowsTest` exercise payment confirmation/reversal, private documents, leave decisions, reimbursements, and purchasing.
