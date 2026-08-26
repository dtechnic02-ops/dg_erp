# DG ERP — SUPER ADMIN COMPANY PERMANENT DELETE WITH EMAIL OTP

Project:
DG ERP — Multi Company SaaS ERP

TASK TYPE:
HIGH-RISK DESTRUCTIVE ADMIN OPERATION

Implement Company Permanent Delete inside:

Super Admin
→ Companies Management

This task must be audit-first.

Do NOT blindly add cascade deletes.

==================================================
BUSINESS REQUIREMENT
==================================================

Super Admin must be able to permanently delete a Company.

But deletion MUST NOT happen immediately.

Required flow:

Super Admin
→ Companies Management
→ Select Company
→ Delete
→ Resolve the authenticated canonical Super Admin email
→ Send OTP to the authenticated Super Admin email
→ Super Admin enters OTP
→ OTP verified
→ Company and ALL Company-owned data permanently deleted

Without valid OTP:

NO DELETE.

==================================================
AUTHORITATIVE OTP RECIPIENT
==================================================

OTP must go to the authoritative email of the authenticated canonical Super Admin.

Resolve server-side.

Preferred authoritative relationship:

Authenticated user
→ role_id = Role::SUPER_ADMIN_ID
→ account_status = active
→ validated email

Do NOT trust:

- request supplied email
- selected Company email
- Company Admin email
- browser supplied recipient email
- arbitrary recipient

If the authenticated canonical Super Admin has no valid email:

BLOCK deletion safely.

Do NOT fallback to an arbitrary email.

==================================================
IMPORTANT SUPER ADMIN RULE
==================================================

Only the authenticated canonical Super Admin may initiate Company Permanent Delete.

Future implementation must require both:

1. `role_id = Role::SUPER_ADMIN_ID`
2. existing `platform_companies_delete` permission/authorization where required by the current architecture

Company Admin:
CANNOT delete Company through this flow.

Company Staff:
CANNOT delete Company.

Super Staff:
CANNOT permanently delete a Company, even when assigned `platform_companies_delete`.

Backend authorization is mandatory.

==================================================
DELETE BUTTON
==================================================

Add a clear Delete action in:

Super Admin
→ Companies Management

Use existing DG UI framework.

Do not redesign Companies Management.

Delete must visibly communicate:

PERMANENT DELETE

This operation removes the Company and all Company-owned data.

==================================================
STEP 1 — REQUEST DELETE OTP
==================================================

When Super Admin chooses Delete:

Server must:

1. Resolve selected Company.
2. Confirm Company exists.
3. Confirm the authenticated user is the canonical active Super Admin.
4. Resolve and validate the authenticated Super Admin email.
5. Generate secure numeric OTP.
6. Store only a secure hash of OTP.
7. Associate OTP with:
   - company_id
   - requested_by Super Admin
   - purpose = company_delete
   - expiry
   - used/verified state
8. Send OTP to the authenticated Super Admin email.

Do NOT delete anything at this stage.

==================================================
OTP SECURITY
==================================================

Use cryptographically secure OTP generation.

Recommended OTP:

6 digits

Recommended expiry:

10 minutes

Recommended limits:

- single-use
- expire after successful verification
- resend throttled
- verification attempt limit
- old OTP invalidated when new OTP generated

Do NOT store plaintext OTP in DB.

Do NOT log OTP.

Do NOT expose OTP in HTML, JSON, exception output or logs.

==================================================
EMAIL CONTENT
==================================================

Use existing:

PlatformMailService
stored Platform SMTP

Do NOT create another SMTP implementation.

Email subject concept:

Company Deletion Verification Code

Email must clearly state:

- Company Name
- deletion was requested by DG ERP Super Admin
- OTP
- expiry
- permanent deletion warning

Do NOT include passwords or unrelated secrets.

Example concept:

A permanent deletion request has been initiated for:

{Company Name}

Verification Code:
{OTP}

This code expires in 10 minutes.

If this deletion was not expected, contact the platform administrator immediately.

==================================================
STEP 2 — OTP VERIFY
==================================================

Super Admin enters OTP.

Backend must validate:

- correct company
- correct OTP purpose
- correct requesting Super Admin
- not expired
- not already used
- attempts not exceeded
- hash matches

Company ID manipulation must not allow deleting another Company.

==================================================
STEP 3 — PERMANENT COMPANY DELETE
==================================================

Only after successful OTP verification:

PERMANENTLY delete the Company and ALL Company-owned data.

This is NOT soft delete.

This is NOT block/deactivate.

This is complete tenant destruction.

==================================================
CRITICAL — FULL DEPENDENCY AUDIT FIRST
==================================================

Before implementing delete logic, audit EVERY table/model containing:

company_id

or any indirect Company-owned relationship.

Produce a complete dependency map.

At minimum inspect:

Company Admin users
Company Staff users
User permissions
Role/permission assignments specific to Company

Financial Years

Customers
Customer Transactions

Suppliers
Supplier Transactions

Products
Product Categories
Brands
Units where company-owned
Services
Service Categories

Sales Invoices
Sales Items
Sales Payments
Sales Returns
Sales Return Items
Sales Return Refunds
Sales Return Refund Adjustments

Purchase Invoices
Purchase Items
Purchase Payments
Purchase Returns
Purchase Return Items
Purchase Return Refunds
Purchase Return Refund Adjustments

Stock Transactions
Stock Movements
Inventory Valuations
Sales Cost Snapshots

Accounts
Cash Accounts
Account Transactions

Income
Income Categories
Expenses
Expense Categories

Journals
Journal Items
Journal Audit Events

Accounting Entries
Accounting Entry Lines
Accounting Period Locks
Chart Accounts where Company-owned

Opening Balances
Opening Balance Lines
Opening Balance Audit Events
Opening Balance Legacy records

Loans
Loan Payments
Loan Saving Ledgers
Party Accounts

Employee Accounts
Salary Sheets
Employee Payments

Delivery Notes
Delivery Note Items
Delivery Signatures
Delivery Attachments
Delivery Status Histories

CRM:
Configurations
Leads
Contacts
Opportunities
Follow Ups
Meetings
Tasks
Notes
Attachments
Status Histories

Quotations
Quotation Items

Company Subscriptions
Subscription Payments
Subscription Histories

Company Permissions
User Permissions

WhatsApp Settings
any future Company Communication settings

Password reset records associated with deleted users where appropriate

Sessions belonging to deleted users

Any other Company-owned table discovered in audit.

DO NOT rely only on this list.

Search the entire database schema.

==================================================
SHARED PLATFORM DATA — MUST NOT DELETE
==================================================

Do NOT delete shared/global platform master data merely because a Company referenced it.

Examples may include:

Countries
Roles
global Permissions
Subscription Plans
Billing Cycles
Platform Settings
Platform SMTP Settings
Platform Payment Gateways
Platform Social Links
Super Admin
Super Staff
global configuration

Audit actual ownership before deletion.

Company deletion must never damage another Company.

==================================================
COMPANY ISOLATION — CRITICAL
==================================================

Deleting Company A must delete ONLY Company A data.

Company B, C, D data must remain byte-for-byte logically unaffected.

Test this extensively.

==================================================
USERS
==================================================

Delete:

- Company Admin user(s) belonging to deleted Company
- all Company Staff users belonging to deleted Company
- their Company-specific permissions
- related sessions/reset records where appropriate

Do NOT delete:

- Super Admin
- Super Staff
- users from another Company

==================================================
FILES / PUBLIC FOLDERS
==================================================

Audit all Company-owned file storage.

Delete all files and folders belonging exclusively to the deleted Company.

Inspect:

public/companies/
storage/app/public/
company logos
company signatures
customer KYC/images
product images
attachments
delivery attachments
damage photos
CRM attachments
invoice/purchase attachments
employee documents
any Company-specific generated files

Do NOT guess folder paths.

Determine actual paths from existing code and DB.

Examples conceptually may include:

public/companies/{company_id}/...
storage/app/public/companies/{company_id}/...

but use actual project architecture only.

Do NOT delete shared directories.

Do NOT allow path traversal.

Every filesystem deletion path must be proven Company-owned.

==================================================
FILESYSTEM + DATABASE ORDER
==================================================

Design deletion safely.

Do not delete files first and then discover DB deletion failed.

Preferred pattern:

1. Audit/collect exact Company-owned filesystem paths.
2. Execute DB deletion transaction.
3. Commit successful DB tenant deletion.
4. Remove verified Company-owned files/directories.
5. Safely log filesystem cleanup failure if any remains.

If existing architecture supports a safer transaction/outbox cleanup mechanism, reuse it.

Never rollback an already-committed database deletion merely because a non-critical leftover file could not be removed.

But final report must identify any filesystem cleanup failure.

==================================================
DATABASE DELETE STRATEGY
==================================================

Do NOT blindly add:

cascadeOnDelete()

to dozens of frozen relationships.

First inspect current foreign keys.

Use the safest existing architecture.

Possible strategies:

- existing FK cascades where already correct
- explicit ordered deletion service
- combination of both

Create ONE centralized tenant destruction service.

Conceptually:

CompanyDeletionService

Do NOT place full deletion logic inside Controller.

==================================================
TRANSACTION
==================================================

Database tenant destruction must execute in a DB transaction where practical.

If any required DB delete fails:

ROLLBACK DB deletion.

Company must not remain partially deleted.

==================================================
NO ORPHAN DATA
==================================================

After deletion, there must be no Company-owned orphan rows.

Verify every company-owned table.

Also verify indirect relationships that do not themselves contain company_id.

Examples:

Sales Item
→ Sales Invoice
→ Company

Those must be removed too.

==================================================
COMPANY REGISTRATION HISTORY
==================================================

Audit existing Company Registration relationship.

Business requirement:

If the approved registration exclusively represents the deleted Company, determine whether it should also be permanently deleted.

For this task, default to deleting the associated approved Company Registration only if there is a safe authoritative relationship proving it belongs to that Company.

Do NOT delete unrelated registration history by matching only company name.

Prefer stable IDs / email + proven relationship.

If the current schema cannot safely establish ownership:

REPORT it instead of guessing.

==================================================
DELETE OTP STORAGE
==================================================

Audit existing OTP/password reset request infrastructure first.

If existing secure one-time-code architecture can be safely generalized/reused:
reuse it.

Otherwise create a NEW additive migration after current latest migration.

Do NOT modify frozen migrations.

Conceptual fields:

id
company_id
requested_by
purpose
otp_hash
expires_at
attempts
verified_at / used_at
created_at
updated_at

Do not store plaintext OTP.

==================================================
AUDIT LOG
==================================================

Because the Company itself will be deleted, retain a minimal PLATFORM audit record if the project has an approved platform audit architecture.

It may record only safe metadata such as:

- deleted company ID
- deleted company name
- requested_by Super Admin ID
- deletion timestamp
- verification success
- deletion result

Do NOT retain deleted Company's transactional/business data in the audit log.

Do NOT retain passwords, OTP or secrets.

If no platform audit architecture exists, report before inventing a large audit system.

==================================================
DELETE CONFIRMATION PAGE
==================================================

Flow:

Companies Management
→ Delete
→ Confirmation page/modal

Display:

Company Name
Company Email
Authenticated Super Admin Email
Permanent deletion warning

Button:

Send Verification Code

After send:

OTP input
Verify & Permanently Delete

Never delete on a GET request.

All destructive actions:

POST / DELETE
with CSRF.

==================================================
REPLAY PROTECTION
==================================================

After successful deletion:

- OTP becomes unusable
- refresh/back/re-submit must not delete another tenant
- duplicate request must fail safely

==================================================
EMAIL FAILURE
==================================================

If OTP email cannot be sent:

NO Company deletion.

Show safe error to Super Admin.

Do not expose SMTP credentials/provider internals.

==================================================
CONCURRENT SAFETY
==================================================

Prevent two simultaneous delete confirmations for the same Company from creating inconsistent results.

Use locking/transaction strategy where appropriate.

==================================================
TEST REQUIREMENTS
==================================================

Add comprehensive tests.

At minimum:

1. Only the authenticated canonical Super Admin can initiate deletion.
2. Company Admin cannot initiate it.
3. Staff cannot initiate it.
4. OTP goes to the authenticated canonical Super Admin email.
5. Request-supplied recipient cannot override email.
6. Super Staff cannot initiate permanent deletion even when assigned `platform_companies_delete`.
7. Plain OTP not stored.
8. OTP expires.
9. Wrong OTP fails.
10. Attempt limit enforced.
11. Used OTP cannot be reused.
12. New OTP invalidates previous OTP.
13. No deletion occurs before OTP verification.
14. Valid OTP deletes Company.
15. Company Admin users deleted.
16. Company Staff users deleted.
17. Company-specific permissions deleted.
18. Customers deleted.
19. Suppliers deleted.
20. Sales data deleted.
21. Purchase data deleted.
22. Accounting data deleted.
23. Stock data deleted.
24. Loans deleted.
25. Income/Expense data deleted.
26. CRM data deleted.
27. Delivery data deleted.
28. Employee/payroll data deleted.
29. Financial Years deleted.
30. Subscription/company billing data deleted.
31. WhatsApp/company settings deleted.
32. Company-owned files deleted.
33. Company-owned public folder deleted.
34. Company logo/signature deleted.
35. Company A deletion does NOT alter Company B.
36. Shared countries remain.
37. shared roles remain.
38. global permissions remain.
39. Subscription Plans remain.
40. Platform Settings remain.
41. Super Admin remains.
42. Super Staff remains.
43. Failed DB deletion rolls back.
44. Wrong/expired OTP leaves all Company data untouched.
45. Duplicate/replayed OTP fails.
46. No orphan Company-owned records remain.
47. Full ERP suite passes.

==================================================
IMPORTANT PRODUCTION SAFETY
==================================================

DO NOT run destructive deletion against real production data during development/testing.

Use test database only.

DO NOT test permanent deletion using an actual production Company.

DO NOT run:

migrate:fresh
migrate:refresh
db:wipe

on production.

==================================================
DO NOT CHANGE
==================================================

Do NOT change unrelated:

AD/BS
Financial Year rules
Sales calculations
Purchase calculations
Accounting formulas
Ledger posting
Stock calculations
VAT
WhatsApp Share
Password Reset
Company Approval workflow
Company Registration creation
Authentication
unrelated permissions
SMTP settings
Platform login
unrelated UI

==================================================
WORKFLOW
==================================================

FIRST:

git status --short
git diff --check

Then perform dependency audit.

IMPORTANT:

Before writing destructive code, return/audit internally the exact deletion dependency map.

Only implement when each Company-owned relationship is understood.

Run focused tests.

Then:

php artisan test --compact

If assets changed:

npm run build

Then:

git diff --check
git diff --stat
git status --short

DO NOT COMMIT.
DO NOT PUSH.

==================================================
FINAL REPORT REQUIRED
==================================================

Return:

1. Complete Company dependency map discovered.
2. Shared/global tables intentionally preserved.
3. Exact authenticated canonical Super Admin email resolution.
4. OTP architecture.
5. OTP expiry/attempt/resend rules.
6. Database deletion order.
7. File/folder deletion architecture.
8. Files/folders deleted.
9. Company Registration handling decision.
10. Files created.
11. Files modified.
12. Migrations created.
13. Authorization enforcement.
14. Transaction/rollback strategy.
15. Cross-company isolation tests.
16. Orphan-data verification.
17. Focused test results.
18. Full suite results.
19. git diff --check.
20. git status --short.
21. Anything that could not safely be auto-deleted and why.

DO NOT COMMIT.
DO NOT PUSH.

==================================================
FINAL GOLDEN RULE
==================================================

Company Permanent Delete is allowed ONLY after valid OTP sent to the authenticated canonical Super Admin email.

Valid OTP
→ delete the selected Company
→ delete ALL Company-owned users/data/files
→ preserve ALL other Companies and shared platform data.

No OTP
→ NO DELETE.

Wrong/expired OTP
→ NO DELETE.

Partial deletion is NOT acceptable.
