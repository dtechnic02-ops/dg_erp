==========================================================
DG ERP ROLE & PERMISSION STANDARD
Version: 4.0 (FREEZE)
Status: FINAL — PERMANENT BUSINESS RULE
Authority: Business Owner
Applies To: Authentication, Authorization, Platform, Company, Staff, Subscription
Reference: docs/01_DG_ERP_MASTER_DEVELOPMENT_STANDARD.md
==========================================================

PURPOSE

This document freezes the official DG ERP Role and Permission
Architecture.

This is the permanent business rule for the entire ERP.

Every Developer,
Every AI,
Every Cursor Prompt,
Every Future Version
must strictly follow this document.

No code change,
no database change,
and no workflow change may violate this standard
unless approved by the Business Owner.

==========================================================
RELATED DOCUMENTATION
==========================================================

Authentication & Login Redirect
→ Role helper methods determine dashboard destination only.
→ Authorization never uses role_id checks.

Platform Permission (Super Admin / Super Staff)
→ Platform Module Permission + Platform Action Permission.
→ Super Admin: implicit full platform access.
→ Super Staff: individual assignment only — no defaults.

Company Management
→ Registration, approval, block, unblock, delete are Platform actions.
→ Company Profile is Company Admin only.

User / Staff Management
→ Company Admin manages staff inside own company only.
→ Company Staff cannot access User Management.
→ Permissions are assigned individually to each staff member.
→ Module Permission + Action Permission (both required).

Subscription
→ docs/10_DG_ERP_SUBSCRIPTION_MODULE_STANDARD.md
→ Staff creation limit is enforced by active subscription plan.

==========================================================
CORE PRINCIPLE
==========================================================

DG ERP has three independent identity systems.

1. System Role (role_id)
   System identity only.
   Determines login dashboard destination.
   NEVER used for authorization.

2. Job Role (job_role)
   Employee position inside a company.
   Examples: cashier, accountant, manager.
   Does NOT grant ERP access by itself.

3. Permission
   The ONLY authorization mechanism.
   Platform permissions: TWO levels required —
   Platform Module Permission + Platform Action Permission.
   Company permissions: TWO levels required —
   Module Permission + Action Permission.
   Super Staff: assigned individually per user.
   Company Staff: assigned individually per user.
   Super Admin: implicit full platform access (non-removable).

These three systems remain independent.

==========================================================
DG ERP ROLE HIERARCHY — SYSTEM RESERVED
==========================================================

The following Role IDs are system reserved.
They must NEVER be reassigned, renamed in meaning, or reused.

Role ID 1
Super Admin

Role ID 2
Company Admin

Role ID 3
Company Staff

Role ID 4
Super Staff

Users NEVER manually choose Role ID.
Role ID is assigned automatically by the system
during installation, approval, or staff creation workflows.

==========================================================
ROLE CREATION RULES
==========================================================

RULE 1 — SUPER ADMIN (Initial Installation)

Only ONE Super Admin exists during initial installation.

role_id = 1

No manual role selection.

----------------------------------------------------------

RULE 2 — SUPER STAFF (Created by Super Admin)

Super Admin can create Super Staff.

When Super Admin creates Super Staff:

System automatically assigns
role_id = 4

No manual role selection.

Super Staff receives permissions ONLY from Super Admin.
Super Staff has NO default permissions.

----------------------------------------------------------

RULE 3 — COMPANY ADMIN (Created on Company Approval)

Company Registration does NOT create a Company directly.

Workflow:

Visitor
↓
Company Registration
↓
Pending
↓
Approve
↓
Company Created
↓
Company Admin Created

During approval the system automatically assigns
role_id = 2

No manual role selection.

Platform NEVER manually creates a Company.
Platform NEVER edits Company Profile.

----------------------------------------------------------

RULE 4 — COMPANY STAFF (Created by Company Admin)

Company Admin creates Staff.

System automatically assigns
role_id = 3

No manual role selection.

Staff creation is controlled by Subscription Plan.
Company Admin cannot exceed Staff Limit.

==========================================================
ROLE ASSIGNMENT SUMMARY — SYSTEM GENERATED ONLY
==========================================================

Event                          | role_id
-------------------------------|--------
Initial Super Admin install    | 1
Company Approval               | 2
Company Staff Creation         | 3
Super Staff Creation           | 4

Manual Role ID selection is forbidden in all workflows.

==========================================================
PLATFORM RESPONSIBILITY
==========================================================

Platform means:

• Super Admin
• Super Staff

Platform controls:

• Registration
• Approval
• Reject
• Company Block
• Company Unblock
• Company Delete
• Subscription
• Plans
• Platform Settings
• Super Staff Management

Platform NEVER edits Company Profile.
Platform NEVER creates Company manually.

==========================================================
SUPER ADMIN
==========================================================

Super Admin has FULL Platform Access.

All Platform Modules.
All Platform Actions.

Super Admin permissions cannot be removed.
Super Admin does NOT require manual permission assignment.

Super Admin authorization is implicit for:

• Every Platform Module Permission
• Every Platform Action Permission

Super Admin may:

• Create Super Staff
• Edit Super Staff
• Delete Super Staff
• Block Super Staff
• Assign Super Staff Permissions (individual)
• Approve Company
• Reject Company
• Block Company
• Unblock Company
• Delete Company
• Manage Plans
• Manage Subscription
• Manage Platform Settings

Super Admin must NEVER:

• Manually create Company
• Edit Company Profile
• Receive Company-scope permissions for ERP tenant operations

==========================================================
SUPER STAFF
==========================================================

Super Staff has NO default permissions.

Every Platform Module must be assigned individually.
Every Platform Action must be assigned individually.

Super Admin assigns permissions to EACH Super Staff user.

Super Staff may perform ONLY assigned Platform permissions.

Super Staff is NOT a Company user.
Super Staff must NEVER receive Company-scope permissions.

Example — Super Staff A

Modules:
• Companies
• Company Approval

Actions:
• company_view
• company_approve
• company_reject
• company_block

Cannot:
• Delete Company
• Manage Plans
• Manage Settings

Two Super Staff users may have completely different permissions.

==========================================================
OFFICIAL PLATFORM PERMISSION MODEL — TWO LEVELS
==========================================================

Platform Permissions are divided into TWO independent levels.

Level 1 — Platform Module Permission
Level 2 — Platform Action Permission

Both levels are mandatory.
Both must pass before access is granted.

This model applies ONLY to Platform-scope permissions
(Super Admin and Super Staff).

----------------------------------------------------------
LEVEL 1 — PLATFORM MODULE PERMISSION
----------------------------------------------------------

Purpose

Control which Platform menus are visible to each
individual Super Staff user.

If a module is not permitted, it must NOT appear in:

• Sidebar
• Dashboard
• Quick Menu
• Search
• Navigation

Platform Module examples

• Dashboard
• Companies
• Company Approval
• Subscriptions
• Plans
• Users
• Super Staff
• Roles
• Permissions
• Reports
• Settings
• System Logs
• Audit Logs
• Support
• Announcements
• Backups
• Maintenance

Every Super Staff may have different visible modules.

Example — Super Staff A

Permitted modules:
• Dashboard
• Companies
• Company Approval

Hidden modules:
• Subscriptions
• Plans
• Settings

Naming convention (documentation standard)

platform_module_<module_code>

Examples:
• platform_module_dashboard
• platform_module_companies
• platform_module_company_approval
• platform_module_subscriptions
• platform_module_plans
• platform_module_super_staff
• platform_module_settings
• platform_module_audit_logs

----------------------------------------------------------
LEVEL 2 — PLATFORM ACTION PERMISSION
----------------------------------------------------------

Purpose

Control what operations a Super Staff may perform
inside a permitted Platform module.

Platform Action examples

• View
• Create
• Edit
• Delete
• Approve
• Reject
• Block
• Unblock
• Suspend
• Activate
• Assign Permission
• Reset Password
• Export
• Import
• Restore
• Backup
• System Settings

Every action is assigned individually.

Example — Companies Module (Super Staff A)

• View     → YES
• Block    → YES
• Unblock  → YES
• Delete   → NO

Example — Company Approval Module (Super Staff A)

• View     → YES
• Approve  → YES
• Reject   → YES

Naming convention (documentation standard)

<platform_module>_<action>

Examples:
• company_view
• company_block
• company_unblock
• company_delete
• company_approve
• company_reject
• subscription_view
• subscription_create
• plan_edit
• super_staff_assign_permission

----------------------------------------------------------
PLATFORM PERMISSION PRIORITY — BOTH MUST PASS
----------------------------------------------------------

Platform Module Permission
↓
Platform Action Permission
↓
Access Granted

If Platform Module Permission fails
→ module is invisible and all routes/actions are denied

If Platform Module Permission passes but Platform Action fails
→ module may be visible in read-only context
→ denied buttons, routes, controllers, middleware, APIs, services

UI hiding alone is NOT sufficient.
Authorization must always validate permissions.

----------------------------------------------------------
INDIVIDUAL PLATFORM PERMISSION RULE — PER SUPER STAFF
----------------------------------------------------------

Each Super Staff user has individual Platform permissions.

Two Super Staff users may have completely different permissions.

Super Admin assigns permissions individually through
permission_user records (or equivalent individual assignment).

Permissions are NEVER inherited automatically.
Permissions are NEVER granted by Job Role or System Role alone.

----------------------------------------------------------
PLATFORM PERMISSION UI SPECIFICATION
----------------------------------------------------------

Super Admin Permission Assignment Screen for Super Staff
must contain TWO sections.

Scope filter: platform only
Company permissions must NEVER appear.

SECTION 1 — Platform Module Permissions

Checkbox list:

☐ Dashboard
☐ Companies
☐ Company Approval
☐ Subscriptions
☐ Plans
☐ Users
☐ Super Staff
☐ Roles
☐ Permissions
☐ Reports
☐ Settings
☐ Audit Logs
☐ Backups
☐ Maintenance

Rule:
If unchecked → module hidden everywhere in platform navigation.

SECTION 2 — Platform Action Permissions

Grouped by Platform module.

Example layout:

Companies
☐ View  ☐ Block  ☐ Unblock  ☐ Delete

Company Approval
☐ View  ☐ Approve  ☐ Reject

Subscriptions
☐ View  ☐ Create  ☐ Edit  ☐ Delete

Rule:
Action checkboxes apply ONLY inside modules granted in Section 1.

==========================================================
COMPANY ADMIN
==========================================================

Can manage ONLY own company.

Can:

• Update own Company Profile
• Create Staff
• Edit Staff
• Delete Staff
• Block Staff
• Unblock Staff
• Reset Staff Password
• Manage Individual Staff Permissions (Company scope only)

Cannot:

• Exceed Staff Limit defined by active Subscription Plan
• Access another Company
• Manage Platform
• Approve / Block / Delete companies

==========================================================
COMPANY STAFF
==========================================================

Cannot access User Management.

Cannot:

• Create Staff
• Edit Staff
• Block Staff
• Unblock Staff
• Delete Staff
• Manage Company Profile
• Manage Platform

Can use ONLY individually assigned Company permissions.

Company Staff receives:

• Module Permissions (menu visibility)
• Action Permissions (operations inside permitted modules)

Both levels are required.
Job Role does NOT grant access.

==========================================================
OFFICIAL COMPANY PERMISSION MODEL — TWO LEVELS
==========================================================

Company Permissions are divided into TWO independent levels.

Level 1 — Module Permission
Level 2 — Action Permission

Both levels are required.
Both must pass before access is granted.

This model applies ONLY to Company-scope permissions
(Company Admin and Company Staff).

Platform permissions remain single-level Platform actions.

----------------------------------------------------------
LEVEL 1 — MODULE PERMISSION
----------------------------------------------------------

Purpose

Control which ERP modules (menus) are visible to each
individual staff member.

If a module is not permitted, it must NOT appear in:

• Sidebar
• Dashboard
• Quick Menu
• Search
• Navigation

Module Permission examples

• Dashboard
• Sales
• Purchase
• Purchase Return
• Sales Return
• Customer
• Supplier
• Product
• Inventory
• Expense
• Income
• Accounts
• Loan
• Report
• HR
• Payroll
• Settings

Every staff member may have different visible modules.

Example — Ram

Permitted modules:
• Dashboard
• Sales
• Customer

Hidden modules:
• Purchase
• Expense
• Report

Naming convention (documentation standard)

module_<module_code>

Examples:
• module_dashboard
• module_sales
• module_purchase
• module_customer
• module_expense
• module_income
• module_hr
• module_reports

----------------------------------------------------------
LEVEL 2 — ACTION PERMISSION
----------------------------------------------------------

Purpose

Control what operations a staff member may perform
inside a permitted module.

Action Permission examples

• View
• Create
• Edit
• Delete
• Print
• Cancel
• Approve
• Reject
• Payment
• Receive Payment
• Return
• Export
• Import
• Restore
• Archive

Every action is assigned individually.

Example — Sales Module (Ram)

• View     → YES
• Create   → YES
• Edit     → NO
• Delete   → NO
• Cancel   → NO
• Print    → YES
• Approve  → NO

Naming convention (documentation standard)

<module>_<action>

Examples:
• sales_view
• sales_create
• sales_edit
• sales_delete
• sales_print
• sales_cancel
• customer_view
• customer_create
• purchase_view
• purchase_create

----------------------------------------------------------
PERMISSION PRIORITY — BOTH MUST PASS
----------------------------------------------------------

Module Permission
↓
Action Permission
↓
Access Granted

If Module Permission fails
→ module is invisible and all routes/actions are denied

If Module Permission passes but Action Permission fails
→ module may be visible (read-only context)
→ denied action buttons, routes, APIs, controllers, services

UI hiding alone is NOT sufficient.
Authorization must always validate permissions.

==========================================================
INDIVIDUAL PERMISSION RULE — PER STAFF
==========================================================

Permissions are assigned to EACH STAFF member individually.

Two employees with the same Job Role may have completely
different permissions.

Example — Ram

Job Role: Cashier

Modules:
• Sales
• Customer

Actions:
• sales_create
• sales_view
• sales_print

----------------------------------------------------------

Example — Shyam

Job Role: Cashier

Modules:
• Sales
• Purchase

Actions:
• sales_view
• sales_edit
• purchase_view
• purchase_create

Even though both are Cashiers,
their permissions may be completely different.

Company Admin assigns permissions per staff user.
Permissions are NEVER inherited from Job Role.
Permissions are NEVER copied automatically from another staff
unless explicitly duplicated by Company Admin action.

==========================================================
JOB ROLE RULE — DESIGNATION ONLY
==========================================================

Job Role is ONLY a designation label.

Examples

• Cashier
• Accountant
• Store Keeper
• Sales Officer
• Manager

Job Role NEVER determines permissions.

Job Role NEVER controls:

• Sidebar visibility
• Module access
• Create / Edit / Delete
• Print / Cancel / Approve

Permissions are assigned individually through the
Company Permission Assignment UI.

==========================================================
COMPANY PERMISSION UI SPECIFICATION
==========================================================

The Permission Assignment Screen must contain TWO sections
for each individual staff member.

Scope filter: company only
Platform permissions must NEVER appear.

----------------------------------------------------------
SECTION 1 — MODULE PERMISSIONS
----------------------------------------------------------

Checkbox list of ERP modules.

Examples:

☐ Dashboard
☐ Sales
☐ Purchase
☐ Purchase Return
☐ Sales Return
☐ Inventory
☐ Customer
☐ Supplier
☐ Expense
☐ Income
☐ Accounts
☐ Loan
☐ HR
☐ Payroll
☐ Reports
☐ Settings

Rule:
If unchecked → module hidden everywhere in navigation.

----------------------------------------------------------
SECTION 2 — ACTION PERMISSIONS
----------------------------------------------------------

Grouped by module.

Example layout:

Sales
☐ View   ☐ Create   ☐ Edit   ☐ Delete
☐ Cancel ☐ Print    ☐ Approve ☐ Export

Customer
☐ View   ☐ Create   ☐ Edit   ☐ Delete

Purchase
☐ View   ☐ Create   ☐ Edit   ☐ Delete

Rule:
Action checkboxes apply ONLY inside modules granted in Section 1.
If module is denied in Section 1,
all action permissions for that module are ignored and denied.

UI must be generated from the official module/action registry.
No hardcoded role_id checks in the UI.

==========================================================
COMPANY PROFILE RULE
==========================================================

Company Profile belongs to the Company.

Only Company Admin may update Company Profile.

Platform cannot edit Company Profile.

Permission examples:

• view_company_profile
• edit_company_profile

These are Company-scope permissions.
Only Company Admin receives them by default policy.

==========================================================
LOGIN DASHBOARD RULE
==========================================================

System Role opens dashboard only.
System Role does NOT authorize actions.

Super Admin        → Admin Dashboard
Super Staff        → Admin Dashboard
Company Admin      → Company Dashboard
Company Staff      → Company Dashboard

Expired company    → Subscription page (before ERP access)
Disabled user      → Access denied
Unknown role       → Unauthorized

Dashboard routing uses Role helper methods.
Authorization uses Permission framework only.

==========================================================
PERMISSION ARCHITECTURE — TWO DOMAINS
==========================================================

DG ERP uses TWO Permission Domains.

Both domains use ONE permissions table.
Both domains use TWO permission levels (Module + Action).
Permissions are separated by scope — NOT by separate tables.

Platform Domain  → scope = platform  → Module + Action (individual per Super Staff)
Company Domain → scope = company   → Module + Action (individual per Company Staff)

----------------------------------------------------------
DOMAIN 1 — PLATFORM PERMISSIONS
----------------------------------------------------------

Scope: platform

Used ONLY by:

• Super Admin (implicit full access — all modules and actions)
• Super Staff (individual assignment only)

Platform permissions are TWO-LEVEL:

Level 1 — Platform Module Permission (platform menu visibility)
Level 2 — Platform Action Permission (platform operations)

Examples — Platform Module Level

• platform_module_dashboard
• platform_module_companies
• platform_module_company_approval
• platform_module_subscriptions
• platform_module_plans
• platform_module_super_staff
• platform_module_settings
• platform_module_audit_logs

Examples — Platform Action Level

• company_view
• company_approve
• company_reject
• company_block
• company_unblock
• company_delete
• subscription_view
• subscription_create
• plan_edit
• super_staff_create
• super_staff_assign_permission

Platform permissions must NEVER appear inside
Company Permission UI.

Super Staff permissions are assigned individually per user.
Super Admin does not require permission_user assignment.

----------------------------------------------------------
DOMAIN 2 — COMPANY PERMISSIONS
----------------------------------------------------------

Scope: company

Used ONLY by:

• Company Admin
• Company Staff

Company permissions are TWO-LEVEL:

Level 1 — Module Permission (module visibility)
Level 2 — Action Permission (operations inside module)

Examples — Module Level

• module_dashboard
• module_sales
• module_purchase
• module_customer
• module_expense
• module_income
• module_hr
• module_reports

Examples — Action Level

• view_company_profile
• edit_company_profile
• manage_users
• edit_users
• block_user
• delete_user
• reset_password
• sales_view
• sales_create
• sales_edit
• sales_print
• customer_view
• customer_create
• purchase_view
• purchase_create
• expense_create
• income_create
• stock_view
• report_view

Company permissions must NEVER appear inside
Platform Permission UI.

Individual assignment rule

Company permissions are assigned to EACH staff user
individually — NOT through Job Role and NOT through
a single global staff role template shared across tenants.

----------------------------------------------------------
PERMISSION UI RULE
----------------------------------------------------------

Platform Permission Screen (per Super Staff user)
→ scope = platform only
→ Section 1: Platform Module Permissions
→ Section 2: Platform Action Permissions (grouped by module)

Company Permission Screen (per Company Staff user)
→ scope = company only
→ Section 1: Module Permissions
→ Section 2: Action Permissions (grouped by module)

Super Admin assigns Platform permissions individually to each Super Staff.
Company Admin assigns Company permissions individually to each Company Staff.

A Company Admin must NEVER assign Platform permissions.
A Super Admin must NEVER assign Company-scope permissions to Super Staff.
Super Staff must NEVER receive Company permissions.
Company Admin and Company Staff must NEVER receive Platform permissions.

==========================================================
PLATFORM / COMPANY SEPARATION RULE — PERMANENT
==========================================================

Platform Permissions are completely separate from Company Permissions.

Scope enforcement is mandatory in:

• Permission seeder
• Permission assignment UI
• Middleware
• Controllers
• Services
• Sidebar rendering
• Search indexing
• API authorization

Super Staff → Platform scope ONLY
Company Admin / Company Staff → Company scope ONLY

Cross-scope assignment is forbidden.

Permission Assignment screens must ALWAYS filter by scope.

If scope = platform → show Platform Module + Platform Action sections only
If scope = company  → show Company Module + Company Action sections only

Never mix scopes in one assignment screen.
Never mix scopes in one permission checkbox list.

==========================================================
DATABASE DESIGN — PERMISSIONS TABLE
==========================================================

Do NOT create two permission tables.

Use ONE permissions table.

Required columns (documentation standard):

scope
Values:
• platform
• company

level
Values:
• module   (Level 1 — module/menu visibility)
• action   (Level 2 — operation control)

Scope + Level combinations:

scope=platform, level=module → Platform Module Permission
scope=platform, level=action → Platform Action Permission
scope=company,  level=module → Company Module Permission
scope=company,  level=action → Company Action Permission

Example rows:

name                           | scope    | level
-------------------------------|----------|--------
platform_module_companies      | platform | module
company_view                   | platform | action
company_approve                | platform | action
platform_module_subscriptions  | platform | module
subscription_create            | platform | action
module_sales                   | company  | module
sales_create                   | company  | action
module_customer                | company  | module
customer_edit                  | company  | action

Rules:

• Platform module permissions → scope = platform, level = module
• Platform action permissions → scope = platform, level = action
• Company module permissions  → scope = company,  level = module
• Company action permissions  → scope = company,  level = action
• Permission seeder must assign scope and level to every permission
• Permission UI must filter using scope and level
• Middleware and controllers must enforce permission name,
  scope, level, and domain context

Individual permission assignment (documentation standard):

Use a user-level permission pivot.

permission_user
• user_id
• permission_id

Rules:

• Super Staff: individual Platform Module + Platform Action via permission_user
• Company Staff: individual Company Module + Company Action via permission_user
• Super Admin: implicit full platform access — no permission_user required
• NEVER rewrite global role records for all tenants
• NEVER assign permissions based on Job Role or System Role alone
• Super Admin manages permission_user for Super Staff
• Company Admin manages permission_user for Company Staff

Company isolation remains:

users.company_id → companies.id

Foreign keys remain unchanged:

users.role_id        → roles.id
permission_role      → roles.id + permissions.id (role defaults only)

Company Admin may receive all company-scope permissions
through role assignment policy.
Company Staff receive individual permissions through
permission_user only.

==========================================================
SECURITY RULES — PERMANENT
==========================================================

NEVER authorize by role_id.

Forbidden in controllers, middleware, views, helpers,
and services:

• role_id == 1
• role_id == 2
• role_id == 3
• role_id == 4
• role_id != N

Authorization MUST use:

• Permission Framework (hasPermission)
• Permission middleware (permission:name)
• Role helper methods (dashboard routing ONLY)
  Examples:
  resolvesToAdminDashboard()
  resolvesToCompanyDashboard()

Role ID exists ONLY as system identity for:

• Automatic assignment during system workflows
• Dashboard routing
• Data relationships

Role ID is NOT an authorization shortcut.

CSRF and correct HTTP methods are required for all
state-changing Platform and Company actions.

Company authorization enforcement layers (mandatory):

1. Module Permission middleware / service check
2. Action Permission middleware / controller check
3. Company isolation (company_id)
4. Subscription module access (where applicable)

Platform authorization enforcement layers (mandatory):

1. Platform Module Permission middleware / service check
2. Platform Action Permission middleware / controller check
3. Scope = platform validation

UI hiding is defense-in-depth only — never sufficient alone.

==========================================================
JOB ROLE — INDEPENDENT SYSTEM (FINAL)
==========================================================

Job Role represents employee position inside a company.

Examples:

• Sales Manager
• Cashier
• Accountant
• Store Keeper
• HR Staff
• Sales Officer

Job Role does NOT replace System Role.
Job Role does NOT replace Permission.
Job Role NEVER determines Module or Action permissions.

Company Admin uses Job Role for organizational labeling only.
ERP access is controlled ONLY by individually assigned Permission.

==========================================================
FINAL ARCHITECTURE
==========================================================

System Role (role_id)
↓
Dashboard Routing Only
↓
Job Role (job_role)
↓
Employee Position Label (NO permission effect)
↓
Permission Domain (platform | company)
↓
Level 1: Module Permission
↓
Level 2: Action Permission
↓
ERP / Platform Access Control (individual per user)

Platform Permissions
→ Super Admin (implicit full access)
→ Super Staff (individual — scope = platform)

Company Permissions
→ Company Admin (role policy + company scope)
→ Company Staff (individual — scope = company)

Module + Action both required in each domain.
Platform and Company scopes never mixed.

==========================================================
IMPLEMENTATION COMPLIANCE CHECKLIST
==========================================================

Before any release touching auth, users, or permissions, verify:

[ ] No role_id authorization in code or views
[ ] Super Admin has implicit full platform module + action access
[ ] Super Staff has NO default platform permissions
[ ] Super Staff platform permissions assigned individually
[ ] Platform UI: Section 1 (Module) + Section 2 (Action)
[ ] Platform UI shows only scope = platform permissions
[ ] Company UI shows only scope = company permissions
[ ] Company UI: Section 1 (Module) + Section 2 (Action)
[ ] Super Staff never receives company-scope permissions
[ ] Company users never receive platform-scope permissions
[ ] Permissions assigned individually per Super Staff and Company Staff
[ ] Job Role does not auto-grant any permission
[ ] Module denied → hidden in sidebar, dashboard, search, navigation
[ ] Action denied → routes, controllers, middleware, APIs reject access
[ ] Module + Action both validated — not UI-only hiding
[ ] Staff creation assigns role_id = 3 automatically
[ ] Approval assigns role_id = 2 automatically
[ ] Super Staff creation assigns role_id = 4 automatically
[ ] Company Admin cannot edit another company
[ ] Platform cannot edit Company Profile
[ ] Company Staff cannot access User Management
[ ] Staff limit enforced by Subscription Plan

==========================================================
FINAL RULE
==========================================================

System Role = Identity + Dashboard
Job Role    = Position Label (never authorization)
Permission  = Authorization (scoped: platform | company)
Platform Permission = Platform Module + Platform Action (individual)
Company Permission  = Module + Action (individual)

These rules are frozen.
Version 4.0 supersedes Version 3.0, 2.0, and 1.0 where conflict exists.

==========================================================
STATUS : FINAL (FREEZE)
VERSION : 4.0
==========================================================
