# DG ERP — Opening Balance Standard

## Document Information

| Item | Value |
|------|-------|
| Document Name | DG ERP Opening Balance Standard |
| File Name | `18_DG_ERP_OPENING_BALANCE_STANDARD.md` |
| Document Type | Business Constitution |
| Version | 1.0 |
| Status | FINAL |
| Authority | Business Owner |
| Scope | Entire DG ERP Opening Balance |
| Applies To | All Companies |
| Architecture | Multi Company SaaS ERP |

---

# Document Hierarchy

1. 00_DG_ERP_CONSTITUTION.md
2. 03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md
3. 04_DG_ERP_MASTER_BUSINESS_STANDARD.md
4. 15_DG_ERP_ACCOUNTING_REPORT_STANDARD.md
5. 16_DG_ERP_ACCOUNTING_CORE_STANDARD.md
6. 17_DG_ERP_JOURNAL_STANDARD.md
7. This Document

If any Opening Balance rule conflicts with another document, this Opening Balance Standard becomes the final authority for all Opening Balance operations.

---

# PART 1 — Opening Balance Foundation

## Chapter 1 — Mission

### 1.1 Mission

The mission of DG ERP Opening Balance is to establish the official financial starting position of every company and every Financial Year in a controlled, auditable, and immutable manner.

Opening Balance represents the beginning of official accounting history for a company within a Financial Year.

---

### 1.2 Objectives

- Establish the initial financial position.
- Ensure accounting accuracy before business operations begin.
- Maintain complete auditability.
- Support General Ledger initialization.
- Support Trial Balance generation.
- Prevent unauthorized balance manipulation.
- Ensure every opening balance follows identical accounting rules.

---

## Chapter 2 — Purpose

The purpose of the Opening Balance module is to record the official opening balances of Assets, Liabilities, Equity, Income, and Expense accounts before normal business transactions begin.

Opening Balance is not an operational transaction.

Opening Balance is an accounting initialization process governed by the Accounting Core.

---

## Chapter 3 — Scope

The Opening Balance Constitution governs:

- Initial Company Opening Balance
- Financial Year Opening Balance
- Chart Account Opening Balance
- Journal Integration
- General Ledger Integration
- Trial Balance Integration
- Audit
- Security
- Validation
- Reversal

This document applies to every company operating under DG ERP.

---

## Chapter 4 — Opening Balance Philosophy

Opening Balance establishes the official financial starting point of a company.

Every amount entered through Opening Balance becomes part of the permanent accounting history.

Opening Balance shall never bypass the Accounting Core or Journal Constitution.

---

## Chapter 5 — Business Principles

1. One Company has one official Opening Balance per Financial Year.
2. Opening Balance must be fully auditable.
3. Opening Balance cannot exist without a valid Financial Year.
4. Opening Balance shall use the official Business Date.
5. Every Opening Balance shall generate official Journal Entries.
6. Every Opening Balance shall update the General Ledger.
7. Every Opening Balance shall affect the Trial Balance.
8. Opening Balance history is permanent.
9. Corrections shall occur only through approved reversal procedures.
10. Financial integrity always takes precedence over operational convenience.

---

## Chapter 6 — Authority

Opening Balance operations shall be performed only by authorized users with Opening Balance permission.

No business module shall create Opening Balance records directly.

Only the official Accounting Engine may post Opening Balance into the accounting system.

---

**PART 1 STATUS: FINAL**



# PART 2 — Opening Balance Business Constitution

## Chapter 1 — Eligible Accounts

### 2.1 Purpose

This chapter defines which Chart Accounts are eligible to receive Opening Balance and the constitutional rules governing their eligibility.

Only eligible accounts may participate in the official Opening Balance process.

---

### 2.2 Eligible Account Classes

Opening Balance may be recorded for the following account classes:

- Asset
- Liability
- Equity

These account classes represent the financial position of the company at the beginning of a Financial Year.

---

### 2.3 Conditional Eligibility

Income and Expense accounts shall not normally receive Opening Balance.

Exception:

If applicable accounting regulations or Business Owner policy require retained operational balances during Financial Year transition, the Accounting Core may allow controlled Opening Balance processing through officially approved closing procedures.

---

### 2.4 Posting Account Requirement

Only Posting Accounts may receive Opening Balance.

The following accounts shall never receive Opening Balance directly:

- Root Accounts
- Group Accounts
- Control Categories
- Header Accounts

Opening Balance shall always be assigned to the lowest posting level defined by the Chart of Accounts Constitution.

---

### 2.5 Company Isolation

Every Opening Balance belongs to exactly one Company.

The system shall reject:

- Cross-company Opening Balance
- Shared Opening Balance
- Cross-company Chart Account references

---

### 2.6 Financial Year Requirement

Opening Balance shall belong to one and only one Financial Year.

The system shall reject:

- Missing Financial Year
- Closed Financial Year
- Invalid Financial Year
- Cross-Year Opening Balance

---

### 2.7 Business Date Requirement

Every Opening Balance shall use the official Business Date defined by the Financial Year Constitution.

System Date shall never determine Opening Balance.

---

### 2.8 Account Status Validation

Opening Balance may be assigned only when the Chart Account is:

- Active
- Authorized for Posting
- Belongs to the Company
- Valid within the Financial Year

The system shall reject inactive, locked, archived, or invalid accounts.

---

### 2.9 Currency Rule

Phase 1 supports one base company currency only.

All Opening Balance amounts shall be recorded using the official company base currency.

Multi-Currency Opening Balance belongs to future constitutional documents.

---

### 2.10 Constitutional Principles

Eligible accounts determine the official financial starting position of the company.

No account may receive Opening Balance unless it satisfies every constitutional validation.

Financial integrity shall always take precedence over operational convenience.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

## Chapter 2 — Opening Balance Types

### 2.1 Purpose

This chapter defines every type of Opening Balance supported by DG ERP.

Each type follows identical accounting principles while serving different business situations.

---

### 2.2 Company Initial Opening Balance

Used when a company starts accounting in DG ERP for the first time.

This process establishes the initial accounting position of the company.

It may be performed only once unless officially reversed.

---

### 2.3 Financial Year Opening Balance

Used when creating a new Financial Year.

Opening balances shall normally originate from the officially approved closing balances of the previous Financial Year.

Manual modification shall follow Business Owner approval rules.

---

### 2.4 New Account Opening Balance

When a new Chart Account is introduced after accounting has started, its initial balance shall follow Accounting Core rules.

The system shall permanently audit the origin of the balance.

---

### 2.5 Migration Opening Balance

Used during data migration from another accounting system.

Migration Opening Balance shall:

- Preserve historical integrity.
- Be fully auditable.
- Record migration source.
- Record migration reference.
- Record responsible user.

---

### 2.6 Correction Opening Balance

Opening Balance shall never be edited directly.

Corrections shall occur only through approved reversal and reposting procedures.

---

### 2.7 Prohibited Types

The system shall reject:

- Duplicate Company Opening Balance
- Duplicate Financial Year Opening Balance
- Cross-company Opening Balance
- Partial unofficial Opening Balance
- Unbalanced Opening Balance

---

### 2.8 Constitutional Principles

Every Opening Balance belongs to one officially recognized business scenario.

No unofficial Opening Balance type shall exist inside DG ERP.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

# PART 3 — Opening Balance Processing Constitution

## Chapter 1 — Opening Balance Creation

### 3.1 Purpose

This chapter defines the official constitutional process for creating Opening Balance inside DG ERP.

Every Opening Balance shall follow one standardized workflow governed by the Accounting Core.

---

### 3.2 Creation Authority

Opening Balance may be created only by authorized users possessing Opening Balance permission.

No other module, API, developer tool, or database script may create official Opening Balance records directly.

---

### 3.3 Creation Workflow

Every Opening Balance shall follow the official processing sequence:

1. Company Validation
2. Financial Year Validation
3. Business Date Validation
4. User Permission Validation
5. Chart Account Validation
6. Opening Balance Validation
7. Journal Creation
8. Accounting Entry Creation
9. General Ledger Update
10. Trial Balance Update
11. Audit Log Creation
12. Transaction Commit

No step may be skipped.

---

### 3.4 Mandatory Information

Every Opening Balance shall contain:

- Company
- Financial Year
- Business Date
- Chart Account
- Debit Amount
- Credit Amount
- Remarks
- Reference Number
- Created By
- Created Date
- Created Time

The system shall reject incomplete Opening Balance records.

---

### 3.5 Debit and Credit Rules

Opening Balance shall always follow Double Entry Accounting.

The total Debit amount shall equal the total Credit amount.

The system shall reject every unbalanced Opening Balance.

---

### 3.6 Journal Integration

Every approved Opening Balance shall automatically create official Journal Entries.

Manual Journal creation for Opening Balance is prohibited.

Journal Entries created by Opening Balance become permanent accounting history.

---

### 3.7 Accounting Entry Integration

Every Opening Balance shall automatically generate official Accounting Entries.

Accounting Entries shall follow the Accounting Core Constitution.

Direct insertion into accounting tables is prohibited.

---

### 3.8 General Ledger Integration

Every approved Opening Balance shall update the General Ledger automatically.

Manual Ledger posting is prohibited.

General Ledger becomes the official accounting record.

---

### 3.9 Trial Balance Integration

Every approved Opening Balance shall automatically update the Trial Balance.

The Trial Balance shall remain balanced after Opening Balance posting.

---

### 3.10 Processing Transaction

The complete Opening Balance process shall execute inside one database transaction.

If any step fails:

- Journal shall not exist.
- Accounting Entry shall not exist.
- General Ledger shall not exist.
- Trial Balance shall not be updated.

The entire transaction shall be rolled back.

---

### 3.11 Duplicate Prevention

The system shall reject:

- Duplicate Opening Balance
- Duplicate Reference Number
- Duplicate Posting
- Duplicate Company Opening
- Duplicate Financial Year Opening

Only one official Opening Balance shall exist for each approved business scenario.

---

### 3.12 Constitutional Principles

Opening Balance is the official beginning of accounting history.

Every Opening Balance shall be processed once, completely, accurately, and permanently.

Financial integrity shall always take precedence over operational convenience.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

## Chapter 2 — Opening Balance Validation Constitution

### 3.13 Purpose

This chapter defines every validation required before an Opening Balance becomes official.

No Opening Balance shall be approved unless every constitutional validation succeeds.

---

### 3.14 Company Validation

The system shall verify:

- Company exists.
- Company is active.
- Company is not locked.
- Company has Accounting enabled.

---

### 3.15 Financial Year Validation

The system shall verify:

- Financial Year exists.
- Financial Year is active.
- Financial Year is not closed.
- Opening Balance is permitted for the Financial Year.

---

### 3.16 Business Date Validation

The system shall verify:

- Business Date exists.
- Business Date belongs to the Financial Year.
- Business Date is open.
- Business Date is authorized.

System Date shall never replace Business Date.

---

### 3.17 User Permission Validation

The system shall verify:

- User is active.
- User belongs to the Company.
- User has Opening Balance permission.
- User has Accounting permission.

Unauthorized users shall be rejected.

---

### 3.18 Chart Account Validation

The system shall verify:

- Account exists.
- Account belongs to the Company.
- Account is active.
- Account accepts posting.
- Account is eligible for Opening Balance.

---

### 3.19 Accounting Validation

The system shall verify:

- Debit equals Credit.
- No duplicate posting exists.
- No duplicate reference exists.
- Required accounts exist.
- Posting rules are satisfied.

---

### 3.20 Validation Failure

If any validation fails:

- Opening Balance shall not be created.
- Journal shall not be created.
- Ledger shall not be updated.
- Trial Balance shall remain unchanged.

---

### 3.21 Constitutional Principles

Validation protects accounting integrity.

No invalid Opening Balance shall ever become official.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

# PART 4 — Opening Balance Lifecycle Constitution

## Chapter 1 — Opening Balance Edit Constitution

### 4.1 Purpose

This chapter defines the constitutional rules governing the modification of Opening Balance records.

Financial integrity shall always take precedence over operational convenience.

---

### 4.2 Edit Authority

Opening Balance may be edited only before official posting.

Once officially posted into the Accounting Core, direct editing is permanently prohibited.

---

### 4.3 Editable Fields

Before posting, the following fields may be modified where permitted:

- Remarks
- Description
- Supporting Notes
- Attachment

Business Owner may define additional configurable fields.

---

### 4.4 Non-Editable Fields

The following fields shall never be modified after posting:

- Company
- Financial Year
- Business Date
- Chart Account
- Debit Amount
- Credit Amount
- Journal Reference
- Accounting Entry Reference

---

### 4.5 Audit Requirement

Every permitted modification shall permanently record:

- Previous Value
- New Value
- User
- Date
- Time
- Reason

Audit history shall never be deleted.

---

### 4.6 Constitutional Principles

Posted Opening Balance is permanent.

Corrections shall occur only through approved reversal procedures.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

## Chapter 2 — Opening Balance Cancel Constitution

### 4.7 Purpose

This chapter defines the constitutional rules governing cancellation of Opening Balance.

Cancellation shall preserve complete accounting history.

---

### 4.8 Cancellation Authority

Only authorized users possessing Opening Balance cancellation permission may perform cancellation.

Business Owner approval may be required according to company policy.

---

### 4.9 Cancellation Rules

Opening Balance cancellation shall:

- Preserve original records.
- Preserve Journal history.
- Preserve General Ledger history.
- Preserve Trial Balance history.
- Record cancellation reason.

Deletion is prohibited.

---

### 4.10 Cancellation Restrictions

The system shall reject cancellation when:

- Financial Year is closed.
- Audit Lock is enabled.
- Accounting Period is locked.
- Business policy prohibits cancellation.

---

### 4.11 Audit Requirement

Cancellation shall permanently record:

- User
- Date
- Time
- Reason
- Approval Reference

---

### 4.12 Constitutional Principles

Cancellation preserves accounting history.

Accounting history shall never disappear.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

## Chapter 3 — Opening Balance Reversal Constitution

### 4.13 Purpose

This chapter establishes the constitutional rules governing Opening Balance reversal.

Reversal is the only approved method for correcting posted Opening Balance.

---

### 4.14 Reversal Authority

Only authorized users may perform reversal.

Unauthorized reversal is prohibited.

---

### 4.15 Reversal Workflow

Official reversal sequence:

1. Validation
2. Approval
3. Reversal Journal
4. Accounting Entry Reversal
5. General Ledger Reversal
6. Trial Balance Update
7. Audit Recording

No step may be skipped.

---

### 4.16 Reversal Rules

Reversal shall:

- Preserve original posting.
- Create equal and opposite accounting entries.
- Maintain complete audit history.
- Record reversal reason.

Original records shall never be deleted.

---

### 4.17 Restrictions

The system shall reject:

- Duplicate reversal
- Partial reversal
- Unauthorized reversal
- Reversal of non-posted Opening Balance

---

### 4.18 Constitutional Principles

Correction occurs through reversal.

Accounting history remains permanent.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

## Chapter 4 — Opening Balance Lock Constitution

### 4.19 Purpose

This chapter defines when Opening Balance becomes locked.

Locked Opening Balance protects financial integrity.

---

### 4.20 Automatic Lock Conditions

Opening Balance shall become locked when:

- Financial Year is closed.
- Company is locked.
- Audit Lock is enabled.
- Business Owner activates permanent lock.

---

### 4.21 Locked Operations

When locked, the system shall reject:

- Edit
- Delete
- Cancel
- Reversal (unless constitutionally approved)
- Direct database modification

---

### 4.22 Audit

Every lock and unlock operation shall permanently record:

- Company
- Financial Year
- User
- Date
- Time
- Reason

---

### 4.23 Constitutional Principles

Locked Opening Balance preserves official accounting history.

No unauthorized modification shall occur after locking.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

# PART 5 — Opening Balance Governance Constitution

## Chapter 1 — Opening Balance Audit Constitution

### 5.1 Purpose

This chapter establishes the constitutional audit requirements governing every Opening Balance transaction inside DG ERP.

Every Opening Balance activity shall be permanently traceable.

---

### 5.2 Audit Scope

The audit system shall record every significant Opening Balance event including:

- Creation
- Validation
- Approval
- Posting
- Edit (before posting only)
- Cancellation
- Reversal
- Lock
- Unlock
- Migration
- System Exception

---

### 5.3 Mandatory Audit Information

Every audit record shall contain:

- Company
- Financial Year
- Business Date
- Opening Balance Reference
- User
- User Role
- Date
- Time
- Action
- Status
- Reason
- IP Address (where available)
- Device Information (where available)

---

### 5.4 Audit Protection

Audit history shall never be:

- Edited
- Deleted
- Replaced
- Rewritten

Audit records become permanent business evidence.

---

### 5.5 Constitutional Principles

Every Opening Balance action shall leave a permanent audit trail.

Financial transparency shall always take precedence over convenience.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

# Chapter 2 — Opening Balance Security Constitution

### 5.6 Purpose

This chapter defines the constitutional security rules protecting Opening Balance from unauthorized access and manipulation.

---

### 5.7 Authorization

Only authorized users possessing Opening Balance permission may perform Opening Balance operations.

Unauthorized access shall always be rejected.

---

### 5.8 Permission Control

Permissions shall include:

- View
- Create
- Approve
- Post
- Cancel
- Reverse
- Lock
- Unlock
- Audit View

Each permission shall be independently controlled.

---

### 5.9 Security Restrictions

The system shall reject:

- Unauthorized access
- Direct database modification
- API bypass
- Duplicate posting
- Company isolation violation
- Financial Year violation

---

### 5.10 Company Isolation

Every Opening Balance belongs to one Company only.

Users shall never access Opening Balance belonging to another company.

Cross-company operations are prohibited.

---

### 5.11 Constitutional Principles

Security protects accounting integrity.

Unauthorized accounting shall never exist inside DG ERP.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

# Chapter 3 — Developer Constitution

### 5.12 Purpose

This chapter defines mandatory constitutional rules for developers implementing Opening Balance functionality.

---

### 5.13 Developer Responsibilities

Developers shall always:

- Follow the Accounting Core Constitution.
- Follow the Journal Constitution.
- Follow the Financial Year Constitution.
- Follow the Business Date Constitution.
- Use the official Accounting Posting Service.

---

### 5.14 Developers Shall Never

Developers shall never:

- Insert directly into accounting tables.
- Modify posted Opening Balance.
- Delete accounting history.
- Bypass validation.
- Skip Journal creation.
- Skip Ledger update.
- Skip Trial Balance update.
- Ignore company isolation.

---

### 5.15 Required Processing Sequence

Every implementation shall follow:

Opening Balance

↓

Validation

↓

Journal

↓

Accounting Entry

↓

General Ledger

↓

Trial Balance

↓

Audit

↓

Commit

---

### 5.16 Constitutional Principles

Business Constitution overrides implementation convenience.

Every implementation shall preserve financial integrity.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

# Chapter 4 — Future Compatibility Constitution

### 5.17 Purpose

This chapter defines how Opening Balance shall remain compatible with future DG ERP modules.

---

### 5.18 Future Integration

Opening Balance shall support integration with:

- Sales
- Sales Return
- Purchase
- Purchase Return
- Expense
- Income
- Loan
- Inventory
- Warehouse
- Payroll
- Fixed Assets
- Budget
- Cost Center
- Multi Currency
- Consolidation
- Balance Sheet
- Profit & Loss
- Cash Flow
- Future Accounting Modules

---

### 5.19 Backward Compatibility

Future versions shall preserve:

- Opening Balance history
- Accounting references
- Journal references
- Ledger history
- Trial Balance history
- Audit history

Historical integrity shall never be compromised.

---

### 5.20 Constitutional Principles

Future expansion shall never invalidate existing accounting history.

Accounting continuity shall always be preserved.

---

## Chapter Status

**Status:** FINAL

**Authority:** Business Owner

---

# Chapter 5 — Final Constitutional Principles

### 5.21 Opening Balance Constitution

DG ERP recognizes Opening Balance as the official beginning of accounting for every Company and every Financial Year.

Every Opening Balance shall:

- Be validated.
- Be authorized.
- Be journalized.
- Be posted through the Accounting Engine.
- Update the General Ledger.
- Update the Trial Balance.
- Preserve permanent audit history.

---

### 5.22 Permanent Principles

The following constitutional principles shall always apply:

- One Company
- One Financial Year
- One Official Opening Balance
- One Official Accounting History
- One Source of Truth
- Permanent Audit Trail
- No Delete Policy
- Reversal-Based Correction
- Company Isolation
- Financial Integrity Above Operational Convenience

---

### 5.23 Final Declaration

This document is the constitutional authority governing every Opening Balance operation performed inside DG ERP.

If any future module, implementation, developer, or business process conflicts with this Constitution, this document shall prevail unless superseded by a newer Business Owner approved constitutional revision.

---

## PART 5 STATUS

**Status:** FINAL

**Authority:** Business Owner

---

# PART 6 — Appendices

## Appendix A — Opening Balance Workflow

Company

↓

Financial Year Validation

↓

Business Date Validation

↓

Permission Validation

↓

Chart Account Validation

↓

Opening Balance Validation

↓

Journal Creation

↓

Accounting Entry Creation

↓

General Ledger Update

↓

Trial Balance Update

↓

Audit Recording

↓

Commit

---

## Appendix B — Constitutional Do's

The system shall always:

- Validate Company
- Validate Financial Year
- Validate Business Date
- Validate Chart Account
- Maintain Double Entry Accounting
- Generate Journal automatically
- Update General Ledger automatically
- Update Trial Balance automatically
- Preserve Audit History
- Maintain Company Isolation
- Follow Accounting Core Constitution

---

## Appendix C — Constitutional Don'ts

The system shall never:

- Delete Opening Balance
- Edit posted Opening Balance
- Bypass Journal
- Bypass Accounting Engine
- Insert directly into Ledger
- Insert directly into Trial Balance
- Ignore Financial Year validation
- Ignore Business Date validation
- Ignore Company isolation
- Allow unbalanced Opening Balance

---

## Appendix D — Related Constitutional Documents

Opening Balance is governed together with:

- DG ERP Constitution
- Financial Year and Business Date Standard
- Master Business Standard
- Accounting Core Standard
- Journal Standard
- Accounting Report Standard

---

## Appendix E — Version History

| Version | Status | Description |
|----------|---------|-------------|
| 1.0 | FINAL | Initial constitutional release |

---

## Appendix F — Final Constitutional Declaration

This document forms part of the official DG ERP Business Constitution.

All Opening Balance implementations shall comply with this Constitution.

Business logic, accounting integrity, auditability, and company isolation shall never be compromised.

---

# DOCUMENT STATUS

Version: 1.0

Status: FINAL

Authority: Business Owner

END OF DOCUMENT
