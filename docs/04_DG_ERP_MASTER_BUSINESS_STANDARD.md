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
Architecture may evolve only through Business Owner approval.

No module may introduce a second business architecture when an equivalent architecture already exists.
MASTER ARCHITECTURE

Customer = MASTER

Supplier = MIRROR

Sales = MASTER

Purchase = MIRROR

Income = MASTER

Expense = MIRROR

Business Date = Financial Truth

Financial Year = Financial Boundary

Cancel = Never Delete

These architectural relationships are permanently frozen unless changed by the Business Owner.

---

# 3. MASTER PRINCIPLES

The following principles are frozen.
Business Date philosophy remains identical.

Every financial module shall follow the Financial Year and Business Date Constitution.

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
Purchase shall never introduce business behaviour different from Sales unless approved by the Business Owner.

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
Income
→ Expense

Employee
→ Payroll

Customer Balance
→ Supplier Balance

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

# 7A. PURCHASE RETURN ACCOUNTING RECOGNITION — FINAL FROZEN RULE

**Status:** FINAL AND FROZEN
**Authority:** Business Owner

This section is the final approved clarification of the Purchase Return mirror rule. Purchase Return and Purchase Return Refund / Settlement are two separate business events.

## 7A.1 Purchase Return Event

Purchase Return represents the actual return of purchased goods or services to the Supplier.

For a **Product Purchase Return**:

- Product is returned to the Supplier.
- Stock SHALL move **OUT immediately**.
- The returned purchase value SHALL be recognized in the official Accounting Core at Purchase Return time.

For a **Service Purchase Return**:

- No stock movement occurs.
- Required return-value accounting recognition SHALL follow the approved accounting rules where applicable.

Purchase Return is **not** a money movement event. At Purchase Return creation:

- Cash/Bank SHALL NOT move.
- A Cash/Bank AccountTransaction SHALL NOT be created.
- The return SHALL NOT be treated as cash received.
- The Supplier Ledger SHALL NOT be automatically settled.
- The system SHALL NOT assume that the Supplier has refunded money.

Purchase Return creates the official return/refundable/supplier-adjustment balance. Goods/value recognition and money settlement are separate events.

## 7A.2 Accounting Core Recognition

Purchase Return SHALL recognize the returned purchase value in the official Accounting Core at the time of return through the approved centralized architecture:

Purchase Return → Accounting Integration Layer → AccountingPostingService → AccountingEntry → AccountingEntryLine → General Ledger / Accounting Reports

Every Purchase Return posting SHALL:

- be balanced double entry;
- preserve company isolation;
- use the active Financial Year;
- use the valid Business Date;
- preserve source identity;
- prevent duplicate posting;
- execute atomically;
- support exact reversal; and
- preserve audit history.

Chart Account IDs SHALL NOT be hard-coded. The following mapping is FINAL, APPROVED, and FROZEN.

### SUPPLIER_RETURN_RECEIVABLE Control Account

The Accounting Core SHALL provide the permanent system account:

**System Code:** `SUPPLIER_RETURN_RECEIVABLE`
**Account Class:** Asset
**Business Type:** Supplier Return Receivable clearing-control account

This account represents Purchase Return value already returned to the Supplier but not yet settled through Purchase Invoice / Accounts Payable adjustment, actual Cash/Bank refund, or mixed settlement.

`SUPPLIER_RETURN_RECEIVABLE` SHALL NOT be treated as Cash/Bank, Accounts Payable, Income, Expense, or Supplier settlement itself.

### Product Purchase Return Mapping

At Product Purchase Return recognition:

- **Debit `SUPPLIER_RETURN_RECEIVABLE`:** total approved refundable value.
- **Credit `INVENTORY`:** returned net product purchase value derived from the approved original Purchase value/cost basis, including approved discount allocation.
- **Credit `INPUT_TAX_RECEIVABLE`:** reversible VAT derived from the original persisted Purchase VAT applicable to the returned portion, when applicable.

Current value and selling value SHALL NOT be used for returned inventory.

### Service Purchase Return Mapping

At Service Purchase Return recognition:

- **Debit `SUPPLIER_RETURN_RECEIVABLE`:** total approved refundable value.
- **Credit the original persisted service value account:** normally `SERVICE_PURCHASE_EXPENSE`.
- **Credit `INPUT_TAX_RECEIVABLE`:** reversible VAT derived from the original persisted Purchase VAT applicable to the returned portion, when applicable.

`SERVICE_PURCHASE_EXPENSE` SHALL NOT be assumed when the original service was posted to another approved account.

No Purchase Return recognition entry creates Cash/Bank movement, Cash/Bank AccountTransaction, or automatic Accounts Payable settlement.

## 7A.3 Purchase Return Refund / Settlement Event

Purchase Return Refund is the separate settlement event. Settlement may use one or more of the following methods:

1. **Purchase Invoice / Supplier Adjustment:** Debit `ACCOUNTS_PAYABLE` and Credit `SUPPLIER_RETURN_RECEIVABLE` for the actual approved adjustment amount. Supplier/Purchase Invoice settlement records SHALL reflect the adjustment. Cash/Bank movement is zero, and no Cash/Bank AccountTransaction is created for this portion.
2. **Cash/Bank Refund:** when the Supplier actually returns money, Debit `CASH_IN_HAND` or `BANK_ACCOUNTS` and Credit `SUPPLIER_RETURN_RECEIVABLE` for the amount actually received. Only that amount creates the corresponding Cash/Bank AccountTransaction.
3. **Mixed Settlement:** Debit `ACCOUNTS_PAYABLE` for the invoice-adjustment portion, Debit `CASH_IN_HAND` or `BANK_ACCOUNTS` for the actual Cash/Bank refund portion, and Credit `SUPPLIER_RETURN_RECEIVABLE` for the combined settlement total. Only the actual Cash/Bank portion creates an AccountTransaction.

Purchase Return Refund / Settlement SHALL NOT recognize the returned goods/value a second time. Purchase Return already recognized that value in the Accounting Core. Settlement records only the financial settlement effect appropriate to the invoice/supplier adjustment, actual Cash/Bank refund, or mixed settlement.

Partial settlement and mixed settlement are permitted. Total settlement SHALL never exceed the remaining Purchase Return refundable balance.

## 7A.4 Cancellation and Reversal

Each source event reverses only its own effects:

- Cancelling Purchase Return SHALL reverse the accounting and stock effects created by Purchase Return, subject to approved dependency rules.
- Cancelling Purchase Return Refund / Settlement SHALL reverse only the settlement effects created by that settlement.
- Original posted accounting records SHALL NOT be edited or deleted.
- Reversal SHALL use exact reversal through the approved Accounting Core architecture.

This frozen clarification preserves the Sales/Purchase mirror architecture where applicable while explicitly establishing:

**PURCHASE RETURN = GOODS/VALUE RETURN EVENT**
**PURCHASE RETURN REFUND = SETTLEMENT EVENT**

Money movement occurs only when money actually moves. Invoice adjustment is not Cash/Bank movement.

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
Delete philosophy remains identical.

Financial documents are cancelled.

They are never physically deleted.

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

# 14. ROLE, PERMISSION & ACCESS (FROZEN)

User access, platform control, company isolation, staff limits,
and company permission architecture are governed by the
Role & Permission Standard.

Reference:

docs/12_DG_ERP_ROLE_PERMISSION_STANDARD.md (Version 4.0)

Key frozen rules:

• System Role IDs 1–4 are reserved and system-assigned only
• Platform (Super Admin / Super Staff) vs Company (Admin / Staff) are separated
• Authorization uses Permission framework only — never role_id
• Company Profile is editable by Company Admin only
• Staff creation is limited by Subscription Plan
• Platform permissions: Platform Module + Platform Action (individual per Super Staff)
• Super Admin: implicit full platform access (non-removable)
• Super Staff: NO default permissions — all assigned individually
• Company permissions: Module + Action (individual per Company Staff)
• Job Role is designation only — it NEVER determines permissions
• Module Permission controls visibility; Action Permission controls operations
• Both levels must pass before access is granted in each domain
• Super Staff must NEVER receive Company permissions
• Company users must NEVER receive Platform permissions

---

# 15. FINAL RULE

Sales Module is the Master Business Standard of DG ERP.

Every equivalent business module SHALL reuse the Sales business architecture.

Only business direction may change.

Business philosophy SHALL remain unified across the entire ERP.

This rule is FINAL.

This rule is FROZEN.

Business Owner approval is required before any modification.
Mirror Modules shall inherit improvements made to the Master Module unless explicitly exempted by the Business Owner.

---

END OF DOCUMENT
