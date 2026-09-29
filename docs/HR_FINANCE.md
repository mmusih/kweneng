# Kweneng HR, receipting and staff workflows

## Included

- Staff directory for teaching and non-teaching employees, with optional links to existing ERP users. Existing teacher assignments and roles are preserved.
- Admin-managed HR access in addition to a user's existing role. HR access does not confer finance access. Only active users can access the new modules.
- Configurable document checklists: permission to teach, BOTEPCO registration/licence, work and residence permits, passports, Omang, employment contracts, qualifications, optional BQA verification and references. Additional types can be added.
- Private PDF/JPG/PNG uploads (10 MB limit), reference/issuer/dates, explicit non-expiring documents, manual verification, and retained document versions.
- Missing, awaiting verification, valid, expiring, expired and not-applicable statuses. A renewal in progress does not change validity. An unverified replacement does not supersede the previous verified document.
- Renewal owner, application reference/date, status, notes and reasons for exceptions. Contract end dates appear in the HR dashboard.
- Daily HR email digest, with per-recipient daily deduplication. Assigned HR users see their assigned and unassigned requirements; administrators receive the full digest. Emails contain counts and a login link, not personnel attachments.
- General incoming payments for parents, individuals and organisations; optional parent portal recipient and student allocations; configurable fee, other-income and refundable-deposit categories.
- Pending/confirmed payments, exact integer-thebe calculations, multiple allocation lines, request idempotency, immutable receipt numbers, PDF downloads, email/resend and email failure status.
- Only confirmation issues a receipt. Receipt numbers use the payment ID, so gaps for pending or cancelled records are expected and numbers are never reused.
- Administrator-only reversals retaining original records and receipt numbers; reversed payments no longer reduce fee balances.
- Student fee ledgers with explicit opening balances and dates, charges, administrator credits, and confirmed fee allocations. Other categories do not reduce tuition balances. Advance fee payments can create a credit balance.
- Parent portal and authenticated app receipt history/download, pagination and current-family access checks. Receipt access is independent of academic-results blocking.
- Audit events for important HR and finance actions in `erp_audit_events`.

## Enable in an existing installation

1. Back up the application database and private document storage using the school's normal process.
2. Deploy the new code and build web assets with `npm run build`.
3. Apply `database/migrations/2026_09_21_000001_create_hr_and_receipting_tables.php`. For an existing checkout with unrelated pending migrations, review those separately; this feature can be applied with `php artisan migrate --path=database/migrations/2026_09_21_000001_create_hr_and_receipting_tables.php`.
4. Ensure `storage/app/private` is writable and is not publicly exposed. Keep it in encrypted backups with the database. Uploads are served only through the HR-authorised controller.
5. Configure a working Laravel mail transport and sender. The default log/array mailers do not deliver email. The UI's sent status means the configured transport accepted the message; it does not prove inbox delivery. Failed sends can be retried without generating another receipt.
6. Ensure the server runs Laravel's scheduler every minute. The `hr:document-reminders` command is scheduled for 07:00 Africa/Gaborone. Use a persistent cache store for reminder deduplication and scheduler locks.
7. Sign in as an administrator, open **Staff & documents**, add profiles, link existing accounts as appropriate, and grant HR access only to authorised staff. Suggested checklists need HR review for applicability. Updating citizenship or teaching role adds new suggestions but intentionally does not erase previously required documents; mark obsolete requirements not applicable with a reason.
8. Accounts officers and administrators use **Payments & receipts**. Confirm funds only after checking cash/bank/card/other payment evidence. Non-cash methods require a transaction reference.
9. Build and distribute the updated parent app through the normal release process. It adds **Payments & receipts** in the portal menu and a receipt button on the fees screen; downloads use authenticated API requests.

## Changing over from imported fee balances

For each student, Accounts chooses an opening date and verifies the balance immediately before that date. The opening balance includes all earlier activity. Record payments on or after that date in the new ledger; do not include them again in the opening balance. Review any existing pending payment before confirmation.

Opening a ledger is explicit and does not automatically import an ambiguous historic balance. Existing imported records are retained as reference. The parent fees API and dashboard use the ledger once it exists, even if another balance spreadsheet is imported later. Existing academic-result blocking remains a separate Accounts decision.

Only payment categories with treatment `fees` reduce that ledger. Initial transport, uniforms, meals and other categories are general receipts; they do not create separate receivable accounts. A charge creates an amount owed; a receipt records money received.

## Receipt privacy

Accounts explicitly selects an optional parent recipient. A parent receipt may include only students currently linked to that parent. Sponsorship receipts covering unrelated families should remain with the sponsor (email/Accounts download), or be split into separate receipts. Linking a student alone does not grant every parent access to a payer's complete receipt. Removing a family link revokes access to receipts containing that student.

## Botswana checklist sources

Checked during planning in September 2026; these are configurable operational aids, not a complete legal compliance determination:

- Permission to teach: https://www.gov.bw/learning-and-teaching/teaching-permit-application
- BOTEPCO registration/licensing: https://botepco.org.bw/our-services/ and https://trls.gov.bw/welcome
- Work permits (including teaching/BQA supporting documents and six-month renewal application guidance): https://www.gov.bw/residency-and-work/work-permit-application?page=0
- Residence permits: https://www.gov.bw/taxonomy/term/115

Work-permit reminders default to 210 days to allow preparation before the six-month renewal point. Record actual document expiry dates; the system does not infer validity periods or automatically treat an application as approval. HR should confirm how current BOTEPCO and permission-to-teach requirements apply to the school and each employee.

## Leave, expenses and purchasing

The second migration, `2026_09_21_000002_create_staff_workflows_and_purchasing.php`, adds these web workflows. Apply it after the first migration, using its explicit path when other unrelated migrations are pending. It does not import staff, allocate leave, approve requests or record real payments automatically.

### Leave

- Staff use **My leave** to submit dates, a reason and optional private supporting evidence. HR can submit on behalf of staff, including employees without a login.
- **Leave approvals** gives authorised HR users the register, annual allowances, type settings and non-working dates. Staff profiles must be active and linked for self-service.
- Suggested leave types are initially disabled. HR must confirm the school policy, choose which weekdays count (including Saturdays/Sundays where appropriate), choose whether to exclude listed non-working dates, set evidence requirements and enable each applicable type. No Botswana statutory leave entitlements are hard-coded.
- Allowances are explicit totals for each employee/type/calendar year, in whole or half days. They do not accrue automatically. Pending requests reserve allowance; approved requests consume it. Rejection and cancellation release it. A total cannot be reduced below approved and pending leave.
- Full-day, morning and afternoon requests are supported. Overlapping pending/approved leave is blocked, while opposite half-days on the same date are allowed. Requests spanning calendar years must be split.
- A separate HR user must approve a request: neither its employee nor the person who submitted it on their behalf can approve it. Employees may withdraw pending leave; HR may cancel approved leave for another employee with a reason.
- Counting rules are fixed once a type has requests. Create a new type for a policy change. Adding/removing non-working dates does not silently recalculate existing requests; cancel and resubmit an affected request with an audit explanation if a correction is required.
- View balances for another year using the year selector. Supporting evidence is accessible only to the employee and authorised HR users, with downloads audited.

### Expense claims

- Staff use **My expenses** to claim a BWP amount, date, category, department and business purpose, with a PDF/JPG/PNG receipt or evidence (10 MB maximum).
- The headmaster or an administrator approves/rejects claims through **Expense approvals**. Self-approval is blocked.
- Accounts officers and administrators record full reimbursements only after approval, with date, method and reference. A claimant cannot record their own reimbursement, and the reimbursement cannot predate approval.
- Repeated form submissions do not create duplicate claims. Claims retain their original evidence and decision history; rejected claims are resubmitted as new claims rather than editing the approved history.
- Only the claimant, Accounts, headmaster and administrators can view a claim or supporting evidence. HR access alone does not grant access to everyone’s expenses.

### Purchasing

- **Purchasing** connects to approved existing requisitions. Accounts/admin maintain the supplier directory and create an order for the full requisition, entering agreed unit prices and any separately described delivery/tax cost.
- Quantities, supplier details, descriptions and agreed prices are saved as snapshots. Quantities use hundredths and money uses thebe; each line is rounded to the nearest thebe, with overflow and maximum-total checks.
- Each requisition can have only one active order. A rejected/cancelled order remains in history and may be replaced.
- The headmaster or another administrator approves orders. Neither the order’s preparer nor the requisition’s requester may approve it. Approval advances the existing requisition to **Ordered**.
- Inventory, Office or an administrator records full delivery and a delivery note. This advances the requisition to **Fulfilled**. Inventory’s existing status editor cannot bypass an active purchase order’s workflow.
- Accounts attaches a supplier invoice whose total matches the approved order. Invoice references cannot be reused for the same supplier. Private invoice downloads are limited to the purchasing roles.
- Supplier payments can be recorded in full or in instalments after delivery and invoice matching. Overpayment, repeated submission and repeated transaction references on the same order are blocked. The order becomes **Paid** only when its outstanding balance is zero.
- Purchase-order PDFs show draft/rejected/cancelled status clearly. Creating or approving an order does not send it to a supplier.

| Action | Authorised users |
| --- | --- |
| Submit own leave / expense claim | Active staff with a linked active staff profile |
| Manage leave rules, allowances and requests | Administrators and users granted HR access |
| Review expenses and purchase orders | Administrator or headmaster, subject to self-approval checks |
| Prepare orders, manage suppliers, attach invoices, record payments | Administrator or Accounts officer |
| Record full delivery | Administrator, Office or Inventory |
| View purchasing records and invoices | Administrator, Accounts officer, headmaster, Office or Inventory |

All expense reimbursements and supplier payments are **records of payments already completed outside the ERP**. These actions do not initiate bank transfers, issue cheques, or send external messages. Audit events record decisions, uploads and payment records.

## Boundaries and next phase

Payroll, bank reconciliation, budgets, bulk invoices, a general ledger, formal refund approvals/payments, and downloadable account statements remain further work. A reversal corrects an erroneous incoming payment record; it is not a refund and never transfers money. Deposit receipts are classified separately but their repayment needs the later refund workflow.

This purchasing version uses one supplier and one full-delivery confirmation per order; partial deliveries, split-supplier orders, deposits before delivery and supplier credit notes remain later extensions. Stock counts are not incremented automatically because existing inventory uses integer quantities and separately tracked assets; the receiving user must update inventory after checking units and asset records. Expense claims are reimbursed in full. Neither module replaces an accounting general ledger.

Leave has explicit annual allowances rather than automatic accrual/carry-forward, and uses each leave type’s chosen workweek rather than per-employee rotating calendars. These new staff workflows are in the web ERP; they have not been added to the teacher mobile app.

The app code is updated, but an installed app does not change until a new build is released. No live external messages, production migration or app publication is part of the automated tests.

## Validation

`php artisan test --filter=HrFinanceTest` tests the full HTTP workflows using an isolated in-memory SQLite database. Coverage includes permissions, private files, citizenship checklists, verification and version history, expiry tracking, digest deduplication, exact allocation totals, repeated submissions, receipt PDFs, pending payments, family isolation, fee cutover, reversals and mail failures.

`php artisan test --filter=StaffWorkflowsTest` covers leave calendars and holiday boundaries, half days, overlaps, pending reservations, annual limits, policy settings, document privacy, self-approval, expenses, purchase snapshots, ordering and delivery transitions, invoice matching, partial payments, duplicate references and overpayment. Tests run only against the isolated test database. The second migration also aligns SQLite's operational-role constraint with the roles already supported in MySQL.

Web assets: `npm run build`. Parent app: `flutter test test/receipts_screen_test.dart test/portal_redesign_test.dart` checks receipt pagination, empty/error states, retry, small-screen layout and existing portal navigation. Test against the configured backend before app distribution.
