# DG ERP MASTER BUSINESS STANDARD
## Version: 1.0
## Status: FROZEN
## Authority: Business Owner
## Applies To: All DG ERP Modules

---

# 1. PURPOSE

This document defines the Master Business Standard for the entire DG ERP System.

It establishes one unified business architecture so that every module follows the same philosophy, workflow, validation, audit standards, coding standards, documentation standards, and business rules whenever applicable.

This document is GLOBAL.

It governs every business module.

---

# 2. MASTER BUSINESS STANDARD

The Sales Module is the MASTER BUSINESS STANDARD of DG ERP.

Reference Document:

docs/02_DG_ERP_SALES_MODULE_STANDARD.md

Whenever an equivalent business process already exists inside the Sales Module, every future module SHALL reuse that business architecture.

Business entities may change.

Business direction may change.

Architecture SHALL remain consistent.

---

# 3. MASTER PRINCIPLES

The following principles are frozen.

## Principle 1

Reuse before creating.

Never create a new business rule if an equivalent Sales rule already exists.

---

## Principle 2

Business architecture remains identical.

Only business entities change.

---

## Principle 3

Coding philosophy remains identical.

---

## Principle 4

Validation philosophy remains identical.

---

## Principle 5

Audit philosophy remains identical.

---

## Principle 6

Documentation philosophy remains identical.

---

## Principle 7

Financial Year rules remain identical.

---

## Principle 8

Company Isolation remains identical.

---

## Principle 9

Cancel philosophy remains identical.

Status changes.

History remains.

Physical deletion is prohibited unless explicitly approved by the Business Owner.

---

# 4. MIRROR MODULE RULE

Whenever a module has an equivalent business workflow already implemented in Sales,

that module SHALL become a Mirror Module.

Mirror Modules SHALL copy the Sales architecture.

Only business direction changes.

No unnecessary business-rule differences are permitted.

---

# 5. PURCHASE MODULE

Purchase Module is the official Mirror Module of the Sales Module.

The Purchase Module SHALL mirror:

• Workflow

• Validation

• Services

• Controllers

• Business Logic

• Constitution Structure

• Audit Rules

• Documentation Structure

• Financial Year Handling

• Company Isolation

• Cancel Rules

• Status Rules

• Stored Amount Philosophy

• Summary Calculation Philosophy

---

# 6. ENTITY MAPPING

Sales Invoice
→ Purchase Invoice

Sales Payment
→ Purchase Payment

Sales Return
→ Purchase Return

Sales Return Refund
→ Purchase Return Refund (if applicable)

Customer
→ Supplier

Customer Ledger
→ Supplier Ledger

Customer Transaction
→ Supplier Transaction

Sales
→ Purchase

---

# 7. BUSINESS DIRECTION MAPPING

Sales Invoice

Product Stock

OUT

Purchase Invoice

Product Stock

IN

--------------------------------------

Sales Return

Stock IN

Purchase Return

Stock OUT

--------------------------------------

Sales Invoice

Customer Debit

Purchase Invoice

Supplier Credit

--------------------------------------

Sales Payment

Customer Credit

Purchase Payment

Supplier Debit

--------------------------------------

Sales Return Refund

Customer Credit

Purchase Return Refund

Supplier Debit

Only the business direction changes.

Architecture SHALL remain identical.

---

# 8. CONSTITUTION STANDARD

Every module shall maintain its own Constitution document.

However,

all Constitutions SHALL follow the same structure whenever possible.

Example

Sales Constitution

↓

Purchase Constitution

↓

Inventory Constitution

↓

HR Constitution

↓

Account Constitution

↓

Loan Constitution

Section numbering should remain consistent whenever practical.

---

# 9. IMPLEMENTATION STANDARD

The following standards SHALL remain identical across mirrored modules.

Controllers

Services

Validation

Sync Services

Stock Services

Ledger Services

Account Services

Database philosophy

Stored business fields

Cancel workflow

Audit workflow

Summary calculations

Print logic

Dashboard logic

Financial calculations

---

# 10. CHANGE MANAGEMENT

Whenever a business rule changes inside the Sales Module,

the Business Owner MUST determine whether the same rule applies to:

Purchase Module

Inventory Module

Account Module

HR Module

Loan Module

Delivery Module

Any other mirrored module.

Equivalent modules SHALL be reviewed before implementation is considered complete.

---

# 11. PROHIBITED

Mirror Modules SHALL NOT:

Invent independent workflows without Business Owner approval.

Invent different validation philosophy.

Invent different audit philosophy.

Invent different cancel philosophy.

Invent different Financial Year philosophy.

Invent different Company Isolation philosophy.

Invent different coding standards.

Invent different document structure.

---

# 12. BUSINESS OWNER AUTHORITY

Only the Business Owner may approve:

New business rules

Business workflow changes

Architecture changes

Mirror exceptions

Permanent deviations

All other implementations shall follow this Master Business Standard.

---

# 13. FUTURE MODULES

The following modules SHALL evaluate the Sales Module before defining business rules.

Purchase

Inventory

Accounts

Expenses

Income

Loan

HR

Payroll

Branch

Delivery

Projects

Assets

CRM

POS

Service

Production

Any future module.

---

# 14. FINAL RULE

Sales Module is the Master Business Standard of DG ERP.

Every equivalent business module SHALL reuse the Sales business architecture.

Only business direction may change.

Business philosophy SHALL remain unified across the entire ERP.

This rule is FINAL.

This rule is FROZEN.

Business Owner approval is required before any modification.

---

END OF DOCUMENT