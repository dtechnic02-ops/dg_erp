# DG ERP ACCOUNTING REPORT STANDARD

## Document Information

| Item | Value |
|------|-------|
| Document Name | 15_DG_ERP_ACCOUNTING_REPORT_STANDARD.md |
| Version | 2.0 FINAL |
| Status | FINAL |
| Document Type | Business Constitution |
| Module | Accounting |
| Authority | Business Owner |
| Scope | Entire DG ERP Accounting System |

---

# CHAPTER 1
# MISSION

DG ERP Accounting is the official financial engine of the ERP.

Its purpose is to provide:

- Accurate accounting
- Complete audit trail
- Double-entry bookkeeping
- Financial statement generation
- Multi-company accounting
- Financial year isolation
- Secure posting architecture
- Future-proof accounting framework

This document is the highest business authority for all accounting development.

Any implementation that conflicts with this document is considered invalid unless approved by the Business Owner.

---

# CHAPTER 2
# DOCUMENT HIERARCHY

Accounting follows the following document priority.

Priority 1

Financial Year Standard

Priority 2

Master Business Standard

Priority 3

Master Development Standard

Priority 4

Master UI Framework Standard

Priority 5

Accounting Report Standard (This Document)

Priority 6

Module Standards

No lower document may override a higher-priority document.

---

# CHAPTER 3
# ACCOUNTING PHILOSOPHY

DG ERP follows true Double Entry Accounting.

Every financial event creates accounting entries.

Accounting is never calculated directly from business modules.

Business modules generate transactions.

Accounting modules generate financial records.

Accounting reports are generated only from official accounting posting records.

Accounting must always remain independent from Sales, Purchase, Expense, Income, Payroll, Loan and other operational modules.

---

# CHAPTER 4
# OFFICIAL ACCOUNTING ARCHITECTURE

Official Architecture

Business Module

↓

Accounting Posting Service

↓

Accounting Entry

↓

Accounting Entry Line

↓

Chart of Accounts

↓

General Ledger

↓

Trial Balance

↓

Profit & Loss

↓

Balance Sheet

Business modules never create financial reports directly.

Every financial report originates from posted accounting entries.

---

# CHAPTER 5
# CHART OF ACCOUNTS CONSTITUTION

Official Table

chart_accounts

This is the only official Chart of Accounts table.

Creating another Chart of Accounts table is strictly prohibited.

Chart Accounts define financial classification only.

Chart Accounts do NOT store payment instrument information.

Each Chart Account belongs to exactly one company.

Each Chart Account has one permanent Account Class.

Each Chart Account has one Normal Balance.

Chart Accounts support parent-child hierarchy.

Chart Accounts support:

- Asset
- Liability
- Equity
- Income
- Expense

Each Chart Account must have:

- Company
- Code
- Name
- Account Class
- Category
- Normal Balance
- Status

System Accounts cannot be deleted.

Control Accounts cannot be deleted.

Inactive Chart Accounts cannot receive new postings.

---

# CHAPTER 6
# OPERATIONAL ACCOUNT CONSTITUTION

Official Table

accounts

Operational Accounts represent payment instruments.

Examples

- Cash
- Bank
- ATM
- Wallet
- Mobile Money
- Petty Cash

Operational Accounts are NOT the Chart of Accounts.

Operational Accounts may optionally be referenced from Accounting Entry Lines.

Operational Accounts are used by:

- Sales
- Purchase
- Expense
- Income
- Loan
- Payment
- Receipt

Operational Accounts do not determine financial classification.

Financial classification always comes from Chart Accounts.

---

# CHAPTER 7
# CONTROL ACCOUNT CONSTITUTION

Control Accounts are mandatory system accounts.

Examples include:

Customer Receivable

Supplier Payable

Sales Revenue

Purchase Clearing

Expense

Cash

Bank

Tax Payable

Tax Receivable

Owner Capital

Retained Earnings

Control Accounts:

- Cannot be deleted.
- Cannot change Account Class.
- Cannot violate posting rules.
- May be protected from manual posting according to business configuration.

System Code uniquely identifies every Control Account.

Business modules must use System Code instead of hard-coded IDs.

---

# CHAPTER 8
# SUB-LEDGER CONSTITUTION

Sub-ledgers provide detailed balances for business parties.

Supported Sub-ledgers include:

- Customer
- Supplier
- Employee
- Loan
- Project
- Branch
- Vendor
- Other approved entities

Sub-ledgers never replace the Chart of Accounts.

Financial reports are produced from Chart Accounts.

Sub-ledgers provide supporting detail only.

Each Accounting Entry Line may optionally reference one Sub-ledger.

Sub-ledger balances must reconcile with their related Control Account.

A difference between a Control Account and its Sub-ledgers is considered a system error.

---

END OF PART 1

---

# CHAPTER 9
# ACCOUNTING ENTRY CONSTITUTION

Official Table

accounting_entries

Accounting Entry is the official accounting posting header.

Every Accounting Entry represents one complete financial transaction.

Each Accounting Entry belongs to exactly one Company.

Each Accounting Entry shall contain:

- Company
- Entry Number
- Entry Date
- Source Module
- Source Type
- Source ID
- Source Event
- Source Key
- Description
- Status
- Posted By
- Posted At

Status values:

- Draft
- Posted
- Reversed

Only Posted Entries affect financial reports.

Draft Entries must never appear in any accounting report.

---

# CHAPTER 10
# ACCOUNTING ENTRY LINE CONSTITUTION

Official Table

accounting_entry_lines

Accounting Entry Lines contain Debit and Credit details.

Every Accounting Entry must contain at least two lines.

Every line must reference:

- Accounting Entry
- Chart Account

Optional references:

- Operational Account
- Sub-ledger
- Description

Rules:

A line may contain Debit only.

OR

A line may contain Credit only.

Both Debit and Credit greater than zero on the same line are prohibited.

Zero amount lines are prohibited.

---

# CHAPTER 11
# POSTING ENGINE CONSTITUTION

The Accounting Posting Service is the only approved posting engine.

No module may insert accounting records directly into accounting tables.

Every module must use the official Posting Service.

The Posting Service is responsible for:

- Validation
- Balancing
- Duplicate prevention
- Audit creation
- Reversal support
- Transaction integrity

---

# CHAPTER 12
# POSTING VALIDATION RULES

Before posting, the system must validate:

- Company
- Posting Date
- Financial Year
- Account existence
- Chart Account status
- Control Account rules
- Debit Total
- Credit Total

Validation Rules:

Total Debit must equal Total Credit.

Posting with unequal totals is prohibited.

Posting with missing Chart Accounts is prohibited.

Posting to inactive Chart Accounts is prohibited.

---

# CHAPTER 13
# SOURCE MODULE INTEGRATION

The following modules may generate accounting postings:

- Sales
- Sales Return
- Purchase
- Purchase Return
- Expense
- Income
- Loan
- Payroll
- Inventory Adjustment
- Journal

Business modules never calculate financial reports.

They only generate accounting entries.

---

# CHAPTER 14
# JOURNAL CONSTITUTION

Journal is the central manual accounting module.

Journal supports:

- Manual Debit
- Manual Credit
- Adjustment Entry
- Correction Entry
- Opening Entry
- Closing Entry

Journal Rules:

Debit Total must equal Credit Total.

Journal cannot be posted when totals differ.

Posted Journal cannot be edited directly.

Corrections must use Reversal Entries.

---

# CHAPTER 15
# GENERAL LEDGER CONSTITUTION

General Ledger is generated only from Posted Accounting Entries.

Ledger displays:

- Opening Balance
- Date
- Entry Number
- Reference
- Description
- Debit
- Credit
- Running Balance
- Closing Balance

Ledger supports filters:

- Company
- Financial Year
- Date Range
- Chart Account

---

# CHAPTER 16
# TRIAL BALANCE CONSTITUTION

Trial Balance is generated only from Posted Accounting Entries.

Columns:

- Account
- Opening Debit
- Opening Credit
- Period Debit
- Period Credit
- Closing Debit
- Closing Credit

Validation:

Total Debit must equal Total Credit.

Otherwise the Trial Balance is invalid.

---

# CHAPTER 17
# PROFIT & LOSS CONSTITUTION

Profit & Loss uses only:

- Income Accounts
- Expense Accounts

Formula:

Income

minus

Expense

equals

Net Profit

or

Net Loss

No Asset, Liability or Equity account shall appear in Profit & Loss.

---

# CHAPTER 18
# BALANCE SHEET CONSTITUTION

Balance Sheet uses only:

- Asset
- Liability
- Equity

Formula:

Assets

=

Liabilities

+

Equity

Income and Expense accounts shall not appear in Balance Sheet.

---

END OF PART 2


---

# CHAPTER 19
# CASH FLOW CONSTITUTION

Cash Flow Statement shall be generated only from posted accounting records.

Cash movements shall be classified into:

- Operating Activities
- Investing Activities
- Financing Activities

Only Cash and Cash Equivalent accounts shall affect the Cash Flow Statement.

The Cash Flow Statement must reconcile with Cash and Bank balances.

Business modules shall never generate the Cash Flow Statement directly.

---

# CHAPTER 20
# FINANCIAL YEAR CONSTITUTION

Every accounting transaction belongs to one Financial Year.

Financial reports must always be filtered by:

- Company
- Financial Year

Financial Years shall never be mixed.

Closed Financial Years cannot receive new postings.

Back-dated postings into a closed Financial Year are prohibited unless specifically authorized.

---

# CHAPTER 21
# COMPANY ISOLATION CONSTITUTION

Every accounting record belongs to exactly one Company.

Every accounting query must filter by Company.

Cross-company accounting access is prohibited.

No financial report shall include another company's accounting records.

Company isolation is mandatory throughout the accounting engine.

---

# CHAPTER 22
# OPENING BALANCE CONSTITUTION

Opening Balance initializes the accounting system for a Financial Year.

Opening Balance shall be posted using the official Posting Service.

Opening Balance becomes the Opening Balance of:

- General Ledger
- Trial Balance
- Balance Sheet

Opening Balance shall never be edited directly after posting.

Corrections must use approved adjustment procedures.

---

# CHAPTER 23
# YEAR-END CLOSING CONSTITUTION

Year-End Closing shall be performed only once per Financial Year.

Closing process includes:

- Closing Income Accounts
- Closing Expense Accounts
- Transferring Net Profit or Net Loss
- Updating Retained Earnings

A completed Year-End Closing cannot be repeated without authorized reversal.

---

# CHAPTER 24
# REVERSAL CONSTITUTION

Posted accounting entries shall never be edited directly.

Corrections shall be made using Reversal Entries.

A Reversal Entry shall:

- Reference the Original Entry
- Reverse Debit and Credit
- Preserve complete audit history

The Original Entry shall remain permanently stored.

---

# CHAPTER 25
# CANCEL CONSTITUTION

Cancel does not physically delete accounting records.

Cancel shall:

- Preserve history
- Preserve audit trail
- Preserve references

Cancelled records must remain traceable.

---

# CHAPTER 26
# DELETE CONSTITUTION

Physical deletion of posted accounting records is prohibited.

Deletion may be allowed only for:

- Draft records
- Temporary records
- Authorized maintenance operations

Business transactions must never lose historical accounting information.

---

# CHAPTER 27
# AUDIT TRAIL CONSTITUTION

Every accounting action must be traceable.

Audit information shall include:

- Created By
- Updated By
- Posted By
- Created Date
- Updated Date
- Posted Date

Every financial transaction shall remain permanently auditable.

Audit history shall never be removed.

---

# CHAPTER 28
# SECURITY CONSTITUTION

Accounting permissions shall follow Role Permission standards.

Examples:

- View
- Create
- Edit
- Post
- Reverse
- Approve
- Print
- Export

Only authorized users may perform accounting operations.

Unauthorized accounting access is strictly prohibited.

---

END OF PART 3

---

# CHAPTER 29
# DATABASE CONSTITUTION

The accounting database structure shall remain normalized.

Official accounting tables:

- chart_accounts
- accounting_entries
- accounting_entry_lines

Supporting tables may exist for:

- Operational Accounts
- Financial Years
- Posting Profiles
- Tax Configuration
- Currency
- Exchange Rates

Business modules shall never duplicate accounting balances.

Accounting reports shall always read from the official accounting structure.

Primary keys shall never be reused.

Foreign key integrity shall be maintained at all times.

---

# CHAPTER 30
# PERFORMANCE CONSTITUTION

Accounting architecture shall prioritize:

- Accuracy
- Consistency
- Auditability
- Performance

Reports shall use indexed fields.

Unnecessary recalculation is prohibited.

Duplicate accounting balances are prohibited.

Heavy financial reports should use optimized queries.

Performance optimization must never compromise accounting accuracy.

---

# CHAPTER 31
# DEVELOPER CONSTITUTION

Developers shall not bypass the official Posting Service.

Developers shall not insert accounting records directly.

Developers shall not modify posted accounting data manually.

Every accounting change shall preserve:

- Audit Trail
- Company Isolation
- Financial Year Isolation
- Double Entry Integrity

Business approval is mandatory before changing accounting architecture.

---

# CHAPTER 32
# BUSINESS RESTRICTIONS

Business users shall not:

- Modify system Chart Accounts without authorization.
- Delete Control Accounts.
- Change Account Classes of Control Accounts.
- Edit posted accounting history.
- Break debit and credit equality.

Business configuration shall always preserve accounting integrity.

---

# CHAPTER 33
# FUTURE EXPANSION CONSTITUTION

Future modules shall follow this accounting architecture.

Examples include:

- Manufacturing
- Fixed Assets
- Depreciation
- Payroll
- POS
- CRM
- Project Accounting
- Budget
- Cost Center
- Multi Branch
- Multi Currency
- Consolidation

No future module may introduce a second accounting engine.

---

# CHAPTER 34
# REPORT GENERATION ORDER

Official report generation order:

Business Transaction

↓

Accounting Posting Service

↓

Accounting Entry

↓

Accounting Entry Line

↓

General Ledger

↓

Trial Balance

↓

Profit & Loss

↓

Balance Sheet

↓

Cash Flow Statement

Reports shall never bypass this sequence.

---

# CHAPTER 35
# DO NOT RULES

The following actions are permanently prohibited:

- Creating multiple Chart of Accounts tables.
- Generating reports directly from operational modules.
- Editing posted accounting entries directly.
- Deleting posted accounting records.
- Bypassing the Posting Service.
- Posting with unequal Debit and Credit.
- Mixing multiple companies in the same accounting query.
- Mixing Financial Years in the same accounting report.
- Creating duplicate accounting balances.
- Breaking audit history.

Violation of these rules is considered a violation of the DG ERP Accounting Constitution.

---

# CHAPTER 36
# FINAL BUSINESS CONSTITUTION

DG ERP officially adopts:

- Single Chart of Accounts (`chart_accounts`)
- Single Double-Entry Posting Engine
- Accounting Entries (`accounting_entries`)
- Accounting Entry Lines (`accounting_entry_lines`)
- Operational Accounts (`accounts`)
- Control Accounts
- Sub-ledgers
- Multi-Company Isolation
- Financial Year Isolation
- Complete Audit Trail
- General Ledger
- Trial Balance
- Profit & Loss
- Balance Sheet
- Cash Flow Statement

All accounting modules, current and future, shall follow this constitution.

Any accounting implementation that conflicts with this document shall be considered invalid until approved by the Business Owner.

---
---

# CHAPTER 37
# MULTI-CURRENCY CONSTITUTION

DG ERP shall support both Single Currency and Multi-Currency accounting.

Every Company shall have one Base Currency.

Examples:

- AED
- NPR
- USD
- INR
- EUR

All official financial statements shall be generated in the Company's Base Currency.

Transactions may be entered in Foreign Currency.

Each foreign currency transaction shall store:

- Transaction Currency
- Exchange Rate
- Base Currency Amount
- Exchange Rate Date

The original transaction currency shall never be modified after posting.

Exchange Gain and Exchange Loss shall be calculated using approved accounting rules.

Historical Exchange Rates shall remain permanently stored for audit purposes.

---

# CHAPTER 38
# TAX ACCOUNTING CONSTITUTION

DG ERP shall support configurable tax systems.

Examples:

- VAT
- GST
- Sales Tax
- Withholding Tax

Tax shall never be hard-coded.

Every Tax shall have:

- Tax Name
- Tax Rate
- Tax Type
- Effective Date
- Status

Tax posting shall use designated Control Accounts.

Input Tax and Output Tax shall always be recorded separately.

Tax calculations shall remain traceable from the originating business transaction to the final accounting entry.

Manual modification of posted tax values is prohibited.

---


END OF PART 5

---

# CHAPTER 39
# COST CENTER CONSTITUTION

DG ERP shall support Cost Center Accounting.

A Cost Center represents a business unit used for internal financial analysis.

Examples include:

- Branch
- Department
- Project
- Division
- Business Unit

Cost Centers are optional unless required by company policy.

Each Accounting Entry Line may reference one Cost Center.

Cost Centers shall not replace the Chart of Accounts.

Cost Center balances shall always reconcile with their related Chart Accounts.

Financial reports may be filtered by:

- Company
- Financial Year
- Cost Center
- Department
- Branch
- Project

Cost Center reporting shall never alter the official General Ledger.

The General Ledger remains the official financial record.

Cost Center reports provide analytical information only.

Historical Cost Center assignments shall remain unchanged after posting.

---

# CHAPTER 40
# BUDGET CONSTITUTION

DG ERP shall support Budget Management.

Budgets are planning tools and shall not replace actual accounting records.

Budget Types may include:

- Annual Budget
- Quarterly Budget
- Monthly Budget
- Department Budget
- Project Budget

Each Budget shall contain:

- Financial Year
- Company
- Budget Period
- Chart Account
- Budget Amount
- Status

Budget Status may include:

- Draft
- Submitted
- Approved
- Closed

Only Approved Budgets shall be used for comparison reports.

Budget reports shall include:

- Budget Amount
- Actual Amount
- Variance Amount
- Variance Percentage

Budget transactions shall never create Accounting Entries.

Accounting Entries shall always represent actual financial events only.

Budget revisions shall preserve complete revision history.

Budget approval shall follow the official Role Permission Standard.

---

END OF PART 6
---

# CHAPTER 41
# POSTING PROFILE CONSTITUTION

DG ERP shall use Posting Profiles to standardize accounting automation.

A Posting Profile defines how a business transaction is converted into Accounting Entries.

Business modules shall never hard-code Chart Account IDs.

Every module shall use an approved Posting Profile.

Standard Posting Profiles include:

- Sales
- Sales Return
- Purchase
- Purchase Return
- Expense
- Income
- Loan
- Payment
- Receipt
- Journal
- Payroll
- Inventory Adjustment

Each Posting Profile shall define:

- Source Module
- Transaction Type
- Debit Chart Account
- Credit Chart Account
- Tax Rules
- Control Account Rules
- Status

Posting Profiles may be configured by authorized users.

Inactive Posting Profiles shall not be used for new postings.

Changes to a Posting Profile shall not affect previously posted Accounting Entries.

Every Accounting Entry shall preserve the Posting Profile that generated it for audit purposes.

---

# CHAPTER 42
# ACCOUNT NUMBERING CONSTITUTION

Every Chart Account shall have a unique Account Code within the Company.

Account Codes shall remain permanent after creation.

Changing an Account Code after Accounting Entries exist is prohibited.

The following Account Class ranges are recommended:

1000 – 1999   Assets

2000 – 2999   Liabilities

3000 – 3999   Equity

4000 – 4999   Income

5000 – 5999   Expenses

Companies may extend these ranges while preserving Account Class integrity.

System Accounts shall use reserved System Codes.

Business modules shall always identify Control Accounts using System Codes rather than numeric IDs.

Deleted Account Codes shall never be reused.

Account numbering shall remain consistent across all Financial Years.

The Chart of Accounts hierarchy shall preserve logical financial reporting order.

---

END OF PART 7
---

# CHAPTER 43
# FINANCIAL REPORT CONSTITUTION

DG ERP shall generate all Financial Reports exclusively from Posted Accounting Entries.

Financial Reports shall never read data directly from operational modules.

Official Financial Reports include:

- General Ledger
- Trial Balance
- Profit & Loss Statement
- Balance Sheet
- Cash Flow Statement

Additional Management Reports may include:

- Monthly Financial Summary
- Quarterly Financial Summary
- Yearly Financial Summary
- Comparative Financial Statement
- Department-wise Financial Report
- Branch-wise Financial Report
- Cost Center Report
- Budget vs Actual Report

Every Financial Report shall support filtering by:

- Company
- Financial Year
- Date Range
- Branch (Optional)
- Department (Optional)
- Cost Center (Optional)
- Project (Optional)

Comparative Reports may compare:

- Current Period vs Previous Period
- Current Financial Year vs Previous Financial Year
- Budget vs Actual
- Branch vs Branch
- Department vs Department

Financial Reports shall remain read-only.

Users shall never edit financial data from report screens.

Every printed or exported Financial Report shall display:

- Company Name
- Report Name
- Report Period
- Financial Year
- Generated Date & Time
- Generated By
- Currency

Financial Reports shall always reconcile with the General Ledger.

Any reconciliation difference shall be treated as a system error.

---

# CHAPTER 44
# ERROR HANDLING CONSTITUTION

Accounting integrity shall always have higher priority than transaction completion.

If any Accounting validation fails, the entire posting process shall fail.

Partial posting is strictly prohibited.

The Posting Service shall use Database Transactions to guarantee atomic processing.

If an error occurs during posting:

- No Accounting Entry shall be created.
- No Accounting Entry Line shall be created.
- No partial financial data shall remain.
- The transaction shall be rolled back completely.

Validation errors may include:

- Missing Chart Account
- Inactive Chart Account
- Invalid Posting Profile
- Invalid Financial Year
- Financial Year Closed
- Company Mismatch
- Debit/Credit Imbalance
- Missing Control Account
- Missing Exchange Rate
- Invalid Tax Configuration

System errors shall be recorded in application logs for troubleshooting.

Error messages displayed to users shall be clear, business-friendly, and shall never expose sensitive system information.

Every failed posting attempt may be recorded for audit purposes.

---

END OF PART 8
---

# CHAPTER 45
# SYSTEM LOCK CONSTITUTION

DG ERP shall implement System Locks to protect accounting integrity.

Official Lock Types include:

- Financial Year Lock
- Accounting Period Lock
- Posting Lock
- Approval Lock
- System Maintenance Lock

Financial Year Lock Rules:

- A Closed Financial Year shall not accept new Accounting Entries.
- Posted Accounting Entries shall not be edited after Financial Year Closing.
- Opening a Closed Financial Year requires Business Owner authorization.
- Every reopen action shall be recorded in the Audit Trail.

Accounting Period Lock Rules:

- Companies may lock individual accounting periods.
- Locked periods shall reject all new postings.
- Unlocking a period requires authorized approval.

Posting Lock Rules:

- Only authorized users may post Accounting Entries.
- Users without posting permission may save Draft Entries only.
- Draft Entries shall not affect any Financial Report.

Approval Lock Rules:

- Business modules requiring approval shall not generate Posted Accounting Entries until approval is complete.
- Approval workflow shall follow the official Role Permission Standard.

System Maintenance Lock Rules:

- During maintenance, accounting posting may be temporarily suspended.
- Existing Posted Accounting Entries shall remain accessible in read-only mode.
- No accounting data shall be modified during maintenance without authorization.

All Lock actions shall be permanently recorded in the Audit Trail.

---

# CHAPTER 46
# ENTERPRISE ACCOUNTING PRINCIPLES

The following principles are the permanent foundation of DG ERP Accounting.

Golden Rule 1

Every financial transaction shall follow Double Entry Accounting.

Golden Rule 2

Total Debit shall always equal Total Credit.

Golden Rule 3

Only Posted Accounting Entries shall affect Financial Reports.

Golden Rule 4

Accounting data shall never be generated directly from operational modules.

Golden Rule 5

Every Accounting Entry shall remain permanently auditable.

Golden Rule 6

Posted Accounting Entries shall never be edited directly.

Golden Rule 7

Corrections shall always use approved Reversal or Adjustment procedures.

Golden Rule 8

Company data shall remain completely isolated.

Golden Rule 9

Financial Years shall remain completely isolated.

Golden Rule 10

The Chart of Accounts shall remain the single source of financial classification.

Golden Rule 11

Business modules shall never bypass the official Posting Service.

Golden Rule 12

Historical accounting information shall never be physically deleted.

Golden Rule 13

All Financial Reports shall reconcile with the General Ledger.

Golden Rule 14

Business convenience shall never override accounting accuracy.

Golden Rule 15

This Business Constitution is the highest accounting authority within DG ERP.

Any accounting implementation that conflicts with this constitution shall be considered invalid until formally approved by the Business Owner.

---


## Authority
Business Owner

This document is the official Accounting Constitution of DG ERP and shall govern all current and future accounting development.

# END OF DOCUMENT
# DG ERP ACCOUNTING REPORT STANDARD
# VERSION 2.0 FINAL