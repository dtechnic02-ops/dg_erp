# DG ERP V1 Full System Audit and Freeze Report

Audit date: 2026-08-15  
Audit type: repository-only, read-only implementation audit  
Decision basis: source, routes, controllers, models, services, middleware, migrations, seeders, views, sidebar, JavaScript, relevant configuration, tests, and all existing `docs/` files  
Excluded: `vendor/`, `node_modules/`, runtime storage/cache, live database data, migrations, seed execution, and authenticated browser testing

## Classification legend

- **V1 READY** — connected implementation exists and repository evidence supports the V1 workflow.
- **NEEDS FIX BEFORE V1** — material security, financial-integrity, or release-verification issue.
- **PARTIAL** — useful implementation exists, but the stated area is incomplete or insufficiently verified.
- **DOCUMENTATION ONLY** — described by standards or menu taxonomy without a connected implementation.
- **FUTURE / V2** — not required to host the presently implemented V1 scope; deliberately backlog it rather than implying availability.

Repository evidence is not proof of deployed schema state, production configuration, mail/payment connectivity, browser responsiveness, or real-data reconciliation. Those items are explicitly marked for verification.

## 1. Executive summary

DG ERP is a substantial multi-company Laravel ERP, not a route-only prototype. The audit found 477 registered routes (75 Admin, 395 Company, 7 public/framework), 88 controllers, 92 models, 103 services, 202 migrations, 272 Blade views, 28 documentation files, and 61 PHP test/support files. Implemented V1 workflows include platform administration, subscription lifecycle, company/staff controls, master data, quotation conversion, sales, purchase, stock, delivery, CRM, Accounting Core, official statements, loan, and a basic payroll workflow.

The system is **NOT READY for V1 freeze/hosting** on repository evidence alone. The principal blockers are: inconsistent named-permission enforcement across many company mutation routes; direct account/party balance mutation remaining in loan, contra, and legacy invoice-payment paths instead of a single governed ledger service; no fresh full-suite or production-engine verification after the latest quotation/country/report work; and no verified deployment/schema/configuration check. These are release blockers because they affect authorization, financial consistency, and confidence in the exact release state.

The earlier `AUDIT_FINANCIAL_PRODUCTION_READINESS.md` is stale where it says GL, Trial Balance, P&L, Balance Sheet, and Purchase Return accounting are missing/unverified. Current source now contains `AccountingReportController`, `OfficialAccountingReportService`, four report views, `OfficialAccountingReportsTest`, Purchase Return builder/profile/integration, the `SUPPLIER_RETURN_RECEIVABLE` chart account, and Purchase Return tests. Its warnings about direct balance mutation and mixed authorization remain materially relevant.

No application code or database was changed by this audit.

## 2. Full module inventory

### Platform / Super Admin

- **STATUS:** **PARTIAL / NEEDS FIX BEFORE V1**.
- **Routes/controllers:** login/logout; Admin dashboard; company registration review; approve/reject; company list/show/delete/block/unblock/limits; OTP-based company-admin password reset; countries; platform settings; subscription administration; Super Staff; platform users.
- **Models/tables/views:** `users`, `roles`, `companies`, `company_registrations`, permission tables, countries, platform settings/social/SMTP/gateway tables, and corresponding Admin views.
- **Permissions:** platform-scoped permission vocabulary and `EnsurePlatformPermission`/`PlatformAuthorizationService` exist. Some Admin routes still rely only on `platform.user`, while controllers perform mixed checks.
- **Tests:** country foundation and general security tests exist; the Admin readiness audit says dedicated Admin workflow coverage is absent.
- **Missing/notes:** real payment gateway execution/webhooks are not implemented; SMTP test is settings-driven but was not exercised; legacy Admin views/controllers remain; production login/session/browser verification is required.

### Subscription

- **STATUS:** **V1 READY** for manual/admin-managed subscriptions; **FUTURE / V2** for live automated billing.
- **Routes/controllers:** plan create/update/activate/deactivate; free trial; renew; upgrade; downgrade; expire; cancel; manual payment; verify/approve/reject; invoice; reports; company subscription/payment pages.
- **Models/tables:** billing cycles, subscription plans/options, company subscriptions, payments, histories; legacy `plans`, `subscriptions`, and `payments` compatibility surfaces also remain.
- **Gating:** subscription middleware and service gating are verified for CRM, loan, HR, and delivery. Sidebar also checks subscription availability.
- **Tests/documentation:** extensive architecture/database/module standards exist, but no dedicated subscription feature test files were found.
- **Missing/notes:** no live gateway API/webhook/recurring charge workflow. Migration-era duplicate models/tables require cleanup planning, not release-time deletion.

### Company core

- **STATUS:** **PARTIAL**.
- **Implemented:** dashboard, profile/edit, staff list/create/edit/block/unblock/delete/reset, direct staff permission allow/deny/revoke, module menu visibility, financial years, business date, Nepal AD/BS conversion service/components, maintenance actions.
- **Authorization:** company isolation middleware and permission services exist; company admin override is intentional in newer permission tests. Route-level permission coverage is inconsistent outside selected modules.
- **Job roles:** `role_id`, `job_role`, permission-role pivot, direct user permissions, and menu-visibility service coexist. This is functional but conceptually duplicated and documentation-sensitive.
- **Missing/notes:** no independent Job Role CRUD workflow was verified; `role_create.blade.php` appears legacy. Maintenance contains powerful reset operations and needs explicit production verification and least-privilege review.

### Master data

- **STATUS:** **V1 READY** with documentation gaps.
- **Implemented:** units, categories, brands, products, product Excel import, Excel/PDF export, service categories, services, customers, suppliers, operational accounts/cash accounts, VAT.
- **Integration:** products connect to stock/opening stock/accounting; customers/suppliers connect to operational and official opening balances; service/product lines are supported in sales, purchase, quotation, and delivery.
- **Tests:** focused tests cover products, customers, suppliers, accounts, VAT and permission behavior.
- **Missing/notes:** standalone guides are largely absent. The historical `banks` to `accounts` migration and separate `cash_accounts` surface are legacy/duplicate architecture to document.

### Inventory

- **STATUS:** **V1 READY** for current purchase/sales/returns/opening-stock scope; **PARTIAL** operationally.
- **Implemented:** `stock_transactions`, `stock_movements`, inventory valuations, sales cost snapshots, current product stock, stock ledger/PDF, stock synchronization, purchase stock-in, sales stock-out, sales-return restoration, purchase-return reduction, opening stock, COGS posting and reversal.
- **Tests:** purchase unit cost, inventory valuation, sales COGS/cancellation/return valuation, and opening-stock accounting coverage exists.
- **Missing:** no Goods Receive/GRN implementation. Stock-sync is an administrative correction surface and requires controlled operational use.

### Quotation

- **STATUS:** **V1 READY**.
- **Implemented:** create Draft, edit/delete Draft only, approve, print, atomic Generate Invoice, row locking, request validation, generated Sales linkage, converter/approver audit fields, and duplicate conversion protection via status plus `sales_invoice_id`.
- **Behavior:** quotation has no stock/accounting effect; converted Sales invokes normal sales behavior. AD/BS, Business Date and active Financial Year are integrated.
- **Evidence:** 10 routes, controller/model/items/migration/views, frozen quotation standard, and `QuotationModuleTest`.
- **Missing/notes:** no standalone end-user guide; CRM linkage is navigation/customer-context rather than a verified opportunity-to-quotation conversion workflow.

### Sales

- **STATUS:** **V1 READY**, subject to global authorization/release blockers.
- **Implemented:** invoice/items, product/service lines, numbering by FY, Business Date/AD-BS, payments, cancellation, customer transactions/statements, VAT, stock, inventory valuation/COGS, product/service returns, refunds/adjustments, print/list print, accounting posting/reversal.
- **Tests:** strong targeted coverage for permissions, AD/BS, COGS, payments, cancellation rollback, returns/refunds, duplicate cancellation, and accounting.
- **Missing/notes:** legacy `invoice_payments` route/controller overlaps the newer `sales_payments` implementation and directly increments an account; it must be retired or proven unreachable before V1.

### Purchase

- **STATUS:** **V1 READY**, subject to global authorization/release blockers.
- **Implemented:** invoices/items, product/service lines, payments, supplier transactions/ledger/statement, VAT, stock, returns, refunds/adjustments, cancellation, print, FY/Business Date, official accounting and reversal.
- **Purchase Return accounting:** current code posts through builder/profile/integration; `SUPPLIER_RETURN_RECEIVABLE` is seeded; cancellation reverses; refund settlement requires the posted return entry.
- **Tests:** purchase product/service, stock unit cost, payment permissions/accounting, cancellation reversal, return valuation, receivable migration, and return-refund accounting.
- **Missing/notes:** no Goods Receive/GRN stage; supplier ledger and statement overlap should be explained in guides.

### Delivery

- **STATUS:** **PARTIAL**.
- **Implemented:** sales-linked delivery notes/items, create, process, complete, cancel, status history, photos, signature, PDF generation, customer email, and print/view flows. It is subscription-gated.
- **Stock behavior:** delivery tracks planned/delivered quantities against Sales items; no independent stock movement is created, consistent with stock already leaving on Sales.
- **Tests:** no dedicated Delivery feature test was found.
- **Missing/notes:** email/filesystem/PDF behavior and duplicate/over-delivery concurrency need release verification; no delivery user/technical guide exists.

### CRM

- **STATUS:** **PARTIAL**.
- **Implemented:** dashboard, customer relationships/leads, contacts, opportunities, won/lost, follow-ups, meetings, tasks, notes, attachments, preview/download, archive/cancel, status histories, numbering/configuration, and subscription/permission gating.
- **Tables/views:** full CRM table family and UI are present.
- **Integration:** CRM reuses Customers and shows Quotation navigation, but no proven Lead/Opportunity → Quotation or Sales conversion service was found.
- **Tests:** no dedicated CRM feature tests were found.
- **Missing/notes:** CRM report/export permissions exist without a verified report/export route; CRM standard exists, but user and technical guides do not.

### Accounting

- **STATUS:** **PARTIAL / NEEDS FIX BEFORE V1**.
- **V1 READY pieces:** operational accounts and transactions; income/categories; expense/categories; journal lifecycle (draft, submit, approve/reject, post, cancel/reverse, lock/unlock, audit); contra; opening balance lifecycle; central Chart of Accounts; balanced immutable accounting entries/lines; idempotent posting/reversal; official GL, Trial Balance, P&L and Balance Sheet from Accounting Core; VAT report.
- **Integrations:** sales/revenue/COGS/payment/return/refund; purchase/payment/return/refund; income; expense; journal; customer/supplier opening balances; product opening stock; loan.
- **Tests:** the strongest area of the repository, including posting, reversal, official reports, concurrency probes, and module integrations.
- **Blockers:** contra and loan controllers still mutate balances directly; route/action permission enforcement is mixed; production-engine concurrency and real-data reconciliation were not run.
- **Documentation conflict:** Accounting Report v2, Accounting Core, and Opening Balance documents each use competing final-authority wording; the implemented architecture is broadly compatible, but hierarchy needs Business Owner clarification.

### Loan

- **STATUS:** **PARTIAL / NEEDS FIX BEFORE V1 if enabled**.
- **Implemented:** party accounts, loan taken/given, payments, principal/interest/fine, compulsory saving ledger, saving withdrawal, next-payment dates, cancellation, print, FY/Business Date, accounting integration and tests.
- **Blocker:** controllers directly increment/decrement party and operational-account `current_balance` while also creating ledger/accounting records. This dual mutation architecture requires code correction and reconciliation proof before enabling Loan in V1.
- **Tests/documentation:** loan end-to-end and accounting tests plus a frozen module standard exist; no user guide exists.

### HR / Payroll

- **STATUS:** **PARTIAL**.
- **Implemented:** employee accounts/status, salary sheets, manual allowance/bonus/overtime/deduction fields, net salary calculation, employee payments, employee ledger, payroll register, print, cancellation/state rules, FY/Business Date, and HR subscription gating.
- **Not implemented:** attendance, leave, overtime records/rules, allowance masters/rules, deduction masters/rules, automated attendance-to-payroll calculation. `EmployeeAccount` contains defensive references to future `Attendance` and `Leave` models, but those models/tables/controllers/routes/views do not exist.
- **Tests:** no dedicated HR/payroll feature tests were found.
- **Classification:** current basic payroll can be V1 only if marketed explicitly as manual payroll; a full HR module is **FUTURE / V2**.

## 3. Full feature list and classification

### V1 READY

- Authentication/session login/logout foundation; company registration and review.
- Company approval/rejection, management, block/unblock/delete, OTP reset.
- Countries and company country relation; Nepal AD/BS date conversion foundation.
- Platform settings, branding, SMTP storage/test action, payment-gateway credential storage.
- Super Staff and platform user management.
- Manual/admin subscription plans, trials, lifecycle, payments, approval/rejection, invoice/reporting, and module gating.
- Company dashboard/profile, staff and direct staff permissions, financial years and Business Date.
- All listed master data, including product import/export.
- Quotation Draft → Approved → Sales conversion.
- Sales, payments, cancellation, returns/refunds, customer ledger/statement, stock/COGS/accounting.
- Purchase, payments, cancellation, returns/refunds, supplier ledger/statement, stock/accounting.
- Inventory ledger, valuation, opening stock, stock effects and sync.
- Income, expense, journal, opening balance, Accounting Core, GL, Trial Balance, P&L, Balance Sheet, VAT report.

### NEEDS FIX BEFORE V1

- Complete and prove named permission enforcement for every state-changing company route.
- Remove or govern direct financial balance mutation in Loan, Contra, and legacy Invoice Payment paths; reconcile all affected ledgers.
- Retire or safely isolate legacy duplicate payment/plan/subscription/account surfaces that can mutate current data.
- Run the full automated suite and MySQL concurrency probes against the exact release commit without touching production data.
- Verify migration status, seed prerequisites, production configuration, queues/mail/storage, HTTPS/session security, and backup/restore on staging/target hosting.
- If Loan, Delivery, CRM, or HR are enabled for V1, add and pass dedicated workflow/security tests for each enabled module.

### PARTIAL

- Company role/job-role/menu-visibility architecture.
- Delivery (complete workflow, insufficient dedicated tests/external-service verification).
- CRM (broad CRUD/activity implementation, missing tested quotation/sales conversion and report/export endpoints).
- Loan (broad workflow, dual balance mutation blocker).
- HR/payroll (manual payroll foundation only).
- Accounting production readiness (implemented core/reports, unresolved direct mutation and release verification).
- Fine-grained permission and subscription coverage for older/core modules.

### DOCUMENTATION ONLY

- HR menu taxonomy for Attendance and Leave.
- Goods Receive/GRN menu taxonomy.
- CRM report/export capability implied by permissions but not connected to routes/UI.
- Several “future” lifecycle/governance provisions in standards that exceed current UI implementation.

### FUTURE / V2

- Attendance, leave, overtime records, allowance/deduction masters, and automated payroll computation.
- Goods Receive/GRN.
- Live payment gateway APIs, webhooks, recurring billing and automated settlement.
- CRM Opportunity/Lead → Quotation/Sales conversion and CRM export/report suite.
- Multi-currency accounting/opening balance and broader country-specific calendars.
- Formal period close/year-end close UI, reconciliation dashboards, and deployment observability.

## 4. Full database/table inventory

### Framework and identity

`users`, `roles`, `permissions`, `permission_role`, `user_permissions`, `company_permission`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### Platform/company/subscription

`companies`, `company_registrations`, `countries`, `plans` (legacy), `payments` (legacy), `subscriptions` (legacy/compatibility), `billing_cycles`, `subscription_plans`, `subscription_plan_billing_options`, `company_subscriptions`, `subscription_payments`, `subscription_histories`, `platform_settings`, `platform_social_links`, `platform_smtp_settings`, `platform_payment_gateways`.

### Master and operational accounts

`units`, `product_categories`, `brands`, `products`, `vats`, `service_categories`, `services`, `customers`, `suppliers`, `banks` (renamed/legacy migration lineage), `accounts`, `cash_accounts`, `financial_years`.

### Sales/quotation/stock

`quotations`, `quotation_items`, `sales_invoices`, `sales_items`, `sales_payments`, `invoice_payments` (legacy overlap), `sales_returns`, `sales_return_items`, `sales_return_refunds`, `sales_return_refund_adjustments`, `customer_transactions`, `stock_transactions`, `stock_movements`, `inventory_valuations`, `sales_cost_snapshots`.

### Purchase

`purchase_invoices`, `purchase_items`, `purchase_payments`, `purchase_returns`, `purchase_return_items`, `purchase_return_refunds`, `purchase_return_refund_adjustments`, `supplier_transactions`.

### Accounting

`account_transactions`, `income_categories`, `incomes`, `expense_categories`, `expenses`, `journals`, `journal_items`, `journal_number_sequences`, `journal_audit_events`, `contras`, `chart_accounts`, `accounting_entries`, `accounting_entry_lines`, `accounting_period_locks`, `opening_balances`, `opening_balance_lines`, `opening_balance_audit_events`, `opening_balance_legacy_records`.

### Loan

`party_accounts`, `loan_accounts`, `loan_payments`, `loan_saving_ledgers`.

### HR/payroll

`employee_accounts`, `salary_sheets`, `employee_payments`. No attendance or leave tables were found.

### Delivery

`delivery_notes`, `delivery_note_items`, `delivery_status_histories`, `delivery_signatures`, `delivery_attachments`.

### CRM

`crm_configurations`, `crm_leads`, `crm_opportunities`, `crm_follow_ups`, `crm_meetings`, `crm_tasks`, `crm_notes`, `crm_attachments`, `crm_status_histories`, `crm_contacts`.

### Relationship/isolation assessment

Business tables predominantly carry `company_id`; financial documents additionally carry `financial_year_id`; line tables link to company-owned headers and masters. Official accounting is `chart_accounts` → `accounting_entries` → `accounting_entry_lines`, with source keys and reversals. Operational subledgers remain in customer/supplier/account/loan tables. Migration-only review cannot prove deployed FK/index state or tenant cleanliness.

## 5. Full permission inventory

The seeder defines module permissions for users, income, expense, journal, opening balance, loan, HR, payroll, delivery, CRM, company profile, maintenance, sales, sales payment, customer, purchase, supplier, stock, accounts, account transactions, contra, VAT, reports, and quotation. Action permissions cover CRUD/print/cancel workflows; Journal and Opening Balance have detailed submit/approve/post/reject/reverse/lock/audit/export permissions; HR, delivery and CRM have module-specific actions; platform scope includes dashboard, companies, registrations, subscriptions, subscription payments/reports, settings, plans, Super Staff, and users.

Key findings:

- Newer modules use named permissions and company-admin override services consistently.
- Many older company routes have only authentication, company, subscription, and last-seen middleware; some controllers compensate, but enforcement is not uniform.
- `RoleMiddleware` still validates broad access by fixed role IDs. This is acceptable only as a portal-boundary classifier; it must not become business authorization under the frozen permission standard.
- `job_role` controls sidebar visibility, while permissions control authorization. Their scopes overlap conceptually and need a single technical guide.
- Seeder contains both singular/plural user permission names (`delete_user`, `delete_users`) and legacy/new dotted naming conventions.

## 6. Full sidebar/menu inventory

Company sidebar exposes: Dashboard; Staff Management/Staff List; Sales (Quotations, Sales, Payments, Returns, Refunds, Customers); CRM (Quotations, Dashboard, Customer Relationships, Contacts, Opportunities, Follow-ups, Meetings, Tasks); Purchase (Purchases, Purchase Payments, Returns, Refunds, Suppliers); Inventory (Products, Categories, Brands, Units, Services, Service Categories, Stock Ledger); Accounts (Accounts, Cash Accounts, Account Transactions, Income, Expenses, Journal, Contra, VAT); Loan (Party Account, Loan Ledger, Loan Payment, Loan Saving Ledger); HR & Payroll (Employee Management, Salary Sheets, Salary Payments); Reports (VAT, Salary, Payroll Register, Supplier Statement, Customer Statement); Accounting Reports (GL, Trial Balance, P&L, Balance Sheet); Settings (Profile, Financial Years, Maintenance); Delivery Notes; Logout.

Visibility is filtered by job-role menu visibility; CRM/Loan/HR/Delivery also require subscription access. Quotation deliberately appears in Sales and CRM. Attendance, Leave, and Goods Receive are named in the menu-visibility standard but are not rendered in the current sidebar and have no implementation.

## 7. Full report inventory

- General Ledger — official Accounting Core lines, account/FY/date scope and running balance.
- Trial Balance — opening/period/closing debit-credit integrity.
- Profit & Loss — official income/expense accounts and net result.
- Balance Sheet — asset/liability/equity with current result and integrity check.
- VAT Report — transaction VAT reporting and print.
- Customer Statement.
- Supplier Statement.
- Supplier Ledger.
- Stock Ledger and PDF.
- Account Transactions/account detail and print.
- Employee Ledger.
- Loan Saving Ledger.
- Payroll Register and print.
- Salary report/print list.
- Subscription report.
- Master and transaction list/profile prints for products, units, categories, brands, services, customers, suppliers, accounts, sales, purchase, returns/refunds, payments, income, expense, journal, contra, loan, employee, delivery and quotation.

No verified CRM report/export endpoint, cash-flow statement, aged receivables/payables report, inventory valuation report UI, or consolidated multi-company statement was found.

## 8. Documentation gap list

### A. Software exists and documentation exists

Sales, quotation, CRM, delivery, loan, income, expense, journal, opening balance, Accounting Core/reports, subscription architecture, permissions/job-role visibility, Financial Year/Business Date/AD-BS, and global development/UI/business rules.

### B. Software exists and documentation is missing or insufficient

Platform operations, company registration/approval, countries UI, company profile/staff administration, master data, purchase end-user workflow, inventory/valuation, product import/export, customer/supplier statements, VAT, payroll implementation, maintenance/reset, official deployment/runbook, and most user guides. Only the Account Management user guide was found.

### C. Documentation exists and software is missing

Attendance, Leave, and Goods Receive menu taxonomy; CRM report/export implication; future multi-currency and extended country behaviors.

### D. Documentation and software conflict

- Older financial audit says official reports and Purchase Return accounting are absent; current source implements them.
- Job Role/Menu standard lists future menu items not implemented.
- Income, Expense, and older Journal standards are marked pending approval while newer accounting/journal constitutions are final.
- Role-ID middleware terminology conflicts with the frozen rule that business authorization is permission-based.

### E. Old/obsolete documentation

`15_DG_ERP_ACCOUNTING_REPORT_STANDARD.md` and `00_DG_ERP_CONSTITUTION.md` duplicate older accounting-report v1 content and are superseded by v2 for that subject. `07_DG_ERP_JOURNAL_MODULE_STANDARD.md` is superseded by `17_DG_ERP_JOURNAL_STANDARD.md`. The two Delivery standard files overlap. The 2026-07-27 Admin and 2026-08-09 Financial audits contain resolved/stale findings and must be labeled historical.

### F. Duplicate/conflicting standards

Accounting Report v2, Accounting Core, and Opening Balance each claim final conflict authority. This hierarchy must be formally resolved by the Business Owner without changing business rules.

## 9. Implemented but undocumented features

Country management UI; current official accounting reports; Purchase Return clearing-account implementation; product import/export; OTP company-admin reset remediation; complete CRM activity/attachment implementation details; delivery proof-of-delivery email/PDF storage; payroll register; inventory valuation/sales cost snapshots; maintenance hard-reset operations; legacy compatibility paths.

## 10. Documented but not implemented features

Attendance; Leave; Goods Receive/GRN; CRM report/export routes; automatic attendance/overtime/allowance/deduction-to-payroll computation; multi-currency opening balance; broader country calendars; some future accounting lifecycle/governance UI.

## 11. Partially implemented features

Full HR; CRM conversion/reporting; Delivery production verification; Loan balance governance; uniform permission coverage; Job Role CRUD; live payment gateway; deployment automation; reconciliation reporting; fine-grained automated tests outside core finance.

## 12. Dead/orphaned routes, controllers, views or tables

- `StaffDashboardController` and `Admin\PaymentApprovalController` have no verified route reference.
- `CompanyDashboardController` is imported/aliased, while the active company dashboard route points to `Company\DashboardController`; this is duplicate/legacy code.
- Legacy Admin plan/payment/permission views coexist with newer subscription-plan/payment flows and closure routes.
- `role_create.blade.php` has no verified active workflow.
- `invoice_payments`/`InvoicePaymentController` overlap `sales_payments` and include direct account mutation.
- `plans`, `payments`, and `subscriptions` have legacy lineage alongside new subscription tables/models.
- `banks`, `accounts`, and `cash_accounts` reflect historical overlap.
- Orphan status is source-inferred; dynamic references and deployed data require verification before removal. No deletion is authorized by this report.

## 13. Possible duplicate/legacy implementations

Admin `PlanController` vs `SubscriptionPlanController`; `PaymentApprovalController` vs `SubscriptionPaymentController`; operational `AccountTransaction` vs official Accounting Core (intentional dual layer but requires reconciliation); `InvoicePayment` vs `SalesPayment`; `StockTransaction` vs `StockMovement`; `Plan`/`Subscription`/`Payment` vs upgraded subscription models; `Role`/`job_role`/direct permissions/menu visibility; old/new accounting report and journal documents.

## 14. Module integration map

Only repository-supported flows are marked VERIFIED:

- **VERIFIED:** Quotation → Sales Invoice → normal Sales stock/customer/accounting effects.
- **VERIFIED:** Purchase → Stock/Inventory Valuation → Supplier subledger → Accounting Core.
- **VERIFIED:** Purchase Return → Stock/value reversal → Supplier Return Receivable → later refund/adjustment settlement.
- **VERIFIED:** Sales → Stock OUT → Customer subledger → Revenue/VAT → COGS/Inventory in Accounting Core.
- **VERIFIED:** Sales Return → product stock/COGS restoration; Sales Return Refund → customer/cash-adjustment settlement.
- **VERIFIED:** Income/Expense/Journal/Opening Balance/Loan → Accounting Core.
- **VERIFIED:** Accounting Core → GL/Trial Balance/P&L/Balance Sheet.
- **VERIFIED:** Sales Invoice → Delivery Note quantities/status/proof; Delivery creates no second stock effect.
- **VERIFIED:** Salary Sheet → Employee Payment → Employee Ledger/Payroll Register.
- **PARTIAL:** CRM → Customer context/activities; no proven Opportunity/Lead conversion to Quotation/Sales.
- **PARTIAL:** HR → Payroll because attendance/leave/overtime source records are absent.

## 15. Final classification

### Fully implemented

Master-data CRUD; quotation; sales; purchase; returns/refunds; stock ledger/valuation/COGS; customer/supplier subledgers; income; expense; journal; opening balance; central posting/reversal; official GL/TB/P&L/Balance Sheet; VAT report; manual subscription lifecycle; core platform/company administration.

### Partially implemented

Permission architecture rollout; Loan; Delivery verification; CRM integrations/reporting; HR/payroll; production deployment readiness; legacy reconciliation/retirement.

### Documentation only

Attendance, Leave, Goods Receive menu definitions; unimplemented future governance/features described above.

### UI only

No active sidebar item was proven entirely UI-only. `role_create.blade.php` and some legacy Admin views are orphan candidates, not verified active UI.

### Backend only

Inventory valuation/cost snapshots, numerous accounting builders/profiles/integrations, audit/history infrastructure, and some legacy controllers have no direct sidebar destination by design or are orphan candidates.

### Not implemented

Attendance, Leave, Goods Receive/GRN, live payment gateway/webhooks, CRM reports/exports/conversion, full automated payroll source calculations, cash-flow statement, aged receivable/payable reports.

### Needs manual verification

Deployed migration status/data constraints; full test suite; MySQL concurrency; production environment/session/HTTPS; SMTP/email; filesystem/PDF permissions; queue/scheduler; gateway credentials; backup/restore; responsive browser workflows; large-data performance; all enabled-module permissions; real-data accounting/subledger/stock reconciliation.

## 16. Documents still required

- V1 Deployment, Backup/Restore, Rollback and Disaster Recovery Runbook.
- Production Security and Environment Configuration Standard.
- Company Registration and Platform Administration User/Technical Guides.
- Subscription Administration and Manual Payment User/Technical Guides.
- Company Profile, Staff, Permission, Job Role and Menu Visibility consolidated guide.
- Master Data and Product Import/Export User Guide.
- Purchase, Inventory, Delivery, CRM, Loan, HR/Payroll, VAT and Reports user guides.
- Accounting Core, reconciliation, period-close and official reports operator guide.
- Database/data dictionary and integration/source-key reference.
- File storage, email, queue and scheduled-job operations guide.
- Test strategy, release acceptance checklist, permission matrix, subscription module matrix, and legacy-retirement register.
- Documentation authority/supersession index resolving duplicate standards.

## 17. Recommended development priority

- **P0:** close permission gaps; eliminate direct/duplicate balance mutation; isolate legacy payment mutation; run full release suite/MySQL probes; verify staging migrations/config/security/backup; reconcile accounting, operational accounts, parties, and stock.
- **P1:** add enabled-module tests for subscription, Delivery, CRM, Loan and payroll; formally resolve documentation authority; complete Job Role/permission mapping; create deployment and accounting operator guides.
- **P2:** CRM conversion/report/export, HR attendance/leave/payroll automation, Goods Receive/GRN, reconciliation/aging/cash-flow reports, legacy code/table retirement after data analysis.
- **P3:** live billing gateway/webhooks, multi-currency, additional country calendars, consolidated analytics and optional UX refinements.

## V1 freeze decision

### V1 FREEZE STATUS:

**NOT READY**

### V1 BLOCKERS:

1. Named permission enforcement is not consistently proven on every state-changing company route; older modules use mixed middleware/controller checks.
2. Loan, Contra, and legacy Invoice Payment paths contain direct operational/party balance increments/decrements, creating a dual-mutation and reconciliation risk.
3. The exact release state has no fresh full-suite result after the latest country, quotation, Purchase Return, and official-report implementation; production-engine concurrency probes are not verified.
4. Deployed migration status, seed prerequisites, production configuration, HTTPS/session security, queue/mail/storage, backup/restore, and real-data reconciliation are unverified.
5. Any optional V1 module enabled in production (Loan, Delivery, CRM, HR) lacks sufficient dedicated end-to-end/security coverage; Loan additionally cannot be enabled until blocker 2 is fixed.

### V1 NON-BLOCKING ITEMS:

1. Missing user/technical guides other than Account Management, provided release/operator documentation is completed before customer handover.
2. Stale historical audit documents and duplicate standard filenames, provided the authority index clearly marks superseded material.
3. Orphan/legacy controllers, views, and migrations that are proven unreachable and non-mutating; cleanup can follow V1.
4. UI consistency issues in legacy Admin views.
5. Missing optional reports such as cash flow, aging, and consolidated analytics.
6. CRM-to-quotation/sales automation, full HR, Goods Receive, and live gateway automation when excluded from the advertised V1 scope.

### V2 BACKLOG:

- Attendance, leave, overtime records, allowance/deduction masters, and automated payroll.
- Goods Receive/GRN and enhanced warehouse controls.
- CRM Lead/Opportunity conversion to Quotation/Sales plus CRM reports/exports.
- Live payment gateway APIs, webhooks, recurring billing and automated settlement.
- Cash-flow, aging, reconciliation, inventory valuation UI, and consolidated reporting.
- Multi-currency and additional country/calendar/localization support.
- Formal period/year close automation, monitoring/observability, performance hardening, and safe legacy retirement.

## Audit verification record

- Read-only commands used: file inventory/search, source display, `php artisan route:list --json`, and Git status.
- No PHPUnit execution was performed because tests create/drop schema and the task prohibits database changes.
- No `migrate:status` was used because even a connection to the configured database was outside the repository-only evidence boundary.
- No migrations, seeders, browser actions, external mail/payment calls, commits, pushes, or application changes were performed.
- The only file created by this task is this report.
