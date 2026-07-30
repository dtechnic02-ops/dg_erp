# DG ERP ACCOUNTING REPORT STANDARD
## Document Version
Version: 1.0 FINAL

Document Name:
15_DG_ERP_ACCOUNTING_REPORT_STANDARD.md

Status:
FINAL
DO NOT MODIFY WITHOUT BUSINESS APPROVAL

=========================================================
MISSION
=========================================================

This document defines the official accounting architecture,
posting rules, chart of accounts structure,
financial reports and accounting standards for DG ERP.

This document is the single source of truth for:

- Chart of Accounts
- Journal
- General Ledger
- Trial Balance
- Profit & Loss
- Balance Sheet
- Control Accounts
- Posting Rules
- Financial Reports

All future accounting development must follow this document.

=========================================================
GENERAL PRINCIPLES
=========================================================

DG ERP uses Double Entry Accounting.

Every accounting transaction must satisfy:

Total Debit = Total Credit

No financial report may calculate balances directly from
Sales, Purchase, Customer or Supplier tables.

All accounting reports must be generated from:

account_transactions

Only.

=========================================================
ACCOUNT MASTER
=========================================================

The existing Accounts table is the official Chart of Accounts.

Do NOT create another Chart of Accounts table.

Required structure:

accounts

Required fields:

id
company_id

account_group
account_type
sub_ledger_type

account_name

bank_name
branch
account_no
iban
currency
swift_code

opening_balance
current_balance

image_path
note
status

created_at
updated_at

=========================================================
ACCOUNT GROUPS
=========================================================

Allowed values:

Asset

Liability

Income

Expense

Equity

These groups are permanent.

=========================================================
ACCOUNT TYPES
=========================================================

Operational account types:

Cash

Bank

ATM

Wallet

Other

These are payment classifications.

=========================================================
SUB LEDGER TYPES
=========================================================

NULL

customer

supplier

employee

party

=========================================================
CONTROL ACCOUNTS
=========================================================

Customer Receivable

Group:
Asset

Supplier Payable

Group:
Liability

Sales Income

Group:
Income

Purchase Expense

Group:
Expense

Owner Capital

Group:
Equity

=========================================================
POSTING RULE
=========================================================

Every financial module must create records inside:

account_transactions

No report may bypass account_transactions.

=========================================================
ACCOUNT TRANSACTIONS
=========================================================

Every transaction must contain:

company_id

financial_year_id

transaction_date

reference_type

reference_id

account_id

debit

credit

description

created_by

=========================================================
JOURNAL
=========================================================

Journal is the central accounting module.

Rules:

Debit total must equal Credit total.

Journal cannot save when totals differ.

Journal posting creates account_transactions.

Journal editing must preserve audit history.

=========================================================
GENERAL LEDGER
=========================================================

General Ledger is generated only from:

account_transactions

Each Ledger must display:

Opening Balance

Transaction Date

Reference

Description

Debit

Credit

Running Balance

Closing Balance

Filters:

Financial Year

Date Range

Account

=========================================================
TRIAL BALANCE
=========================================================

Generated only from:

account_transactions

Columns:

Account

Opening Debit

Opening Credit

Period Debit

Period Credit

Closing Debit

Closing Credit

Validation:

Total Debit

must equal

Total Credit

=========================================================
PROFIT & LOSS
=========================================================

Uses only:

Income

Expense

Calculation:

Income

minus

Expense

equals

Net Profit

or

Net Loss

=========================================================
BALANCE SHEET
=========================================================

Uses only:

Asset

Liability

Equity

Formula:

Assets

=

Liabilities

+

Equity

=========================================================
FINANCIAL YEAR
=========================================================

Every accounting report must be filtered by:

company_id

financial_year_id

Reports must never mix financial years.

=========================================================
COMPANY ISOLATION
=========================================================

Every accounting query must include:

company_id

No company may access another company's accounting records.

=========================================================
AUDIT RULES
=========================================================

Financial transactions must never be physically deleted.

Use:

status

reversal

or

soft delete

according to project standards.

=========================================================
PAYMENT MODULE RULE
=========================================================

Payment account dropdowns must display only operational accounts.

Allowed:

Cash

Bank

ATM

Wallet

Accounting-only accounts must not appear in payment selectors.

=========================================================
REPORT GENERATION ORDER
=========================================================

Posting

↓

Account Transactions

↓

General Ledger

↓

Trial Balance

↓

Profit & Loss

↓

Balance Sheet

=========================================================
DO NOT
=========================================================

Do not calculate reports directly from:

sales

purchase

customer

supplier

expense

income

loan

contra

Do not duplicate accounting balances.

Do not maintain separate ledger balances.

Use account_transactions as the only accounting source.

=========================================================
FINAL BUSINESS RULE
=========================================================

DG ERP Accounting follows:

Single Chart of Accounts

Single Account Transactions Table

Double Entry Accounting

Multi Company Isolation

Financial Year Isolation

Account Group Classification

Operational Account Type Classification

All accounting reports must be generated from account_transactions only.

END OF DOCUMENT