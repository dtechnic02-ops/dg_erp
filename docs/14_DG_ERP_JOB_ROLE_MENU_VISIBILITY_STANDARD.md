DG ERP
JOB ROLE & MENU VISIBILITY STANDARD
Version: 1.0
Status: FINAL (FROZEN)
Authority: Business Owner

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
