# DG ERP
# 03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD_v1.1.md

Version : 1.1

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



# 4. SYSTEM TIMESTAMP RULE

The following fields are system timestamps.

created_at

updated_at

These fields exist ONLY for

✓ Record Creation Time

✓ Record Update Time

✓ Record History

✓ Debugging

✓ Developer Investigation

✓ Audit Reference

These fields are NOT Business Dates.

They must NEVER be used for

✗ Financial Year Validation

✗ Posting

✗ Ledger

✗ Stock

✗ Reports

✗ Dashboard

✗ Balance

✗ VAT

✗ Financial Calculations
deleted_at

deleted_by
Financial documents normally do not use deleted_at or deleted_by.

Financial documents are cancelled, not deleted.

These fields may exist only for modules where deletion is constitutionally permitted.

---

# 5. FINANCIAL YEAR RULE
Financial Year Name is only a label.

Validation always depends on

Start Date

End Date

Never validate using the Financial Year name.

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
Business Date must never be empty.

Business Date must always be a valid date.

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
✓ Business Date is not null

✓ Business Date is a valid date

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
Cancelled transactions remain available for

Search

Viewing

Printing

Audit

History

only.

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

Audit fields are Record Metadata.

They record

Creation

Modification

History

Investigation

Developer Debugging

These fields never participate in financial behaviour.

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
Business Date represents
the actual date on which the business event occurred.

It is independent of

Record Creation Time

Record Update Time

Server Time

System Time

User Login Time.

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
System timestamps record

when the ERP stored the record.

Business Date records

when the business actually occurred.

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
Business Date synchronization
must execute inside a single database transaction.

Partial synchronization is prohibited.

---

## 19.7 Future Modules

Every future DG ERP module MUST follow this Business Date Supremacy Rule without exception.

No module may introduce an alternate date source for financial behaviour.

No module may use created_at or updated_at for business calculations, reporting, or filtering.
Every future module must expose
its own Business Date field.

Financial behaviour must never rely
on created_at or updated_at.

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
Default Financial Filters

Every financial module should use the following standard filters where applicable:

- Financial Year
- Date From
- Date To
- Status
- Customer (or the relevant master such as Supplier, Account, Employee, etc.)

Default Values

Financial Year = Active Financial Year

Status = Active

Customer = All

Date From and Date To = User selected.

Financial Year Filter

The Financial Year filter shall provide:

- Active Financial Year
- All Financial Years

When Active Financial Year is selected:

- Only records belonging to the Active Financial Year shall be displayed.

When All Financial Years is selected:

- Records from all Financial Years may be displayed.
- Date From and Date To shall determine the reporting period across Financial Years.

Status Filter

Status shall provide:

- Active
- Cancelled
- All

Default Status = Active.

Filtering shall always use the module's Business Date.

END OF DOCUMENT

---

# BUSINESS OWNER AMENDMENT — OFFICIAL ACCOUNTING REPORT FINANCIAL YEAR SCOPE

Status: FINAL AND FROZEN

The generic “All Financial Years” reporting option does not apply to:

- General Ledger
- Trial Balance
- Profit & Loss
- Balance Sheet

Each of these four official Accounting Core reports shall operate on exactly one selected Financial Year belonging to the selected company. Cross-Financial-Year mixing is prohibited.

Date From and Date To use Accounting Entry Business Date and shall remain within that selected Financial Year. This amendment controls wherever the generic Default Financial Filters wording could otherwise permit “All Financial Years” for these four reports.

---

# BUSINESS OWNER AMENDMENT — COUNTRY MASTER AND NEPAL AD/BS DATE EXTENSION

Version: 1.1 Amendment

Status: FINAL AND FROZEN

This amendment extends the existing Financial Year and Business Date Standard without deleting, replacing, weakening, or changing any previously frozen Business Date, Financial Year, posting, reporting, audit, cancellation, or synchronization rule.

Where this amendment is silent, all existing rules remain fully in force.

## A. BUSINESS DATE AUTHORITY REMAINS UNCHANGED

Business Date remains the ONLY official Financial Date in DG ERP.

The existing module-specific Business Date fields remain authoritative, including but not limited to:

- invoice_date
- payment_date
- purchase_date
- return_date
- refund_date
- expense_date
- income_date
- journal_date
- loan_date
- stock_date
- opening balance business date
- any future module-specific Business Date

The Nepali/BS date introduced by this amendment is NOT a second independent Business Date.

It is a system-generated calendar representation of the authoritative Business Date for companies whose Country is Nepal.

created_at, updated_at, created_by, updated_by, deleted_at, and deleted_by remain Audit/System Metadata only and MUST NOT become Business Dates.

## B. ENGLISH / AD DATE PRIMARY RULE

DG ERP shall use the existing Business Date as the primary authoritative date.

For date storage, validation, Financial Year validation, posting, ledger, stock, VAT, reporting, filtering, sorting, searching, export, printing, synchronization, and all financial calculations, the existing Business Date remains the Financial Truth.

For Nepal companies, the corresponding Nepali/BS date shall be derived from that Business Date.

The BS date MUST NOT independently control financial behaviour.

## C. COUNTRY MASTER RULE

DG ERP shall maintain a Country Master as a reusable system master.

A dedicated countries master/table shall be used rather than storing uncontrolled free-text country values in each company.

The Country Master shall support, at minimum, a stable internal identifier and a standard country code suitable for deterministic system rules.

Conceptual minimum:

- id
- name
- iso_code
- is_active

Additional country metadata may be added under the applicable DG ERP development standards when business-approved.

Country identity MUST NOT depend only on a display name such as "Nepal" because display names may change or be localized.

A stable country identifier/code shall be used for system behaviour.

## D. COMPANY COUNTRY RELATION RULE

Every Company shall belong to one Country through the approved Company-to-Country relationship.

Conceptually:

companies.country_id -> countries.id

The Company Country is the authoritative source for country-dependent calendar behaviour.

Financial transaction screens MUST NOT provide an independent Country selector for changing the calendar behaviour of an individual transaction.

A transaction inherits country/calendar behaviour from its owning Company.

Company isolation remains mandatory.

## E. NEPAL COUNTRY CALENDAR RULE

When the owning Company's Country is Nepal, identified through the approved stable Country Master identifier/code:

- AD/English Business Date remains primary.
- Nepali/BS date functionality is enabled.
- BS date is automatically derived from the authoritative Business Date.
- BS date may be displayed wherever the approved UI/report/print requirement requires it.

Conceptual rule:

Country = NP
-> AD Business Date = Authoritative
-> BS Date = Automatically Derived

The implementation MUST NOT rely on a user typing "Nepal" into a transaction.

## F. NON-NEPAL COUNTRY RULE

When the owning Company's Country is not Nepal:

- The existing AD/English Business Date continues normally.
- Nepal-specific BS date input/display controls shall be hidden or treated as not applicable.
- Nepal-specific BS conversion MUST NOT affect financial behaviour.
- No Nepali calendar requirement shall be forced on that Company solely because the ERP supports Nepal.

Conceptual rule:

Country != NP
-> Business Date continues normally
-> Nepal BS Date = Hidden / Not Applicable

This amendment does not define the tax, fiscal, calendar, or legal rules of other countries.

## G. BS AUTO-CONVERSION RULE

For a Nepal Company, the user shall select or enter the module's normal authoritative Business Date according to existing DG ERP rules.

The ERP shall automatically calculate the corresponding Nepali/BS date.

Example:

invoice_date = authoritative Business Date
invoice_date_bs = system-derived Nepali representation

The same pattern applies to other financial modules using their own Business Date.

The BS value MUST be derived from the relevant module Business Date and MUST NOT be derived from:

- created_at
- updated_at
- server date
- login date
- current date when different from Business Date

## H. NO MANUAL INDEPENDENT BS DATE RULE

The Nepali/BS date MUST NOT be an independent manually controlled financial date.

A user MUST NOT be able to create a mismatch by setting an AD Business Date and an unrelated BS date.

Where a BS field is shown for a Nepal Company, it shall be system-generated/read-only unless a future Business Owner amendment explicitly changes this rule.

The authoritative Business Date remains the only date the user changes under the existing Business Date editing rules.

## I. BUSINESS DATE EDIT SYNCHRONIZATION WITH BS

The existing Section 19.6 Business Date Editing Rule remains fully in force.

Business Date editing remains ALLOWED where the existing standard permits it.

When an authoritative Business Date is edited for a Nepal Company:

1. Existing linked financial Business Dates shall synchronize according to Section 19.6.
2. The corresponding BS representation shall be recalculated from the updated authoritative Business Date.
3. No linked record may retain an obsolete BS representation for the synchronized Business Date.
4. Synchronization shall follow the existing single-database-transaction rule where financial linked records are being synchronized.

Changing only the Business Date/calendar representation MUST NOT independently change:

- Amount
- Balance
- Quantity
- VAT amount
- Payment amount
- Stock quantity
- Other financial values

unless another existing business rule independently requires such a change.

## J. FINANCIAL YEAR RULE REMAINS INDEPENDENT

This amendment does NOT replace the existing Financial Year model.

Every Company continues to own and configure its Financial Years using Start Date and End Date.

Financial Year validation continues to use the authoritative Business Date.

The existence of a Nepali/BS display does NOT authorize developers to validate Financial Year using a Financial Year name, display label, created_at, updated_at, or a separate manually entered BS date.

The configured Financial Year continues to win.

## K. INVOICE DATE RULE

For Sales Invoice, invoice_date is the Invoice Business Date and therefore the Financial Truth for that invoice.

For a Nepal Company:

- invoice_date remains authoritative.
- the corresponding Nepali/BS invoice date is generated from invoice_date.
- ledger/report/VAT/stock/Financial Year behaviour continues to use the authoritative Invoice Business Date according to the existing standard.

The BS representation MUST NOT replace invoice_date as the posting source.

## L. OTHER MODULE BUSINESS DATE RULE

The same country/calendar principle applies to every financial module without changing its existing Business Date identity.

Examples:

Sales Payment -> payment_date

Purchase -> purchase_date

Sales Return -> return_date

Refund -> refund_date

Expense -> expense_date

Income -> income_date

Journal -> journal_date

Loan -> loan_date

Stock Movement -> stock_date

Each existing module Business Date remains authoritative.

For Nepal Companies only, the corresponding BS representation is automatically derived from that module's Business Date.

## M. UI DISPLAY RULE

For Nepal Companies, approved transaction forms may display both calendars while maintaining one authoritative Business Date.

Conceptual display:

Date (AD): [Business Date]

मिति (BS): [Auto-generated BS Date]

The BS field shall not create a second independent transaction date.

For non-Nepal Companies, Nepal-specific BS controls shall not be displayed.

UI visibility MUST NOT be the only enforcement mechanism. Country/date behaviour must also be enforced by the appropriate backend/domain validation architecture.

## N. PRINT AND REPORT RULE

For Nepal Companies, invoices, ledgers, statements, reports, and other approved outputs may display both AD and BS representations where required by the approved business/legal format.

However:

- filtering uses Business Date;
- sorting uses Business Date;
- Financial Year validation uses Business Date;
- posting uses Business Date;
- ledger uses Business Date;
- VAT behaviour uses Business Date;
- balances use Business Date;
- accounting calculations use Business Date.

The BS value is a corresponding calendar representation and MUST NOT silently create a separate reporting timeline.

For non-Nepal Companies, Nepal-specific BS display shall be omitted unless explicitly required by a future approved rule.

## O. DATE CONVERSION CENTRALIZATION RULE

AD-to-BS conversion logic MUST be centralized and reusable.

Individual Controllers, Blade files, reports, or modules MUST NOT each implement independent Nepali date conversion algorithms.

There shall be one approved date/calendar conversion mechanism or service used consistently across DG ERP.

This prevents different modules from producing different BS dates for the same authoritative Business Date.

Frontend conversion may be used for immediate display convenience, but authoritative validation/conversion must not depend solely on client-side code.

## P. DATE CONSISTENCY RULE

For a Nepal Company, the same authoritative Business Date MUST always resolve to the same approved BS representation throughout the ERP.

The following MUST NOT disagree for the same transaction:

- Transaction Form
- View Page
- Invoice Print
- Ledger
- VAT Report
- Financial Report
- Export
- PDF
- Audit/Investigation view where the Business Date is represented

Any AD/BS mismatch is a date-integrity defect and must be corrected before production release.

## Q. COUNTRY CHANGE CONTROL RULE

Because Company Country controls calendar behaviour, Company Country is a business-critical master setting.

Country changes must be permission-controlled and validated under the applicable Company/Master Business Standard.

Changing a Company's Country MUST NOT silently rewrite historical Business Dates or financial postings.

This amendment does not authorize automatic historical conversion, migration, tax change, or Financial Year change when Company Country is changed.

Any such historical transformation requires separate Business Approval and migration rules.

## R. COUNTRY MASTER DATA INTEGRITY

Country records referenced by Companies MUST maintain referential integrity.

A Country that is already referenced by Company/business records MUST NOT be removed in a way that breaks historical Company identity.

Activation/deactivation behaviour may be implemented according to the applicable Master Business Standard, but historical references must remain valid.

## S. FUTURE COUNTRY EXTENSION RULE

The Country Master is the foundation for future country-specific behaviour.

Future approved standards may attach country-specific:

- Currency
- Timezone
- Tax Profile
- Fiscal rules
- Invoice rules
- Calendar rules
- Electronic billing integration

However, no future country feature may override the Business Date Supremacy Rule without explicit Business Owner approval.

This amendment currently defines only the Nepal AD/BS calendar behaviour and Country-based visibility/activation rule.

## T. PROHIBITED IMPLEMENTATIONS

DO NOT:

- Replace Business Date with BS Date.
- Treat BS Date as a second independent Financial Truth.
- Use created_at or updated_at to generate the transaction's legal/business date.
- Allow manual AD/BS mismatch.
- Use a transaction-level Country selector to override Company Country.
- Hard-code Nepal behaviour globally for every Company.
- Apply Nepal BS fields to non-Nepal Companies by default.
- Implement different AD/BS conversion algorithms in different modules.
- Use BS display value to bypass Active Financial Year validation.
- Rewrite historical financial Business Dates merely because Company Country changes.
- Modify existing Business Date, Financial Year, cancellation, posting, reporting, or audit rules through this amendment.

## U. REQUIRED VALIDATION

Before saving or updating an applicable financial transaction, existing validation remains mandatory, including:

- Company exists.
- Company ownership is valid.
- Financial Year exists.
- Financial Year belongs to Company.
- Financial Year is Active.
- Business Date is valid.
- Business Date belongs to Active Financial Year.

Additionally, for Nepal Company calendar representation:

- Company Country must resolve through the approved Country Master.
- BS representation must correspond to the authoritative Business Date.
- User-supplied independent BS values must not override the generated value.

Failure of Nepal date conversion must not silently produce a different financial Business Date.

## V. TESTING REQUIREMENTS

At minimum, implementation must test:

1. Nepal Company shows/enables BS representation.
2. Non-Nepal Company does not show Nepal-specific BS controls.
3. AD Business Date correctly generates BS representation.
4. Editing Business Date recalculates BS representation.
5. Linked Business Date synchronization remains compliant with Section 19.6.
6. created_at/updated_at never affect BS conversion of the transaction Business Date.
7. Back-date entry converts the actual Business Date, not current system date.
8. Financial Year validation continues to use Business Date.
9. Reports filter using Business Date.
10. Same transaction displays consistent AD/BS values across form, view, print, report, and export where applicable.
11. Company A's Country configuration cannot affect Company B.
12. Country changes do not silently rewrite historical financial records.
13. Direct request manipulation cannot submit an unrelated manual BS date as authoritative.
14. Non-Nepal transactions remain unaffected by Nepal calendar functionality.

## W. DEVELOPER / AI RULE

Before implementing this amendment, every developer or AI coding agent MUST read:

- this complete Financial Year and Date Standard;
- Section 19 — Business Date Supremacy Rule;
- the applicable Company/Master Business Standard;
- the applicable module standard;
- the DG ERP Master Development Standard.

Implementation must begin with an existing-code and existing-database audit.

Do NOT create a duplicate Country table, duplicate Business Date architecture, or duplicate date-conversion mechanism without first verifying the current project structure.

Existing frozen rules must not be changed merely to make implementation easier.

## X. FROZEN DECISIONS OF THIS AMENDMENT

The following are BUSINESS APPROVED and FROZEN:

- Existing Business Date remains Financial Truth.
- English/AD Business Date remains primary/authoritative.
- created_at and updated_at remain Audit/System timestamps only.
- Country is controlled from the Company relationship, not per transaction.
- Country Master uses a stable ID/code relationship.
- Nepal Company enables automatic BS representation.
- Non-Nepal Company hides/does not apply Nepal-specific BS date functionality.
- BS date is derived from Business Date.
- BS date is not an independent Business Date.
- Manual AD/BS mismatch is prohibited.
- Business Date editing remains governed by existing Section 19.6.
- When Business Date changes for Nepal Company, its BS representation must synchronize.
- Financial Year behaviour remains governed by the existing standard.
- Country/calendar extension must not alter existing financial calculations.
- Date conversion logic must be centralized.

Any modification to these decisions requires Business Owner approval.

## Y. FINAL CALENDAR FLOW

Approved conceptual flow:

Company
-> country_id
-> Country Master
-> Stable Country Code

If Country = Nepal (NP):

Module Business Date (AD / Authoritative)
-> Financial Year Validation
-> Financial Posting / Ledger / Reports / VAT / Stock
-> Automatic BS Conversion
-> BS Display / Approved Print Representation

If Country != Nepal:

Module Business Date (Authoritative)
-> Existing Financial Year / Posting / Reporting behaviour
-> Nepal BS functionality not applied

## Z. FINAL GOLDEN RULE

Business Date remains the Financial Truth.

For Nepal Companies, Nepali/BS Date is a system-generated representation of that Financial Truth.

For all other Companies, Nepal-specific BS Date functionality remains inactive unless a future Business Owner-approved standard explicitly provides otherwise.

Nothing in this amendment changes the existing frozen Business Date Supremacy, Financial Year, posting, reporting, cancellation, company-isolation, or audit rules.

---

END OF BUSINESS OWNER AMENDMENT — COUNTRY MASTER AND NEPAL AD/BS DATE EXTENSION
