DG ERP
JOB ROLE & MENU VISIBILITY STANDARD
Version: 1.0
Status: FINAL (FROZEN)
Authority: Business Owner

BUSINESS OWNER AMENDMENT — JOB ROLE CHANGE PERMISSION RESET
Version: 1.3 Authority Freeze
Status: FINAL — BUSINESS OWNER APPROVED
Effective: 2026-08-09

This amendment freezes the security behavior when a Company Staff Job Role
actually changes. It does not make Job Role an authorization mechanism. Where
wording elsewhere conflicts with this amendment, this amendment controls.

JOB ROLE, CATALOG, AND AUTHORITY

- Job Role controls organizational designation, approved visibility, and the
  assignable-permission catalog boundary.
- Explicit `scope=company` Module and Action permissions control Company Staff
  backend authority.
- Job Role grants zero backend business authority and grants no permission
  automatically.

ACTUAL JOB ROLE CHANGE

Changing a Company Staff member from one Job Role to another requires a clear
warning and explicit confirmation. On confirmation, the Job Role change and
removal of that target staff member's existing explicit company permission
assignments must be atomic.

After the confirmed reset, the new Job Role determines the new assignable
catalog. Company Admin or an explicitly authorized Sub Admin must then assign
the required company Module and Action permissions. The new Job Role itself
must not create, allow, or inherit permissions.

JOB ROLE UNCHANGED

Saving a staff record without an actual Job Role change must not reset explicit
permissions. Changes to name, email, phone, or other staff information do not
constitute a permission-reset event.

CANCELLED OR UNCONFIRMED CHANGE

If the authorized user cancels or does not confirm an actual Job Role change,
the stored Job Role and all existing explicit permissions must remain
unchanged.

TARGET AND SCOPE BOUNDARY

This rule applies to Company Staff, including staff designated as Sub Admin.
Only the target staff member's explicit `scope=company` assignments are reset.
It must not affect Permission master records, another staff member, Company
Admin implicit authority, Super Admin, or Super Staff platform permissions.

END BUSINESS OWNER AMENDMENT — VERSION 1.3

BUSINESS OWNER AMENDMENT — ASSIGNABLE PERMISSION CATALOG BOUNDARY
Version: 1.2 Authority Freeze
Status: FINAL — BUSINESS OWNER APPROVED
Effective: 2026-08-09

This amendment adds one approved Job Role responsibility without creating a
new authorization model. Where wording elsewhere in this document limits Job
Role exclusively to sidebar/dashboard visibility or otherwise conflicts with
this amendment, this amendment controls.

THREE DISTINCT RESPONSIBILITIES

1. Job Role Visibility

Job Role controls approved sidebar, dashboard, widget, and organizational
visibility.

2. Job Role Assignable-Permission Catalog Filter

When managing permissions for Company Staff, Job Role may limit the
`scope=company` Module and Action permissions available for new assignment.
This is an administrative assignment-catalog boundary only. It does not grant,
deny, or evaluate backend access.

3. Explicit Permission-Based Backend Authorization

Backend authority exists only when the staff member has the explicitly
assigned company Module Permission and corresponding company Action
Permission. Job Role never auto-assigns or auto-allows either permission.

RECEIVER CATALOG FREEZE

Receiver's assignable operational domains are:

- Purchase;
- Suppliers;
- Inventory.

Unrelated domains, including CRM, Income, HR, Payroll, and Journal, must not be
available for new Receiver assignment unless the Business Owner changes the
Receiver Job Role definition.

LEGACY ASSIGNMENT PRESERVATION

An existing explicit permission outside a new or changed Job Role filter must
not be silently deleted, revoked, denied, or rewritten. It must remain visible
for review and may be removed only through an explicit authorized cleanup
decision. Applying the filter alone is never a cleanup decision.

BOUNDARIES UNCHANGED

- Company Admin retains full own-company authority.
- Sub Admin and Company Staff remain permission-authorized users.
- Job Role grants zero backend business authority.
- Platform permissions are never assignable through a company Job Role.
- Owner-reserved and platform-reserved permissions remain unavailable to
  Company Staff regardless of Job Role.

END BUSINESS OWNER AMENDMENT — VERSION 1.2

BUSINESS OWNER AMENDMENT — COMPANY SUB ADMIN IDENTITY AND BOUNDARY
Version: 1.1 Authority Freeze
Status: FINAL — BUSINESS OWNER APPROVED
Effective: 2026-08-09

This amendment resolves the Sub Admin identity and authority boundary without
changing the Job Role architecture. Where wording elsewhere in this document
conflicts with this amendment, this amendment controls.

Company Sub Admin is not a System Role. DG ERP continues to have exactly four
System Roles: Super Admin, Company Admin, Company Staff, and Super Staff.

A Company Sub Admin has:

- System Role: Company Staff;
- Job Role: Sub Admin;
- business authority: individually assigned `scope=company` Module and Action
  permissions.

The Sub Admin Job Role grants zero backend business authority. It controls only
sidebar visibility, dashboard visibility, widgets, and organizational
designation. A visible menu is not authorization, and a hidden menu does not
revoke an assigned business permission.

A Sub Admin may receive broad operational authority, including approved staff
management and Company Staff permission management, only through corresponding
company-scope Module and Action permissions. This permission-based exception
overrides the general ordinary-Company-Staff User Management restriction only
for an explicitly authorized Sub Admin.

The following remain unavailable to Sub Admin regardless of assigned
permissions or menu visibility: Company Profile ownership-level administration,
Company Reset, Database Reset, Dangerous Maintenance Tools, System Maintenance,
Cache Clear, Queue Restart, Log Management, Maintenance Mode, System Utilities,
Company Delete or tenant-destructive deletion, platform company approval,
platform company blocking, platform company deletion, and every other action
classified by the Constitution as Company Admin owner-only or Platform-only.

Sub Admin authority is restricted to the user's own company. Sub Admin receives
no platform-scope authority and no cross-company authority.

END BUSINESS OWNER AMENDMENT — VERSION 1.1

PURPOSE

This document defines the official Job Role architecture for DG ERP.

This document controls ONLY:

• Sidebar Menu Visibility
• Dashboard Visibility

This document DOES NOT control:

• View Permission
• Create Permission
• Edit Permission
• Delete Permission
• Print Permission
• Export Permission
• Approve Permission
• Block Permission
• Any business authorization

Those are controlled entirely by the Permission System.

PRECEDENCE

For Sidebar Menu Visibility and Dashboard Visibility only, this standard
supersedes conflicting statements in earlier Role & Permission standards.

Permission remains the only authority for business authorization. Job Role
must never grant, deny, replace, or bypass any business permission.

OFFICIAL PRINCIPLE

DG ERP uses TWO independent security layers.

Layer 1 — Job Role

Purpose: UI Visibility

Controls:

• Sidebar Menu
• Dashboard Cards
• Dashboard Widgets
• Module Visibility

Only.

Layer 2 — Permission

Purpose: Business Authorization

Controls:

• View
• Create
• Edit
• Delete
• Approve
• Reject
• Print
• Export
• Import
• Block
• Restore
• All business actions

Permission NEVER depends on Job Role.
Job Role NEVER grants business permission.

OFFICIAL RULE

Job Role only decides what the employee can SEE.

Permission decides what the employee can DO.

This rule is FINAL. Never mix these two systems.

JOB ROLE LIST

SUPER LEVEL

1. Super Admin

Purpose: System Owner

Visibility:

• Super Dashboard
• Company Management
• Subscription
• Plans
• Pending Companies
• Global Reports
• System Maintenance

Cannot access company transactional data such as Sales, Purchase, Products,
or Customers unless future business policy changes.

2. Super Staff

Purpose: Support Staff

Visibility: Almost the same as Super Admin.

Business limitations are decided by Permission.

COMPANY LEVEL

3. Company Admin

Highest authority inside one company.

Sidebar:

• Dashboard
• Staff Management
• Sales
• Purchase
• Inventory
• Accounting
• HR
• CRM
• Reports
• Settings
• Company Settings
• Subscription
• System Maintenance

Dashboard: All company widgets.

System Maintenance: Visible.

4. Sub Admin

Purpose: Replacement of Company Admin when the Company Admin is absent.

Sidebar: Exactly the same as Company Admin, except System Maintenance is hidden.

Dashboard: Same as Company Admin.

Cannot access:

• Company Reset
• Database Reset
• Dangerous Maintenance Tools

Business authority is controlled only by Permission.

5. Manager

Purpose: Department Manager.

Sidebar:

• Dashboard
• Sales
• Purchase
• Inventory
• Delivery
• HR
• Reports

Dashboard: Department summaries.

6. HR

Sidebar:

• Dashboard
• Staff
• Attendance
• Leave
• Payroll

Dashboard: HR widgets.

7. Accountant

Sidebar:

• Dashboard
• Accounting
• Ledger
• Journal
• Expense
• Income
• Reports

Dashboard: Accounting widgets.

8. Sales

Sidebar:

• Dashboard
• Customer
• Quotation
• Sales

Dashboard: Sales widgets.

9. Cashier

Sidebar:

• Dashboard
• Payment Receive
• Cash Book
• Sales

Dashboard: Cash widgets.

10. Receiver

Sidebar:

• Dashboard
• Purchase
• Goods Receive
• Stock

Dashboard: Receiving widgets.

11. Delivery

Sidebar:

• Dashboard
• Delivery

Dashboard: Delivery widgets.

12. Company Staff

Purpose: General employee.

Sidebar: Only menus assigned by Job Role.

Dashboard: Only assigned widgets.

SYSTEM MAINTENANCE

This menu is extremely sensitive. It contains:

• Company Reset
• Database Reset
• Cache Clear
• Queue Restart
• Log Management
• Maintenance Mode
• System Utilities

Visible ONLY to Company Admin.

Never visible to:

• Sub Admin
• Manager
• HR
• Accountant
• Sales
• Cashier
• Receiver
• Delivery
• Company Staff

SIDEBAR RULE

Every sidebar item belongs to a module. A Job Role only decides Visible or
Hidden. Nothing more.

DASHBOARD RULE

Dashboard Cards, Dashboard Widgets, Dashboard Charts, and Dashboard Summary
are controlled ONLY by Job Role.

Permission never controls dashboard visibility.

PERMISSION RULE

Permission controls every business action, including Sales, Purchase, Product,
Staff, and Roles actions such as View, Create, Edit, Delete, Approve, and Print.

Job Role never changes these permissions.

FUTURE MODULE RULE

Whenever a new module is created, answer only these questions:

1. Which Job Roles can SEE the menu?
2. Which Permissions control the actions?

Nothing else. Architecture never changes.

DEVELOPMENT RULE

Developers MUST NEVER:

• Mix Job Role with Permission.
• Use Job Role for CRUD authorization.
• Hide CRUD buttons based on Job Role.
• Grant business access through Job Role.
• Skip Permission checks because a menu is visible.

BUSINESS RULE

A visible menu DOES NOT mean the user can use it.
A hidden menu DOES NOT remove business permission.
Business authorization is ALWAYS checked by the Permission System.

FINAL ARCHITECTURE

Login
↓
Load Job Role
↓
Build Sidebar Menu
↓
Build Dashboard
↓
User Opens Module
↓
Permission Check
↓
Allow / Deny Business Action

FINAL DECISION (FROZEN)

• Job Role controls Sidebar Menu Visibility only.
• Job Role controls Dashboard Visibility only.
• Permission controls all business actions.
• Company Admin can see System Maintenance.
• Sub Admin has the same operational menus as Company Admin but System
  Maintenance is hidden.
• Future modules must follow the same two-layer architecture without exception.

This architecture is FINAL (FROZEN) and must not be redesigned unless the
Business Owner explicitly approves a change.
