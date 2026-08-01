# DG ERP — Loan Module Business Constitution

**Document Type:** Business Constitution and AI Implementation Contract  
**Module:** Loan  
**Version:** 1.0  
**Status:** FINAL — Business Rule Freeze  
**Authority:** Business Owner  
**System:** DG ERP Multi-Company SaaS  

---

## Document Purpose

This document is the final business authority for the DG ERP Loan Module.

It is written so that any developer or AI system can:

- implement the Loan Module;
- audit existing Loan code;
- identify business-logic bugs;
- create controllers, services, models, migrations, views, reports, tests, and accounting postings;
- verify consistency between the Loan sub-ledger, Cash/Bank accounts, Party accounts, Chart of Accounts, General Ledger, Trial Balance, Profit and Loss, and Balance Sheet;
- avoid assumptions not approved by the Business Owner.

No code, prompt, design, or implementation may contradict this document.

### Business Owner Amendment — Loan Compulsory Saving (Version 1)

This amendment is final and overrides every conflicting saving statement elsewhere in this document.

- Saving functionality in Version 1 applies only to Loan Taken.
- Loan Taken compulsory saving belongs to the company and is an Asset named **Loan Compulsory Saving / Loan Deposit Asset**.
- It is not customer/member saving, not Saving Payable, and not an Expense.
- Loan Given must not collect or manage borrower/member saving.
- Saving interest is not active.
- Loan Taken compulsory-saving deposit: Dr Loan Compulsory Saving Asset; Cr Cash/Bank.
- Withdrawal into a company Cash/Bank account: Dr Cash/Bank; Cr Loan Compulsory Saving Asset.
- Saving-funded Loan Taken settlement: Dr Loan Payable for principal; Dr Loan Interest Expense for interest; Dr Loan Fine Expense for fine; Cr Loan Compulsory Saving Asset for the total.
- A saving-funded settlement has no Cash/Bank movement and must not create an AccountTransaction.

---

## 1. Document Hierarchy

When rules conflict, follow this priority:

| Priority | Document | Authority |
|---:|---|---|
| 1 | Financial Year and Business Date Standard | Financial year, active-year rule, transaction date |
| 2 | Master Business Standard | Company isolation, cancel philosophy, record ownership |
| 3 | Master Accounting and Chart of Accounts Standard | Double-entry accounting and reporting |
| 4 | Master UI Framework Standard | Reusable DG UI rules |
| 5 | This Loan Module Standard | Loan-specific business constitution |
| 6 | Existing code | Implementation evidence only; code does not override business rules |

If existing code conflicts with this document, the code must be corrected.

---

## 2. Module Purpose

The Loan Module manages money borrowed by the company, money lent by the company, loan principal settlements, interest, fines, loan-related saving deposits, saving withdrawals, and saving used for loan settlement.

The module must support two primary loan directions:

1. **Loan Taken** — the company borrows money from a party.
2. **Loan Given** — the company lends money to a party.

The module is both:

- a **business sub-ledger** for party-wise loan details; and
- a **financial accounting source module** that posts every financial effect to the Chart of Accounts.

---

## 3. Core Accounting Principle

Every Loan transaction that changes money, asset, liability, income, expense, or equity must post to the Chart of Accounts through balanced double-entry accounting.

The Loan Module must never update only a local balance while ignoring the General Ledger.

The following records must remain synchronized:

- Loan Account;
- Loan Payment;
- Loan Saving Ledger;
- Party Account;
- Cash/Bank Account;
- Account Transaction;
- Journal Entry;
- General Ledger;
- Chart of Accounts balance;
- Trial Balance;
- Profit and Loss;
- Balance Sheet;
- Cash Flow report.

A transaction is incomplete if the business record is saved but its required accounting entry is missing.

---

## 4. Required Chart of Accounts

Each company must have, or be able to map, the following accounts.

### 4.1 Assets

- Cash in Hand
- Bank Accounts
- Loan Receivable
- Loan Interest Receivable, if accrual accounting is enabled
- Loan Fine Receivable, if accrual accounting is enabled

### 4.2 Liabilities

- Loan Payable
- Loan Interest Payable, if accrual accounting is enabled
- Loan Fine Payable, if accrual accounting is enabled

### 4.3 Income

- Loan Interest Income
- Loan Fine Income

### 4.4 Expenses

- Loan Interest Expense
- Loan Fine or Penalty Expense

### 4.5 Loan Compulsory Saving Asset

- Loan Compulsory Saving / Loan Deposit Asset

This account is used only for Loan Taken compulsory saving in Version 1.

### 4.5 Account Mapping Rule

The system may use company-level default mappings, loan-product mappings, or transaction-specific mappings, but every posting must resolve to valid company-owned Chart Accounts.

A financial Loan transaction must not be posted when a required Chart Account mapping is missing.

The system must show a clear validation error instead of silently skipping accounting.

---

## 5. Loan Types

### 5.1 Loan Taken

The company receives money from a party and owes principal to that party.

Accounting nature:

- Loan principal outstanding is a **Liability**.
- Interest paid is an **Expense**.
- Fine paid is an **Expense**.

### 5.2 Loan Given

The company gives money to a party and expects repayment.

Accounting nature:

- Loan principal outstanding is an **Asset**.
- Interest received is **Income**.
- Fine received is **Income**.

### 5.3 Type Immutability

After financial posting, Loan Taken must not be converted into Loan Given, and Loan Given must not be converted into Loan Taken.

If the direction was wrong, the original transaction must be cancelled and recreated correctly.

---

## 6. Loan Account Master

A Loan Account represents one contractual loan relationship with one party.

Minimum required fields:

- company_id;
- financial_year_id, where applicable;
- party_account_id;
- loan type: taken or given;
- loan/account number;
- transaction/start date;
- original principal;
- remaining principal;
- interest terms, if used;
- fine terms, if used;
- saving requirement or configuration, if used;
- source/destination Cash or Bank account;
- note;
- attachment/documentation;
- created_by;
- updated_by;
- cancelled_by or deleted_by where required;
- status.

### 6.1 Original Principal

Original principal is the principal amount at loan creation.

It must not be changed after financial activity exists.

### 6.2 Remaining Principal

Remaining principal is the unpaid principal only.

It must not include:

- interest;
- fine;
- saving;
- documentation charges;
- unrelated expenses.

### 6.3 Principal Formula

For Loan Taken:

`Remaining Principal = Original Principal - Principal Repaid + Reversed Principal Repayments`

For Loan Given:

`Remaining Principal = Original Principal - Principal Recovered + Reversed Principal Recoveries`

Remaining principal must never become negative.

---

## 7. Loan Creation Accounting

### 7.1 Loan Taken Creation

When the company receives borrowed money:

```text
Dr. Cash/Bank
Cr. Loan Payable
```

Effects:

- Cash/Bank increases;
- Loan liability increases;
- Party loan balance reflects amount payable;
- Remaining principal equals original principal.

### 7.2 Loan Given Creation

When the company lends money:

```text
Dr. Loan Receivable
Cr. Cash/Bank
```

Effects:

- Cash/Bank decreases;
- Loan receivable increases;
- Party loan balance reflects amount receivable;
- Remaining principal equals original principal.

### 7.3 Creation Validation

The system must validate:

- authenticated user belongs to the company;
- selected party belongs to the same company;
- selected Cash/Bank account belongs to the same company;
- selected Chart Accounts belong to the same company;
- financial year is active;
- transaction date is within the active financial year;
- principal is greater than zero;
- Cash/Bank has sufficient balance for Loan Given unless negative balance is explicitly allowed by another approved standard;
- loan type is valid;
- duplicate submission is prevented;
- all required accounting mappings exist.

---

## 8. Loan Payment Structure

A Loan Payment may contain four independent components:

1. Principal Amount
2. Interest Amount
3. Fine Amount
4. Saving Amount

Each component must be stored separately.

The system must never store only a combined payment total without component details.

### 8.1 Account Payment Total

When payment is made through Cash/Bank:

`Total = Principal + Interest + Fine + Saving`

### 8.2 Saving Payment Total

When payment is made using existing loan saving:

`Total Saving Used = Principal + Interest + Fine`

Saving must not be counted twice.

### 8.3 Principal Limit

Principal paid or recovered must not exceed remaining principal.

### 8.4 Zero-Value Rule

At least one component must be greater than zero.

A payment containing only zeros must be rejected.

---

## 9. Loan Taken Payment Accounting

Loan Taken payment means the company is paying a lender.

### 9.1 Principal Repayment

```text
Dr. Loan Payable
Cr. Cash/Bank
```

### 9.2 Interest Payment

```text
Dr. Loan Interest Expense
Cr. Cash/Bank
```

### 9.3 Fine Payment

```text
Dr. Loan Fine/Penalty Expense
Cr. Cash/Bank
```

### 9.4 Loan Taken Compulsory Saving Deposit

```text
Dr. Loan Compulsory Saving Asset
Cr. Cash/Bank
```

The saving belongs to the company. Principal, interest, fine, and compulsory saving remain separate components even when entered in one UI form.

---

## 10. Loan Given Payment Accounting

Loan Given payment means the company receives money from the borrower.

### 10.1 Principal Recovery

```text
Dr. Cash/Bank
Cr. Loan Receivable
```

### 10.2 Interest Receipt

```text
Dr. Cash/Bank
Cr. Loan Interest Income
```

### 10.3 Fine Receipt

```text
Dr. Cash/Bank
Cr. Loan Fine Income
```

### 10.4 Loan Given Saving Prohibition

Loan Given must not collect, hold, withdraw, or settle borrower/member saving in Version 1.

---

## 11. Loan Saving Ledger

Loan Compulsory Saving is a separate Asset sub-ledger connected only to a Loan Taken account.

It must support at least these transaction types:

- deposit;
- withdraw;
- loan_settlement;
- reversal.

### 11.1 Type Constants

Saving ledger types must be defined as constants or enums.

Raw strings repeated across controllers are not the final approved implementation pattern.

### 11.2 Running Balance

Every active saving ledger record must store `balance_after`.

The formula is:

`New Saving Balance = Previous Active Balance + Credit - Debit`

### 11.3 Saving Asset

Loan Taken compulsory saving belongs to the company. It is an Asset, not a Liability, Income, or Expense.

### 11.4 Loan Taken Compulsory Saving Deposit Accounting

```text
Dr. Loan Compulsory Saving Asset
Cr. Cash/Bank
```

### 11.5 Saving Withdrawal Into Company Cash/Bank

```text
Dr. Cash/Bank
Cr. Loan Compulsory Saving Asset
```

Effects:

- saving Asset decreases;
- Cash/Bank increases;
- saving sub-ledger decreases.

### 11.6 Saving Used for Loan Taken Settlement

Principal portion:

```text
Dr. Loan Payable
Cr. Loan Compulsory Saving Asset
```

Interest portion:

```text
Dr. Loan Interest Expense
Cr. Loan Compulsory Saving Asset
```

Fine portion:

```text
Dr. Loan Fine Expense
Cr. Loan Compulsory Saving Asset
```

No Cash/Bank entry or AccountTransaction is created because this is an internal Asset settlement.

### 11.7 Loan Given Saving

Saving functionality is prohibited for Loan Given in Version 1.

### 11.8 Insufficient Saving

A withdrawal or saving-based settlement must be rejected when amount exceeds active saving balance.

Saving balance must never become negative.

---

## 12. Saving Interest

Saving interest is not active in Version 1 and must not be implemented or advertised.

---

## 13. Party Account Rule

The Party Account is a business sub-ledger identity, not a substitute for the General Ledger.

Party balances may be maintained for operational convenience, but Loan sub-ledger and General Ledger control accounts remain authoritative. Loan compulsory saving reconciles to the Loan Compulsory Saving Asset, not PartyAccount.current_balance.

### 13.1 Meaning Must Be Frozen

The meaning of `current_balance` must be explicit and consistent throughout DG ERP.

It must not sometimes mean "amount receivable" and elsewhere mean "amount payable" without a documented sign convention.

Approved options are:

1. signed net balance with a documented debit/credit convention; or
2. separate receivable and payable balances; or
3. derived balance from sub-ledgers.

Until a separate Party Account Standard freezes the sign convention, code must not infer business meaning solely from `increment()` or `decrement()` calls.

### 13.2 Reconciliation

For every party:

- total active Loan Given remaining principal must reconcile to party loan receivable;
- total active Loan Taken remaining principal must reconcile to party loan payable;
- total active Loan Taken compulsory saving must reconcile to the Loan Compulsory Saving Asset;
- net party display may be derived, but underlying balances must remain separately auditable.

---

## 14. Cash and Bank Account Rule

Every Cash/Bank-affecting Loan transaction must:

- update the account balance through the approved Account Balance Service;
- create an immutable Account Transaction record;
- reference the source Loan transaction;
- use the correct debit/credit direction;
- belong to the same company;
- belong to the same active financial year;
- be reversible through cancellation.

Direct balance increment/decrement without the approved transaction service is prohibited in final architecture unless that service itself performs both operations atomically.

---

## 15. Chart of Accounts Posting Rule

The Chart of Accounts is the central accounting authority.

Every posted Loan transaction must create balanced journal lines where:

`Total Debit = Total Credit`

The system must reject unbalanced postings.

### 15.1 Source Reference

Each journal entry must store enough information to trace back to:

- source module: loan;
- source transaction type;
- source record ID;
- loan account ID;
- loan payment ID, when applicable;
- party account ID;
- financial year ID;
- company ID;
- transaction date;
- creator;
- reversal reference, when cancelled.

### 15.2 No Duplicate Posting

The same source transaction must not create duplicate journal entries.

Use a unique source-reference rule or idempotency mechanism.

---

## 16. Status and Record Lifecycle

Approved conceptual statuses:

- active;
- cancelled.

Optional workflow statuses such as draft, pending, or approved may be added only under a separate approval workflow standard.

### 16.1 Delete Rule

Posted financial transactions must not be hard-deleted.

Soft deletion alone is not sufficient for financial cancellation if it removes the transaction from reports without reversal.

The correct process is cancellation with reversal.

### 16.2 Cancelled Records

Cancelled records:

- remain stored;
- remain traceable;
- are excluded from active operational totals;
- have complete reversal entries;
- display cancelled status;
- cannot be edited or cancelled twice.

---

## 17. Cancellation and Reversal

Cancellation must reverse every effect of the original transaction in one database transaction.

### 17.1 Loan Creation Cancellation

Cancellation must reverse:

- Cash/Bank balance effect;
- Account Transaction;
- Chart of Accounts journal;
- General Ledger lines;
- Party balance/sub-ledger effect;
- Loan principal balance;
- related saving effect, if any;
- source record status.

### 17.2 Loan Payment Cancellation

Cancellation must restore:

- remaining principal;
- Cash/Bank balance;
- Party balance/sub-ledger;
- saving balance;
- Loan Compulsory Saving Asset;
- interest/fine income or expense;
- journal and ledger balances;
- source references.

### 17.3 Reversal Method

Reversal must create equal and opposite accounting entries.

Original journal lines must not be edited or deleted.

### 17.4 Cancellation Preconditions

The system must reject cancellation when:

- transaction is already cancelled;
- user lacks permission;
- transaction belongs to another company;
- reversal would break a later dependent transaction and no approved chain-reversal process exists;
- financial year is locked, unless authorized reopening or adjustment rules permit it.

---

## 18. Edit Rule

Financial amounts, loan direction, party, account, date, and Chart Account mappings must not be directly edited after posting.

Allowed documentation-only edits may include:

- note;
- attachment;
- reference number;
- non-financial description.

Such edits must:

- preserve financial values;
- preserve original transaction identity;
- store updated_by and updated_at;
- follow documentation edit permissions.

To correct a financial mistake:

1. cancel the original transaction;
2. create a new correct transaction.

---

## 19. Financial Year and Date Rules

Every financial Loan transaction must:

- use the company's active financial year;
- have a date inside that financial year;
- use the approved business-date validation;
- reject a client-supplied financial year that differs from the active financial year;
- respect locked-period rules.

The server must not trust hidden form values for company or financial-year authority.

---

## 20. Company Isolation and Security

Every Loan query and write must be scoped by authenticated user's `company_id`.

The system must not trust a raw record ID alone.

For every selected record, verify company ownership for:

- Loan Account;
- Party Account;
- Cash/Bank Account;
- Chart Account;
- Financial Year;
- Loan Payment;
- Saving Ledger;
- Journal Entry;
- attachment/document.

Cross-company access is prohibited even for Super Admin and Super Staff when viewing internal company business data, except through a separately approved support mechanism that still respects privacy rules.

---

## 21. Permissions

Permissions should be separated by action.

Recommended permissions:

- view_loan;
- create_loan;
- cancel_loan;
- view_loan_payment;
- create_loan_payment;
- cancel_loan_payment;
- view_loan_saving;
- create_loan_saving_deposit;
- create_loan_saving_withdraw;
- cancel_loan_saving_transaction;
- view_loan_report;
- export_loan_report;
- edit_loan_documentation.

Permission names may follow the final system naming standard, but capabilities must remain independently controllable.

---

## 22. Validation Rules

### 22.1 Common Validation

- company ownership is mandatory;
- active status is mandatory where required;
- amount fields must be numeric and non-negative;
- transaction total must be greater than zero;
- date must be valid;
- financial year must be active;
- attachment must follow file type and size policy;
- note length must follow UI/API standard;
- account and party IDs must exist within the company;
- status values must use constants/enums;
- server-side validation is mandatory even if client validation exists.

### 22.2 Monetary Precision

All financial amounts must use decimal storage, not floating-point storage.

Recommended database precision:

`DECIMAL(18,2)` or the approved system-wide monetary precision.

Calculations must use decimal-safe arithmetic.

Direct PHP float arithmetic is not the preferred final implementation for financial values.

### 22.3 Concurrency

Balance-affecting operations must:

- run inside a database transaction;
- lock relevant balance rows where concurrent updates are possible;
- prevent double submission;
- prevent lost updates;
- recalculate from authoritative active records where required.

---

## 23. Reporting Rules

The Loan Module must support at least:

- Loan Account List;
- Loan Taken Outstanding Report;
- Loan Given Outstanding Report;
- Party-wise Loan Statement;
- Loan Payment History;
- Principal Recovery/Repayment Report;
- Interest Income Report;
- Interest Expense Report;
- Fine Income Report;
- Fine Expense Report;
- Loan Saving Statement;
- Loan Compulsory Saving Asset Report;
- Saving Withdrawal Report;
- Cancelled Loan Transactions Report;
- Loan-to-General-Ledger Reconciliation;
- Loan-to-Party Reconciliation;
- Loan-to-Cash/Bank Reconciliation.

### 23.1 Active Record Rule

Normal totals must include active records only.

Cancelled records must appear only when explicitly requested or in audit reports.

### 23.2 Report Authority

Financial statements must derive from the General Ledger.

Operational Loan reports may derive from Loan tables, but totals must reconcile to General Ledger control accounts.

---

## 24. UI Business Rules

The UI must clearly show:

- Loan Taken or Loan Given;
- party;
- original principal;
- remaining principal;
- payment source;
- principal amount;
- interest amount;
- fine amount;
- saving amount;
- total;
- saving balance;
- Cash/Bank account;
- transaction date;
- financial year;
- note;
- attachment;
- status.

### 24.1 Dynamic Behavior

The UI may calculate totals and display balances, but server-side calculation is authoritative.

### 24.2 Direction Labels

The UI must use clear business language. It must not rely only on debit/credit terminology for ordinary users.

### 24.3 Cancellation Warning

Before cancellation, the UI must state that the action will reverse all financial effects and cannot be treated as a simple delete.

---

## 25. Database and Model Rules

### 25.1 Required Relationships

LoanAccount should relate to:

- company;
- financial year;
- party account;
- source account;
- payments;
- saving ledgers;
- creator/updater/canceller;
- journal entries or source references.

LoanPayment should relate to:

- loan account;
- company;
- financial year;
- account;
- saving ledger entries;
- account transactions;
- journal entry;
- creator/updater/canceller.

LoanSavingLedger should relate to:

- loan account;
- loan payment, when generated by a payment;
- account, when Cash/Bank is affected;
- company;
- financial year;
- creator;
- reversal source.

### 25.2 Constants and Enums

Use constants or enums for:

- loan type;
- payment source;
- saving transaction type;
- status;
- journal source type;
- transaction direction.

Magic strings scattered across controllers are prohibited in final architecture.

---

## 26. Service Architecture

Financial logic must not be duplicated across controllers.

Recommended services:

- LoanAccountService;
- LoanPaymentService;
- LoanSavingService;
- LoanCancellationService;
- LoanAccountingService;
- AccountBalanceService;
- JournalPostingService;
- LoanReconciliationService.

Controllers should handle:

- authorization;
- request validation;
- service invocation;
- response/redirect.

Services should handle:

- business rules;
- balance calculations;
- accounting postings;
- cancellation reversal;
- source references;
- reconciliation safeguards.

---

## 27. Known Implementation Audit Findings

The following findings were identified from the audited implementation and must be checked during future code correction.

### 27.1 Positive Existing Architecture

Existing implementation demonstrates:

- separate principal, interest, fine, and saving fields;
- account and saving payment sources;
- remaining principal updates;
- saving deposit/withdraw ledger records;
- running saving balance;
- financial year validation;
- active-record filtering;
- cancellation/reverse logic in major Loan flows;
- company scoping in reviewed controllers;
- Party Account connection to Loan Accounts.

These patterns should be preserved where they agree with this Constitution.

### 27.2 Chart of Accounts Posting Not Proven

The audited Loan code showed Cash/Bank Account Transaction handling, but complete Loan Receivable, Loan Payable, Interest Income/Expense, Fine Income/Expense, Loan Compulsory Saving Asset, Journal, and General Ledger posting was not proven.

Future audit must inspect the accounting services and confirm all postings required by this document.

### 27.3 Saving Withdrawal Direction Requires Correction/Verification

A reviewed saving-withdraw flow reduced both saving balance and selected Cash/Bank balance.

That behavior is correct only if the transaction means the company is paying saving back to the customer/member.

In that case, the accounting must be:

```text
Dr. Cash/Bank
Cr. Loan Compulsory Saving Asset
```

The UI label and business meaning must clearly identify this as a payout to the party.

If the intended meaning is "transfer saving into the company's Cash/Bank," then decreasing Cash/Bank is wrong; however, that interpretation is not the standard saving withdrawal meaning and must not be used without explicit approval.

The final code must align naming, user messaging, and accounting direction.

### 27.4 Saving Withdrawal Account Transaction

Every Cash/Bank-affecting saving withdrawal must create the approved Account Transaction and General Ledger posting.

A direct account balance change without transaction history is incomplete.

### 27.5 Saving Interest Not Yet Established

Deposit and withdraw support do not constitute saving-interest support.

Saving interest must not be advertised or assumed until the complete rules in Section 12 are implemented.

### 27.6 Party Balance Sign Convention Not Frozen

The PartyAccount model contains opening and current balance fields, but the reviewed model alone does not define whether positive values mean receivable or payable.

Future Party Account Standard must freeze this convention.

Until then, an AI auditor must treat Loan sub-ledger plus General Ledger as authoritative and flag ambiguous Party balance updates.

---

## 28. Prohibited Implementations

The following are prohibited:

- updating Cash/Bank without an Account Transaction;
- updating Loan records without General Ledger posting;
- posting Principal as Income or Expense;
- posting Saving deposits as Income;
- posting Saving withdrawals as Expense;
- combining Principal, Interest, Fine, and Saving into one unidentified amount;
- allowing remaining principal to become negative;
- allowing saving balance to become negative;
- using another company's party/account/loan/chart account;
- trusting client-supplied company_id;
- trusting client-supplied active financial year without server verification;
- hard-deleting posted transactions;
- editing posted financial amounts in place;
- cancelling without full reversal;
- using floats as the final monetary calculation method;
- silently skipping missing accounting mappings;
- creating unbalanced journal entries;
- duplicating journal posting on retry;
- using raw status/type strings throughout the codebase;
- treating Party Account balance as a replacement for General Ledger control accounts.

---

## 29. AI Implementation Contract

Any AI generating or modifying Loan code must perform these steps:

1. Read this document completely.
2. Read the Financial Year and Date Standard.
3. Read the Master Business Standard.
4. Read the Chart of Accounts and Accounting Standard.
5. Audit only files relevant to the assigned Loan task.
6. Identify the financial effect before writing code.
7. Define debit and credit entries for every transaction path.
8. Ensure company isolation.
9. Ensure active financial year validation.
10. Use database transactions for all balance-affecting operations.
11. Use decimal-safe calculations.
12. Create source-linked Account Transactions and Journal Entries.
13. Implement cancellation as full reversal.
14. Preserve audit history.
15. Add automated tests for success, validation, insufficient balance, cross-company access, duplicate submission, and cancellation.
16. Report any conflict between existing code and this document instead of copying the conflict.
17. Never invent a new business rule silently.

---

## 30. AI Bug Audit Checklist

An AI auditing Loan code must answer all of the following.

### Loan Creation

- Does Loan Taken debit Cash/Bank and credit Loan Payable?
- Does Loan Given debit Loan Receivable and credit Cash/Bank?
- Is principal greater than zero?
- Is Cash/Bank sufficient for Loan Given?
- Is remaining principal initialized correctly?
- Are Party and account company-scoped?
- Is the journal balanced?

### Loan Payment

- Are principal, interest, fine, and saving separate?
- Is principal limited by remaining principal?
- Does principal affect only Loan Receivable/Payable, not P&L?
- Does interest post to the correct Income or Expense account?
- Does fine post to the correct Income or Expense account?
- Is the Cash/Bank direction correct for Loan Taken versus Loan Given?
- Is an Account Transaction created?
- Is a Journal Entry created exactly once?

### Saving

- Does a Loan Taken saving deposit increase the compulsory-saving Asset and decrease Cash/Bank?
- Does withdrawal decrease the compulsory-saving Asset and increase Cash/Bank?
- Does saving settlement avoid unnecessary Cash/Bank entries?
- Does `balance_after` reconcile?
- Can balance become negative?
- Are saving types constants/enums?
- Is saving interest separately implemented and posted?

### Cancellation

- Is every original effect reversed?
- Is remaining principal restored?
- Is saving restored?
- Is Cash/Bank restored?
- Are Income/Expense effects reversed?
- Are journal lines reversed, not deleted?
- Is double cancellation prevented?

### Security and Integrity

- Is every query company-scoped?
- Is active financial year asserted server-side?
- Is transaction date valid?
- Are balance rows locked where necessary?
- Is duplicate submission prevented?
- Are monetary calculations decimal-safe?
- Are cancelled records excluded from active totals?

### Reconciliation

- Does Loan Given outstanding equal Loan Receivable control balance?
- Does Loan Taken outstanding equal Loan Payable control balance?
- Does total Loan Taken compulsory saving equal the Loan Compulsory Saving Asset control balance?
- Do Account Transactions reconcile with Cash/Bank General Ledger?
- Does the journal balance?

Any failed item is a bug, missing feature, or unresolved business decision.

---

## 31. Minimum Automated Test Matrix

The final Loan Module must include tests for:

1. create Loan Taken successfully;
2. create Loan Given successfully;
3. reject Loan Given with insufficient Cash/Bank;
4. reject cross-company party;
5. reject cross-company account;
6. reject inactive financial year;
7. reject out-of-year date;
8. pay Loan Taken principal only;
9. pay Loan Taken principal plus interest and fine;
10. recover Loan Given principal only;
11. recover Loan Given principal plus interest and fine;
12. create saving deposit;
13. reject saving withdrawal above balance;
14. process valid saving withdrawal;
15. use compulsory saving for Loan Taken settlement;
16. reject principal above remaining principal;
17. cancel Loan payment and verify complete reversal;
18. cancel saving transaction and verify complete reversal;
19. prevent double cancellation;
20. prevent duplicate journal posting;
21. verify debit equals credit;
22. verify Loan sub-ledger to General Ledger reconciliation;
23. verify cancelled records excluded from active reports;
24. verify concurrent payments cannot overpay principal;
25. verify concurrent saving withdrawals cannot create negative balance.

---

## 32. Final Frozen Business Rules

The following rules are FINAL:

1. DG ERP Loan Module supports Loan Taken and Loan Given.
2. Every financial Loan transaction must post to the Chart of Accounts.
3. Loan Taken principal is a Liability.
4. Loan Given principal is an Asset.
5. Principal is never Income or Expense.
6. Loan Given interest and fine received are Income.
7. Loan Taken interest and fine paid are Expenses.
8. Loan Taken compulsory saving belongs to the company and is an Asset.
9. Loan Taken saving deposit debits the compulsory-saving Asset and credits Cash/Bank.
10. Saving withdrawal into company Cash/Bank debits Cash/Bank and credits the compulsory-saving Asset.
11. Principal, interest, fine, and saving must remain separate.
12. Loan sub-ledger, Party sub-ledger, Cash/Bank, and General Ledger must reconcile.
13. Every Cash/Bank effect requires Account Transaction history.
14. Every financial effect requires balanced journal posting.
15. Posted transactions are corrected by cancellation and recreation, not direct financial editing.
16. Cancellation must reverse every original effect.
17. Financial records must not be hard-deleted.
18. All operations must enforce company isolation.
19. All financial transactions must enforce the active financial year and valid transaction date.
20. Saving interest is not active in Version 1.
20A. Saving functionality is prohibited for Loan Given in Version 1.
21. Party balance sign convention must be formally frozen before it is treated as an independent accounting authority.
22. Existing code must be changed wherever it conflicts with this document.

---

## 33. Final Authority Statement

This document is the final Loan Module Business Constitution for DG ERP Version 1.0.

Developers and AI systems must implement the business rules exactly as written.

When implementation is incomplete, the system must fail clearly and safely rather than post partial accounting.

When existing code conflicts with this Constitution, this Constitution prevails and the code must be audited, corrected, tested, and reconciled.
