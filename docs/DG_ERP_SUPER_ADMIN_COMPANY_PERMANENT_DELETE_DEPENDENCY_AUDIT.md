# DG ERP — COMPANY PERMANENT DELETE DEPENDENCY AUDIT

## 1. Executive Summary

**Assessment: NO-GO for implementation until blockers are resolved.**

The schema contains **105 application tables**:

- **80 tables contain `company_id`**
- **79 have non-null `company_id`**
- `users.company_id` is nullable because platform users share the table
- Only **22 direct tables** cascade when `companies.id` is deleted
- **16 direct tables** restrict/no-action
- **42 direct tables have no FK from `company_id` to `companies.id`**

Therefore deleting the `companies` row cannot provide complete tenant deletion. A centralized, explicitly ordered deletion service is required.

A critical pre-existing unsafe operation already exists:

- `POST admin/company/delete/{id}`
- Protected by `platform_companies_delete`
- Verifies the current platform user's password
- Immediately deletes Company users and then Company
- Has no authenticated Super Admin email OTP and no dependency cleanup

Evidence: `app/Http/Controllers/Admin/CompanyController.php:132`, `routes/web.php:189`.

This existing endpoint must not be used for permanent tenant deletion in its present form.

## 2. Direct Company-owned Tables

All tables below are Company-owned. Except `users.company_id`, their `company_id` columns are non-null.

### Company FK with `ON DELETE CASCADE` — 22

These rows can be removed by Company deletion, but explicit orchestration is still preferable because of secondary FKs and audit verification:

- `accounting_entries` — `AccountingEntry`
- `brands` — `Brand`
- `chart_accounts` — `ChartAccount`
- `company_permission` — pivot, no dedicated model
- `company_subscriptions` — `CompanySubscription`
- `company_whatsapp_settings` — `CompanyWhatsappSetting`
- `crm_attachments` — `CrmAttachment`
- `crm_configurations` — `CrmConfiguration`
- `crm_contacts` — `CrmContact`
- `crm_follow_ups` — `CrmFollowUp`
- `crm_leads` — `CrmLead`
- `crm_meetings` — `CrmMeeting`
- `crm_notes` — `CrmNote`
- `crm_opportunities` — `CrmOpportunity`
- `crm_status_histories` — `CrmStatusHistory`
- `crm_tasks` — `CrmTask`
- `customers` — `Customer`
- `financial_years` — `FinancialYear`
- `products` — `Product`
- `stock_transactions` — `StockTransaction`
- `subscription_payments` — `SubscriptionPayment`
- `units` — `Unit`

### Company FK with RESTRICT/NO ACTION — 16

These must be deleted explicitly before `companies`:

- `accounting_period_locks`
- `inventory_valuations`
- `journal_audit_events`
- `journal_number_sequences`
- `journals`
- `loan_accounts`
- `loan_payments`
- `loan_saving_ledgers`
- `opening_balance_audit_events`
- `opening_balances`
- `purchase_return_refund_adjustments`
- `quotation_items`
- `quotations`
- `sales_cost_snapshots`
- `sales_return_refund_adjustments`
- `subscription_histories`

### No Company FK — explicit deletion mandatory — 42

These contain non-null `company_id` but the database does not connect that column to `companies.id`:

- `account_transactions`
- `accounts`
- `cash_accounts`
- `contras`
- `customer_transactions`
- `delivery_attachments`
- `delivery_note_items`
- `delivery_notes`
- `delivery_signatures`
- `delivery_status_histories`
- `employee_accounts`
- `employee_payments`
- `expense_categories`
- `expenses`
- `income_categories`
- `incomes`
- `invoice_payments`
- `journal_items`
- `loan_integrity_seeded_chart_accounts`
- `opening_balance_legacy_records`
- `party_accounts`
- `product_categories`
- `purchase_invoices`
- `purchase_items`
- `purchase_payments`
- `purchase_return_items`
- `purchase_return_refunds`
- `purchase_returns`
- `salary_sheets`
- `sales_invoices`
- `sales_items`
- `sales_payments`
- `sales_return_items`
- `sales_return_refunds`
- `sales_returns`
- `service_categories`
- `services`
- `stock_movements`
- `supplier_transactions`
- `suppliers`
- `users`
- `vats`

Most have matching Eloquent models. No dedicated model was found for at least `cash_accounts`, `company_permission`, `loan_integrity_seeded_chart_accounts`, and `opening_balance_legacy_records`.

All 42 are high orphan risks if deletion relies on FK cascades.

## 3. Indirect Company-owned Tables

These lack `company_id` but contain tenant-owned data:

| Table | Ownership chain | Current deletion behavior |
|---|---|---|
| `accounting_entry_lines` | entry line → `accounting_entries.company_id` | Cascades from accounting entry |
| `opening_balance_lines` | line → `opening_balances.company_id` | Cascades from opening balance |
| `user_permissions` | permission assignment → `users.company_id` | Cascades when user is deleted |
| `sessions` | session → `users.company_id` | No FK; explicit deletion required |
| `password_reset_requests` | reset → `user_id`/`initiated_by` | User FK becomes null; PII snapshots remain |
| `password_reset_tokens` | token → user email | No user/company FK |
| `company_registrations` | registration → approved company | No authoritative FK; email is the only apparent link |

Special handling:

- `password_reset_requests` stores `user_email` and `initiated_by_email`. Relying on `nullOnDelete()` leaves tenant PII behind.
- `sessions` must be deleted by Company user IDs before those users are removed.
- `password_reset_tokens` must be matched against authoritative Company-user emails.
- Queue tables (`jobs`, `job_batches`, `failed_jobs`) have no structured Company ownership. Tenant jobs embedded in serialized payloads are **UNRESOLVED**.

## 4. Shared/Global Tables to Preserve

The following must not be deleted as Company-owned data:

- `countries`
- `roles`
- `permissions`
- `permission_role`
- `subscription_plans`
- `subscription_plan_billing_options`
- `billing_cycles`
- `platform_settings`
- `platform_smtp_settings`
- `platform_payment_gateways`
- `platform_social_links`
- `platform_login_settings`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

Preserve:

- Super Admin users
- Super Staff users
- All users whose `company_id` differs from the selected Company
- Platform settings/media
- Platform SMTP credentials
- Global payment and subscription-plan configuration

Some global tables reference `users` through `SET NULL`; deleting a Company user will not delete those global rows.

## 5. User/Auth/Permission Dependencies

Delete for selected Company:

1. `sessions` for all tenant user IDs
2. `password_reset_tokens` for tenant email addresses
3. `password_reset_requests` where:

   - `user_id` is a tenant user
   - `initiated_by` is a tenant user where appropriate
   - retained email snapshots belong to deleted tenant users

4. `user_permissions` — existing user cascade supports this
5. `users WHERE company_id = selected_company_id`

Preserve:

- Role IDs 1–4 and all `roles`
- Global `permissions`
- Global `permission_role`
- Platform users with `company_id IS NULL`
- Users of all other Companies

Company Admin and Company Staff resolution remains required for tenant-user deletion when:

```text
users.company_id = selected Company ID
AND users.role_id = Role::COMPANY_ADMIN_ID
AND users.email is valid
```

Company Admin existence or multiplicity does not determine the OTP recipient and does not block OTP initiation. The OTP recipient is the authenticated canonical Super Admin email. All users belonging to the selected Company, including Company Admin users, remain in the tenant deletion inventory.

## 6. Financial/Accounting Dependencies

Required dependency direction:

```text
opening_balance_audit_events
→ opening_balance_lines
→ opening_balances

account/customer/supplier transaction self-reversal chains
→ journal_audit_events
→ journal_items
→ journals
→ accounting_entry_lines
→ accounting_entries

accounting_period_locks
journal_number_sequences
opening_balance_legacy_records
loan_integrity_seeded_chart_accounts

loan_saving_ledgers
→ loan_payments
→ loan_accounts
→ party_accounts

employee_payments
→ salary_sheets
→ employee_accounts

expenses/incomes
→ expense_categories/income_categories
→ chart_accounts/accounts/cash_accounts
→ financial_years
```

High-risk constraints:

- Journal, opening-balance and loan tables contain RESTRICT references to Company users.
- Self-references exist in accounting entries and transaction reversal records.
- Deleting users too early will fail or destroy actor traceability before tenant deletion completes.
- `accounting_entry_lines` and `opening_balance_lines` are indirect children.
- Category tables reference chart accounts without cascade.
- Financial-year references use a mixture of CASCADE, SET NULL and RESTRICT.

Users, accounts, chart accounts and financial years must be deleted late.

## 7. Sales/Purchase/Inventory Dependencies

Recommended child-first order:

1. `sales_return_refund_adjustments`
2. `purchase_return_refund_adjustments`
3. `sales_cost_snapshots`
4. `inventory_valuations`
5. Delivery attachments/signatures/status/items/notes
6. Sales/Purchase return refunds
7. Sales/Purchase return items
8. Sales/Purchase returns
9. Sales/Purchase payments and `invoice_payments`
10. `stock_transactions`
11. `stock_movements`
12. `sales_items`, `purchase_items`
13. `sales_invoices`, `purchase_invoices`
14. `quotations` children as applicable
15. Products/services
16. Categories/brands/units/VAT
17. Customers/suppliers and their ledgers

Important restrictions:

- `sales_cost_snapshots` references invoices, items, products, valuations and stock movements with RESTRICT.
- Refund-adjustment tables restrict invoice/refund deletion.
- Several invoice/item/payment tables contain `company_id` but lack Company FK.
- Product deletion cascades stock transactions, but valuations and snapshots can still restrict it.
- Customer/supplier transactions reference journal items and self-reversal rows.

## 8. CRM/Delivery/Quotation Dependencies

CRM direct tenant tables:

- `crm_configurations`
- `crm_leads`
- `crm_contacts`
- `crm_opportunities`
- `crm_follow_ups`
- `crm_meetings`
- `crm_tasks`
- `crm_notes`
- `crm_attachments`
- `crm_status_histories`

Although Company cascade exists, cross-references to customers, employees and other CRM records require CRM deletion before those masters.

Delivery:

- `delivery_attachments`
- `delivery_signatures`
- `delivery_status_histories`
- `delivery_note_items`
- `delivery_notes`

All contain `company_id`, but none has a Company FK. Delete explicitly child-first.

Quotation:

- `quotation_items` cascades from `quotations`
- Both contain `company_id`
- Both directly restrict Company deletion
- Quotations also reference customer, financial year and converted sales invoice

Delete quotation items and quotations before customers, invoices and financial years.

## 9. Subscription Dependencies

Company-owned:

- `subscription_histories`
- `subscription_payments`
- `company_subscriptions`

Order:

```text
subscription_histories
→ subscription_payments
→ company_subscriptions
```

Preserve:

- `subscription_plans`
- `subscription_plan_billing_options`
- `billing_cycles`

`company_subscriptions` and payments cascade from Company, but `subscription_histories.company_id` is RESTRICT and also references subscriptions. It must be explicitly deleted first.

Payment proof files are stored in shared directories such as `storage/app/public/subscription-payments`; the entire directory must not be deleted. Individual DB-referenced files require verified cleanup.

## 10. Company Registration Linkage

**BLOCKER / UNRESOLVED**

`company_registrations` has:

- no `company_id`
- no approved Company relationship
- no stable approval-result identifier

The only apparent correlations are email and human-readable fields.

Email matching is not a sufficiently authoritative destructive relationship because:

- emails can theoretically change
- historical records may collide conceptually
- it does not prove approval ownership
- registration history may have retention requirements

Recommendation: do not automatically delete registration history until a business decision and authoritative linkage design are approved.

## 11. Filesystem Dependency Map

### Safe Company-rooted public directory

Actual code writes extensively under:

```text
public/companies/{company_id}/
```

Examples:

- Company logo/signature
- Customer/supplier/account images
- Product/brand/service/category files
- Income/expense documents
- Employee/payment documents
- Loan and party-account files
- Sales/Purchase payment evidence
- Delivery images, signatures and PDFs

The complete resolved directory `public/companies/{validated integer company_id}` is a strong Company-owned deletion candidate.

Evidence includes:

- `app/Services/FileUploadService.php`
- `app/Services/DeliveryNoteService.php:227`
- `app/Http/Controllers/Company/CompanyClientController.php`
- `app/Http/Controllers/Admin/CompanyApprovalController.php:153`

### Storage public Company directory

Some uploads use the public disk:

```text
storage/app/public/companies/{company_id}/
```

Examples include return damage photos. This is separate from `public/companies/{id}` and must also be collected.

### CRM private storage

CRM attachments use:

```text
storage/app/crm/{company_id}/{entity_type}/{entity_id}/
```

Evidence: `app/Services/CrmAttachmentService.php:120`.

### Shared storage directories — individual file cleanup only

- `storage/app/public/subscription-payments/`
- `storage/app/public/payments/` where applicable
- Any shared platform-media directory

These cannot be deleted wholesale. File paths must be collected from tenant DB records before transaction commit.

### Existing reset service problem

`app/Services/CompanyHardResetService.php` is an empty placeholder. It only attempts to delete:

```text
storage/app/public/companies/{company_id}
```

inside a DB transaction.

Problems:

- no database deletion logic
- misses `public/companies/{id}`
- misses `storage/app/crm/{id}`
- filesystem mutation occurs inside the DB transaction
- cannot roll filesystem changes back
- not suitable for permanent deletion

## 12. Foreign Key/Cascade Map

Practical summary:

| Classification | Count | Consequence |
|---|---:|---|
| Company FK CASCADE | 22 | Automatically removable, subject to secondary constraints |
| Company FK RESTRICT/NO ACTION | 16 | Must be deleted before Company |
| No Company FK | 42 | Must be explicitly deleted |
| Nullable tenant user link | 1 (`users`) | Must filter strictly by Company |

Important indirect cascades:

- `accounting_entry_lines → accounting_entries`: CASCADE
- `opening_balance_lines → opening_balances`: CASCADE
- `quotation_items → quotations`: CASCADE
- `user_permissions → users`: CASCADE

Important non-cascading chains:

- Journal items/audits → journals
- Customer/supplier/account transactions → journal items
- Inventory valuations and cost snapshots → stock/product/sales records
- Subscription histories → subscriptions/payments
- Delivery children → delivery notes
- Employee payments → salary sheets

The current FK graph is not sufficient for direct Company deletion.

## 13. Proposed Safe Deletion Order

Within one DB transaction:

1. Lock selected Company and active delete challenge.
2. Resolve and lock tenant user IDs.
3. Delete auth residue:

   - sessions
   - password-reset tokens/requests
   - user permissions

4. Delete shared-directory file references from DB only after collecting their exact paths.
5. Delete leaf data:

   - refund adjustments
   - cost snapshots
   - audit/history rows
   - delivery attachments/signatures/status/items
   - accounting/opening-balance lines
   - return items/refunds
   - payments
   - CRM attachments/notes/tasks/follow-ups/meetings/contacts/opportunities

6. Delete parent transactions:

   - returns
   - quotations
   - delivery notes
   - invoices
   - stock movements/valuations
   - journals/opening balances/accounting entries
   - loans/payroll/income/expense
   - customer/supplier/account transactions

7. Delete tenant masters:

   - customers/suppliers
   - products/services/categories/brands/units/VAT
   - employees/party accounts
   - chart accounts/accounts
   - financial years

8. Delete subscriptions:

   - histories
   - payments
   - subscriptions

9. Delete Company settings/pivots.
10. Delete Company users after every RESTRICT actor reference is gone.
11. Verify zero rows in all 80 direct tables and indirect tables.
12. Delete Company.
13. Commit.
14. Delete prevalidated filesystem roots and collected individual shared-directory files.
15. Record safe cleanup failures without restoring DB rows.

## 14. Orphan Risks

### BLOCKER

- Existing immediate-delete route deletes only users and Company.
- 42 tenant tables have no Company FK.
- CompanyRegistration has no authoritative Company link.
- No complete dependency service exists.
- Queue payload ownership cannot be reliably inferred.
- Current `CompanyHardResetService` is incomplete and has unsafe filesystem ordering.

### HIGH

- Password reset records retain email snapshots after user nulling.
- Sessions have no user FK.
- Self-referencing transaction reversal records.
- RESTRICT user actor references in journals, loans and opening balances.
- Cost snapshots reference six separate transaction/master tables.
- CRM attachments live outside the primary Company public directory.
- Shared upload directories cannot be recursively removed.

### MEDIUM

- Platform/global records can contain `created_by`/`updated_by` links to tenant users. Most use `SET NULL`, but this must be verified during tests.
- Missing or multiple Company Admin records may complicate tenant-account validation, but do not control OTP delivery.
- File records may contain legacy paths not rooted under Company ID.

### LOW

- Tables with simple `company_id → companies.id ON DELETE CASCADE` and no problematic secondary references.

## 15. OTP/Email Architecture Reuse

Reusable patterns:

- `UserPasswordResetService`:

  - `random_int()` six-digit OTP
  - `Hash::make()` / `Hash::check()`
  - 10-minute expiry
  - five-attempt limit
  - `lockForUpdate()`
  - previous challenge invalidation
  - single-use state

- `PlatformMailService`:

  - stored `PlatformSmtpSetting`
  - explicit `platform_smtp` mailer
  - safe missing/disabled/incomplete SMTP failure
  - no `.env` fallback

- Existing approval-email flow sends through `PlatformMailService`.

Do not reuse `password_reset_requests` directly: its semantics and columns are password-specific. A small additive Company-delete challenge table/service is cleaner.

The existing Company password-reset code has a separate OTP service pattern, but some controller mail paths use `Mail::to(...)`; future Company deletion must explicitly use `PlatformMailService`.

## 16. Authorization Architecture

Existing canonical permission:

```text
platform_companies_delete
```

Existing enforcement:

- Route middleware: `platform.permission:platform_companies_delete`
- `EnsurePlatformPermission`
- `PlatformAuthorizationService`
- Controller concern: `authorizeDeleteCompany()`

Final business decision:

- Permanent Company Delete is canonical Super Admin only.
- The authenticated user must have `role_id = Role::SUPER_ADMIN_ID`, an active account and a valid email.
- Existing `platform_companies_delete` permission/authorization must also be enforced where required by the current architecture.
- Super Staff is denied even when assigned `platform_companies_delete`.
- Company Admin and Company Staff are denied.

The previous Super Staff authority blocker is **RESOLVED**.

## 17. Test Strategy

Use isolated test databases only:

- `RefreshDatabase`
- SQLite fresh schema for fast dependency tests
- MySQL-focused tests for actual FK behavior and transaction locking
- Existing multi-Company fixtures from permission/security tests

Minimum isolation fixture:

```text
Company A
  Company Admin A
  Staff A
  records in every direct and indirect tenant table
  files in every Company-owned root

Company B
  Company Admin B
  Staff B
  matching equivalent records and files

Global
  Super Admin
  Super Staff
  countries/roles/permissions/plans/platform settings
```

Assertions:

- Invalid/expired/replayed OTP: every Company A count unchanged.
- Valid OTP: every Company A tenant count becomes zero.
- Every Company B row and file remains unchanged.
- Global masters remain unchanged.
- Injected DB failure rolls back every Company A DB deletion.
- Files remain until DB commit.
- Cleanup failure is reported without corrupting Company B.
- Never test against production or use production Company IDs.

## 18. Blockers / Required Business Decisions

1. **Existing unsafe delete endpoint:** disable/replace atomically during future implementation.
2. **CompanyRegistration retention:** no authoritative Company link exists.
3. **Platform deletion audit:** no confirmed durable platform audit architecture exists.
4. **Queue payloads:** ownership is unresolved.
5. **Shared proof files:** determine retention rules for subscription/payment evidence.
6. **Filesystem cleanup retry:** define operational retry/reporting mechanism.
7. **HardReset service:** must not be reused as currently written.
8. **Direct tables without Company FK:** require a maintained explicit inventory and zero-row verification.

## 19. Recommended Implementation Architecture

Future implementation should use:

- `CompanyDeleteChallenge` model/table
- `CompanyDeletionOtpService`
- `CompanyDeletionService`
- Dedicated Mailable through `PlatformMailService`
- Thin controller
- Canonical Super Admin role enforcement plus existing `platform_companies_delete` middleware
- One authoritative dependency inventory in the service
- DB transaction with `lockForUpdate`
- Post-commit filesystem cleanup
- Zero-row/orphan assertions before Company deletion
- Safe platform audit containing IDs/names/result only
- Idempotent challenge invalidation and replay protection

The existing immediate `CompanyController::delete()` and placeholder `CompanyHardResetService` should not become the foundation without controlled replacement.

## 20. Final GO / NO-GO Assessment

**AUDIT RESULT: COMPLETE**

**IMPLEMENTATION GO/NO-GO: NO-GO**

Implementation should begin only after:

- CompanyRegistration retention/linkage decision
- Platform deletion-audit decision
- Queue/shared-upload handling decision
- Approval of the explicit 80-table dependency inventory and deletion order
- Safe replacement plan for the existing immediate-delete endpoint

No files, migrations, database rows, routes, controllers or services were modified during this audit. No audit file was created during the audit-only pass, as requested.
