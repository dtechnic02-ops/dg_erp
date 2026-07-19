# DG ERP
# 03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md

Version : 1.0

Status : FINAL (FROZEN)

Owner : DG ERP

Document Type : Business Standard

Applies To :

- Sales
- Sales Payment
- Sales Return
- Sales Return Refund
- Purchase
- Purchase Payment
- Purchase Return
- Purchase Return Refund
- Income
- Expense
- Journal
- Contra
- Loan
- Stock
- Customer Ledger
- Account Ledger
- Reports

---

# 1. PURPOSE

This document defines the official Financial Year and Business Date standards for DG ERP.

Its purpose is to ensure that every financial transaction is posted using the correct Business Date rather than system timestamps.

This standard applies to every financial module inside DG ERP.

---

# 2. SCOPE

This document applies to every module that creates financial transactions.

Including but not limited to:

• Sales

• Sales Payment

• Sales Return

• Sales Return Refund

• Purchase

• Purchase Payment

• Purchase Return

• Purchase Return Refund

• Income

• Expense

• Journal

• Contra

• Loan

• Stock Movement

• Customer Ledger

• Account Ledger

• VAT Reports

• Financial Reports

---

# 3. BUSINESS DATE RULE

Business Date is the ONLY official date used by DG ERP.

Examples:

invoice_date

payment_date

purchase_date

return_date

refund_date

expense_date

income_date

journal_date

loan_date

stock_date

Business Date MUST be used for:

✓ Financial Year Validation

✓ Ledger Posting

✓ Stock Posting

✓ Customer Ledger

✓ Account Ledger

✓ VAT Calculation

✓ Financial Reports

✓ Due Calculation

✓ Balance Calculation

Business Date is the Financial Truth.

See also Section 19 — Business Date Supremacy Rule.

---

# 4. SYSTEM DATE RULE

The following fields are NOT Business Dates.

created_at

updated_at

created_by

updated_by

deleted_by

These fields exist ONLY for:

✓ Audit Trail

✓ User Tracking

✓ Record History

✓ Investigation

✓ Change Tracking

These fields MUST NEVER be used for:

✗ Financial Reports

✗ Ledger Posting

✗ Stock Posting

✗ Financial Year

✗ VAT

✗ Balance Calculation

✗ Due Calculation

---

# 5. FINANCIAL YEAR RULE

Every company owns its own Financial Year.

Financial Year belongs to the company.

Financial Year NEVER belongs to the ERP.

Example 1

Financial Year

2026

Start Date

2026-01-01

End Date

2026-12-31

Example 2

Financial Year

2020-2030

Start Date

2020-01-01

End Date

2030-12-31

Example 3

Financial Year

2083

Start Date

2083-04-01

End Date

2084-03-31

DG ERP MUST NEVER assume:

• Calendar Year

• January Start

• Gregorian Calendar Only

The configured Financial Year always wins.

---

# 6. MULTI-COMPANY FINANCIAL YEAR RULE

Every company manages its own Financial Year.

Example

Company A

2026

Company B

2020-2030

Company C

2083

Company D

FY 24-25

All companies are independent.

One company's Financial Year MUST NEVER affect another company.

Company isolation is mandatory.

---
# 6A. ACTIVE FINANCIAL YEAR RULE

Every company may create multiple Financial Years.

However,

ONLY ONE Financial Year may be Active at any time.

Example

2025    Inactive

2026    Active

2027    Inactive

Multiple Active Financial Years are NOT allowed.

All financial transactions may only be created, edited or cancelled inside the currently Active Financial Year.

If another Financial Year must be used,

the current Financial Year must first be deactivated,

then the required Financial Year must be activated.

This is a Business Approved Rule.

This behaviour is permanently frozen.

# 7. TRANSACTION DATE RULE

Every transaction MUST belong to the selected Financial Year.

Example

Payment Date

2026-05-10

Financial Year

2026

✓ Allowed

Example

Payment Date

2027-01-05

Financial Year

2026

✗ Not Allowed

The ERP MUST reject transactions outside the selected Financial Year.

---

# 7A. BACK-DATE ENTRY RULE

DG ERP supports legal back-date transaction entry.

Example

Current Active Financial Year

2026

A missing invoice must be entered for

2025.

Correct workflow

Deactivate FY 2026

↓

Activate FY 2025

↓

Create / Edit Transaction

↓

Deactivate FY 2025

↓

Activate FY 2026

Transactions MUST NEVER be entered into an Inactive Financial Year.

This is a Business Approved Rule.

# 8. REPORTING RULE

Every financial report MUST use Business Date.

Examples

Sales Report

Payment Report

Purchase Report

Ledger Report

VAT Report

Customer Statement

Supplier Statement

Stock Report

Income Report

Expense Report

Journal Report

Reports MUST NEVER use:

created_at

updated_at

---

# 9. POSTING RULE

All financial posting uses Business Date.

Stock Posting

↓

Business Date

Customer Ledger

↓

Business Date

Account Ledger

↓

Business Date

Journal Posting

↓

Business Date

VAT Posting

↓

Business Date

Business Date is always the posting date.

---

# 10. VALIDATION RULE

Before saving any financial transaction the ERP MUST validate:

✓ Company

✓ Financial Year

✓ Business Date

✓ Date belongs to Financial Year

✓ Financial Year exists

✓ Financial Year belongs to Company

If validation fails

Transaction MUST NOT be saved.
✓ Financial Year Exists

✓ Financial Year belongs to Company

✓ Financial Year is Active

✓ Business Date belongs to Active Financial Year

✓ Company Ownership

---
# 10A. DEFAULT FILTER RULE

Every financial module must use Business Date for filtering.

Examples

Sales

sale_date

Sales Payment

payment_date

Sales Return

return_date

Sales Return Refund

refund_date

Purchase

purchase_date

Expense

expense_date

Income

income_date

Loan

loan_date

Reports

Business Date

The following fields MUST NEVER be used for filtering

created_at

updated_at

System timestamps are Audit fields only.

# 10B. CANCELLED RECORD RULE

Cancelled transactions exist only for

Audit

History

Investigation

Viewing

Printing

Cancelled transactions MUST NEVER be included in

Sales Totals

Purchase Totals

Customer Balance

Supplier Balance

Ledger Balance

Stock Balance

VAT

Profit & Loss

Trial Balance

Balance Sheet

Dashboard Summary

Financial Reports

When Status Filter = All

Cancelled transactions may be displayed,

but they must never participate in financial calculations.

This rule is permanently frozen.

# 11. CALENDAR INDEPENDENCE RULE

DG ERP does not depend on any specific calendar.

Supported examples:

Gregorian

Nepali Fiscal Year

Long-Term Financial Year

Custom Financial Year

Future calendars may be added.

The ERP always follows the configured Financial Year.

---

# 12. AUDIT TRAIL RULE

Audit fields are for reference only.

created_by

updated_by

deleted_by

created_at

updated_at

Purpose:

• Audit

• History

• Investigation

• User Tracking

Audit fields MUST NEVER affect financial calculations.

---

# 13. DEVELOPER RULES

Every developer must follow these rules.

Always:

✓ Use Business Date

✓ Validate Financial Year

✓ Validate Company Ownership

✓ Keep Financial Year independent

Never:

✗ Use created_at for reports

✗ Use updated_at for posting

✗ Ignore Financial Year validation

✗ Mix company Financial Years

---

# 14. BUSINESS RULES

Business Date is the official transaction date.

Financial Year controls every transaction.

Audit fields never control financial behaviour.

Reports always follow Business Date.

Posting always follows Business Date.

Company isolation is mandatory.

Financial Year validation is mandatory.

---

# 15. FORBIDDEN RULES

DO NOT

❌ Use created_at for Financial Reports

❌ Use updated_at for Ledger Posting

❌ Skip Financial Year Validation

❌ Save transactions outside Financial Year

❌ Mix Financial Years between companies

❌ Assume Calendar Year automatically

❌ Assume January is always the first month

❌ Ignore Company Ownership

❌ Replace Business Date with System Date

---

# 16. FROZEN RULES

The following rules are BUSINESS APPROVED.

Business Date

Financial Year Validation

Company Isolation

Posting Date

Reporting Date

Ledger Date

Stock Date

VAT Date

Business Date Supremacy

Business Date Editing Synchronization

These rules are permanently frozen.

Any modification requires Business Approval.

Changing these rules may produce:

Wrong Financial Reports

Wrong Stock

Wrong Ledger

Wrong Customer Balance

Wrong Supplier Balance

Wrong VAT

Wrong Due Amount

Wrong Trial Balance

Wrong Profit & Loss

Wrong Balance Sheet

---

# 17. FUTURE DEVELOPMENT RULES

The following may change:

✓ User Interface

✓ CSS

✓ Blade Files

✓ JavaScript

✓ Performance

✓ Validation Implementation

✓ Service Architecture

✓ Database Optimization

The following MUST NEVER change without Business Approval:

Business Date

Financial Year Behaviour

Posting Rules

Reporting Rules

Company Isolation

Ledger Rules

Stock Rules

Financial Validation Rules

Business Date Supremacy Rule (Section 19)

---

# 18. GOLDEN RULE

Business Date is the only official Financial Date.

System timestamps are Audit information only.

Financial Year belongs to the Company.

Business Date always determines:

• Posting

• Reports

• Ledger

• Stock

• VAT

• Balances

If source code conflicts with this document,

this document represents the approved business rules.

Any implementation changes must first receive Business Approval.

Both Documentation and Source Code must always remain synchronized.

See Section 19 — Business Date Supremacy Rule for the complete constitutional definition.

---

# 19. BUSINESS DATE SUPREMACY RULE

This section is part of the DG ERP Constitution.

It consolidates and permanently freezes the supremacy of Business Date across every module, report, and future development.

Existing rules in this document remain in force.

Where any section conflicts with this rule,

Section 19 represents the approved business standard.

---

## 19.1 Financial Truth

Business Date is the Financial Truth.

Business Date is the ONLY valid date used by DG ERP for all accounting and business operations.

---

## 19.2 Business Date Scope

Business Date includes (but is not limited to):

• Invoice Date

• Payment Date

• Purchase Date

• Purchase Payment Date

• Sales Return Date

• Purchase Return Date

• Refund Date

• Expense Date

• Income Date

• Journal Date

• Loan Date

• Stock Movement Date

• Opening Balance Date

• Any other accounting transaction date

---

## 19.3 Business Date Usage

The following MUST always use Business Date only:

• Financial Year Validation

• Customer Ledger

• Supplier Ledger

• Account Ledger

• Trial Balance

• Profit & Loss

• Balance Sheet

• VAT Reports

• Sales Reports

• Purchase Reports

• Dashboard Statistics

• Stock Reports

• Aging Reports

• Due Reports

• Bank Reconciliation

• Search

• Filter

• Sorting

• Export

• Print

Any future module must follow the same rule.

---

## 19.4 System Timestamp Exclusion

created_at and updated_at are NOT Business Dates.

They exist ONLY for:

• Audit

• Debugging

• Record History

• Created By

• Updated By

They must NEVER be used for:

• Financial Reports

• Search

• Filters

• Accounting

• Ledger

• Financial Year

• Dashboard

• Stock

• Business Calculations

---

## 19.5 Offline Business Rule

DG ERP must always support delayed data entry.

Example:

A transaction occurs today.

The operator enters it tomorrow.

The Business Date remains today's actual transaction date.

created_at records tomorrow's entry time.

Financial reports must always use Business Date.

---

## 19.6 Business Date Editing Rule

Business Date editing is ALLOWED.

However,

when a Business Date is edited,

every related financial record must remain synchronized.

No linked record may retain an old Business Date.

Examples include:

• Invoice

• Customer Ledger

• Supplier Ledger

• Account Ledger

• Stock Movement

• Payment

• Return

• Refund

• Any linked business document

Amounts, balances, stock quantities, and payment calculations must NOT change when only the Business Date is edited.

Only the business-date fields of linked records may be updated.

---

## 19.7 Future Modules

Every future DG ERP module MUST follow this Business Date Supremacy Rule without exception.

No module may introduce an alternate date source for financial behaviour.

No module may use created_at or updated_at for business calculations, reporting, or filtering.

---

Only the Active Financial Year accepts transactions.

Business Date is the only valid Financial Date.

System timestamps are Audit information only.

Cancelled transactions never affect Financial Calculations.

Business Date always controls

Reports

Ledger

Stock

VAT

Dashboard

Balances

Financial Statements

END OF DOCUMENT