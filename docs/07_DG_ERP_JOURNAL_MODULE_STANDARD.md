# DG ERP — Journal Module Standard

**Document Type:** Business Constitution  
**Module:** Journal  
**Version:** 1.2  
**Status:** FINAL DRAFT — Pending Business Owner Approval  
**Scope:** Financial business logic for all Journal Entry operations  
**Authority:** Business Owner  

**Revision 1.1:** Added Journal Edit Rule, Journal Detail Validation, Duplicate Account Rule, and Accounting Integrity as dedicated constitution sections.

**Revision 1.2:** Added Sub Ledger Architecture — current implementation, future roadmap, accounting rules, voucher display, General Ledger grouping, and backward compatibility.

---

## Document Hierarchy

This document is subordinate to the following frozen DG ERP standards. Where any conflict exists, the higher document always wins.

| Priority | Document | Role |
|----------|----------|------|
| 1 | `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md` | Financial Year, Business Date, Cancelled Record Rule |
| 2 | `04_DG_ERP_MASTER_BUSINESS_STANDARD.md` | Master principles, cancel philosophy, reuse architecture |
| 3 | `01_DG_ERP_MASTER_UI_FRAMEWORK_STANDARD.md` | UI framework, print framework, DG components |
| 4 | `02_DG_ERP_SALES_MODULE_STANDARD.md` | Reference architecture for validation, audit, cancel, ledger integrity |
| 5 | `05_DG_ERP_INCOME_MODULE_STANDARD.md` | Reference for voucher workflow, AccountBalanceService pattern |
| 6 | `06_DG_ERP_EXPENSE_MODULE_STANDARD.md` | Reference for voucher workflow, cancel, reporting |
| 7 | **This document** | Journal-specific business constitution |

**Note:** `01_DG_ERP_MASTER_DEVELOPMENT_STANDARD.md` currently mirrors the UI Framework Standard. Development implementation shall follow UI Framework + this document + Financial Year Standard.

---

## 1. Module Purpose

The Journal Module is the official business engine for **manual, balanced accounting entries** that are not automatically created by other DG ERP transaction modules.

The module exists to ensure that every manual adjustment is recorded accurately, posted to the correct accounts, traceable in the Account Ledger, auditable through status-based cancellation, and correctly reflected in financial reports — without corrupting account balances, trial balance, profit and loss, or dashboard summaries.

### 1.1 What Journal Module Records

Journal Module records **multi-line, balanced accounting entries** where total debits equal total credits.

**Examples (illustrative, not exhaustive):**

| Category | Example |
|----------|---------|
| Opening Balance | Initial account balances at FY start |
| Adjustment Entry | General ledger correction |
| Depreciation | Periodic asset depreciation |
| Bank Adjustment | Bank reconciliation adjustment |
| Correction Entry | Error correction not tied to a source document |
| Year End Adjustment | Period-end accrual or reclassification |
| Accrual Entry | Accrued income or expense |
| Prepaid Expense | Prepaid expense allocation |

Each journal entry represents a **formal double-entry voucher** with one header and multiple detail rows affecting two or more accounts.

### 1.2 What Journal Module Does NOT Record

The following transactions **must never** be recorded in the Journal Module when a dedicated module exists:

| Prohibited in Journal | Correct Module |
|-----------------------|----------------|
| Customer sales and sales revenue | **Sales Module** |
| Customer receipts against invoices | **Sales Module** (Sales Payment) |
| Sales returns and refunds | **Sales Return / Sales Return Refund** |
| Supplier purchases | **Purchase Module** |
| Supplier payments | **Purchase Module** (Purchase Payment) |
| Purchase returns and refunds | **Purchase Return / Purchase Return Refund** |
| Non-sales cash receipts | **Income Module** |
| Non-purchase cash payments | **Expense Module** |
| Transfers between two accounts only | **Contra Module** (when implemented) |
| Stock movements | **Sales / Purchase / Inventory modules** |

**Golden boundary rule:** If the transaction is already modeled by Sales, Purchase, Income, Expense, or another approved source module, it **must not** be duplicated in Journal.

### 1.3 Core Responsibilities

#### Journal Entry Create

A **Journal Entry** records a balanced set of account movements under one voucher number.

Business effect:

- A Journal header is created with a unique **JRN** voucher number.
- `journal_date` (Business Date) is validated against the Active Financial Year.
- At least **two detail rows** are saved.
- Total Debit **must equal** Total Credit.
- One or more **Account Transactions** are posted through `AccountBalanceService`.
- Account balances change only through the ledger (never by direct balance update).
- Journal status is set to **Active**.

#### Journal Edit

**Journal Edit** updates documentation fields and, when permitted, financial detail rows of an active journal entry within Financial Year rules.

Business effect:

- Header and detail rows may be updated per Edit Rules (Section 6 and Section 8).
- All linked Account Transactions are synchronized through `AccountBalanceService`.
- Journal remains balanced after edit.
- `updated_by` is recorded.

#### Journal Cancel

**Journal Cancel** voids an active journal entry and reverses all account ledger effects caused by that entry.

Business effect:

- Journal status is set to **Cancelled**.
- All related Account Transactions are reversed through `AccountBalanceService`.
- Account balances are restored through the ledger.
- Stored header and detail values are preserved for audit.
- Cancelled journals are excluded from all financial calculations and active reporting totals.

### 1.4 Supporting Controls

#### Account Master

Every journal detail row must reference a valid account from the company Account Master.

#### Account Ledger

The Account Ledger tracks all account movements. Journal Create posts debits and credits through Account Transactions. Journal Cancel reverses those entries.

#### Financial Year

Every journal transaction must belong to a valid Financial Year and comply with the Active Financial Year rule. Financial Year behaviour is defined exclusively in `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md`.

#### Voucher Number

Every journal entry receives a unique, sequential reference number under the fixed prefix **JRN**. Voucher numbers provide audit trail and document identity.

---

## 2. Journal Structure

### 2.1 Journal Header

| Field | Rule |
|-------|------|
| Journal No | Server-generated only; unique per company and financial year |
| Journal Date | Business Date; must belong to Active Financial Year on create |
| Reference No | Optional external or internal reference |
| Narration | **Required** — primary description of the journal entry |
| Attachment | Optional supporting document |
| Status | Active (1) default; Cancelled (0) on cancel |

**System / audit fields:**

| Field | Purpose |
|-------|---------|
| `company_id` | Company isolation |
| `financial_year_id` | Financial year ownership |
| `created_by` | Creator audit |
| `updated_by` | Last editor audit |
| `created_at` | System timestamp — audit only |
| `updated_at` | System timestamp — audit only |
| `cancelled_by` | User who cancelled (on cancel) |
| `cancelled_date` | Business cancel date (on cancel) |
| `cancel_reason` | Mandatory text on cancel (on cancel) |

**Derived header field (recommended):**

| Field | Rule |
|-------|------|
| `total_amount` | Stored total of debit side (must equal credit side); audit and display only |

### 2.2 Journal Details (Multiple Rows)

Each journal entry contains **one or more detail rows** in `journal_items` (or approved equivalent table).

| Field | Rule |
|-------|------|
| Account | Mandatory; FK to `accounts`; company-scoped; active account |
| Sub Ledger Type | Optional reference; derived from Account configuration when required |
| Sub Ledger ID | Optional; FK to Customer, Supplier, Employee, or Party entity when Sub Ledger Type applies |
| Debit | Numeric; ≥ 0 |
| Credit | Numeric; ≥ 0 |
| Remark | Optional row-level note |

**Row rules:**

1. Every row must have an account.
2. Every row must have **either** a debit **or** a credit amount greater than zero.
3. A row **must not** have both debit and credit greater than zero.
4. A row **must not** have both debit and credit equal to zero.
5. Minimum **two detail rows** per journal entry.
6. Sum of all debits **must equal** sum of all credits before save.

**Implementation note:** Existing legacy implementations may store row direction as `type` (`debit` / `credit`) with a single `amount` field. Approved implementations may use explicit `debit` and `credit` columns provided all constitution rules remain enforced.

### 2.3 Database Flow

```
Journal Header (1) ──► (many) Journal Detail Rows
        │                        │
        │                        └──► (1) Account per row
        └──► (1) Financial Year
        └──► (1) Company

Journal Entry ──────────► Account Transactions (Debit/Credit rows on create; Reverse on cancel)
```

### 2.4 Write Sequence Summary

| Business Event | Tables Written / Updated |
|----------------|--------------------------|
| Journal Create | `journals`, `journal_items`, Account Transactions (one per detail row) |
| Journal Edit | `journals`, `journal_items`, Account Transactions (sync via service) |
| Journal Cancel | `journals` (status + cancel audit), Account Transactions (reverse all) |

---

## 3. Accounting Constitution

All journal account effects **must** flow through the Account Ledger using approved DG ERP services.

### 3.1 Posting Chain (Mandatory)

```
Journal Entry
    ↓
Account Transaction(s)
    ↓
AccountBalanceService
    ↓
General Ledger
    ↓
Trial Balance
    ↓
Profit & Loss
```

### 3.2 Mandatory Rules

| Rule | Requirement |
|------|-------------|
| Ledger posting | Always via `AccountBalanceService` |
| Direct balance update | **Forbidden** — no `increment` / `decrement` on `accounts.current_balance` in Journal Controller |
| Physical delete | **Forbidden** — Cancel only |
| Balanced entry | Total Debit = Total Credit before save |
| Minimum rows | At least two detail rows |
| Cancel reversal | Reverse **all** related Account Transactions |

### 3.3 Journal Create — Account Effect

When a Journal Entry is saved:

1. Validate company, Active Financial Year, Business Date, narration, accounts, row rules, and balance.
2. Create the `journals` record with status **Active**.
3. Create all `journal_items` rows.
4. Post **one Account Transaction per detail row** through **`AccountBalanceService::createTransaction`**:

| Detail row | Account Transaction |
|------------|---------------------|
| Debit row | `debit = amount`, `credit = 0` |
| Credit row | `debit = 0`, `credit = amount` |

**Reference fields (each transaction):**

| Field | Value |
|-------|-------|
| `reference_type` | `Journal` |
| `reference_id` | Journal header ID |
| `voucher_no` | Journal No |
| `transaction_date` | `journal_date` (Business Date) |
| `description` | Narration and/or row remark per approved pattern |

### 3.4 Account Balance Principle

```
Account Balance = Total Debits − Total Credits
```

(active entries only)

The Journal Module **must never** update `accounts.current_balance` directly. All balance changes must originate from `AccountBalanceService` and active Account Transactions.

### 3.5 Balanced Journal Validation

Saving is **prohibited** if:

| Condition | Result |
|-----------|--------|
| Total Debit ≠ Total Credit | Reject save |
| Fewer than two detail rows | Reject save |
| Any row missing account | Reject save |
| Any row with both debit and credit > 0 | Reject save |
| Any row with both debit and credit = 0 | Reject save |
| Any inactive or cross-company account | Reject save |

Rounding tolerance: compare totals at **two decimal places** unless Business Owner approves company currency precision rules.

### 3.6 Ledger Integrity

Every active journal detail row must produce exactly one active Account Transaction while the journal status is Active. Cancel must fully reverse all row effects. Edit must keep ledger, detail rows, and stored totals synchronized and balanced.

---

## 4. Voucher Number Rules

Every journal entry receives a fixed-prefix voucher number.

| Prefix | Document Type | Purpose |
|--------|---------------|---------|
| **JRN** | Journal Entry | Identifies a manual balanced accounting voucher |

### 4.1 Generation Rules

1. Generated **server-side only** at store time.
2. Format (approved pattern):

```
JRN-{companyId}-{financialYearName}-{sequence}
```

Example: `JRN-4-2026-0001`

3. Unique per **company** and **financial year**.
4. Sequential within prefix scope.
5. Must not be submitted from browser form fields as authoritative input.
6. Database uniqueness enforcement is **recommended**.

### 4.2 Why Format Must Remain Stable

**Audit continuity:** Historical journal documents and account statements reference JRN numbers.

**Ledger linkage:** JRN numbers are embedded in Account Transaction voucher references.

**Compliance:** Financial audits depend on consistent document numbering.

---

## 5. Journal Entry Workflow

Validate Active Financial Year

↓

Validate Business Date belongs to Active Financial Year

↓

Validate Narration (required)

↓

Validate Reference No (optional)

↓

Validate at least two detail rows

↓

Validate each row (account, debit/credit rules)

↓

Validate Total Debit = Total Credit

↓

Generate Journal No (server-side)

↓

Create Journal Header (Status = Active)

↓

Create Journal Detail Rows

↓

Post Account Transactions via AccountBalanceService (one per row)

↓

Record created_by

### 5.1 Attachment Rules

- Attachment is **optional**.
- Allowed types: jpg, jpeg, png, pdf (or ValidationService document rule).
- Maximum size per ValidationService.
- Upload must use approved **`FileUploadService`** — not ad-hoc public folder manipulation.

### 5.2 Default Status

Default status on create is **Active (1)**.

---

## 6. Journal Edit Workflow

Validate Journal Status = Active

↓

Validate Active Financial Year rules

↓

Validate Business Date still belongs to Financial Year

↓

Validate Narration

↓

Validate detail rows and balance

↓

Update Journal Header

↓

Synchronize Journal Detail Rows

↓

Synchronize Account Transactions via AccountBalanceService

↓

Set updated_by

### 6.1 Editable Fields

| Field | Editable when Active |
|-------|----------------------|
| Journal Date | Yes — must sync all ledger `transaction_date` values |
| Reference No | Yes |
| Narration | Yes |
| Attachment | Yes — replace via approved upload service |
| Detail rows (account, debit, credit, remark) | Yes — journal must remain balanced |

### 6.2 Non-Editable Fields

| Field | Rule |
|-------|------|
| Journal No | Never changes after create |
| Company | Never changes |
| Financial Year | Never changes after create |
| Status | Changed only through Cancel workflow |
| `created_by` / `created_at` | Audit — never overwritten |

### 6.3 Business Date Edit Synchronization

When `journal_date` is edited, **all** linked Account Transaction `transaction_date` values **must** be updated to the same Business Date.

**Why:** FY Standard §19.6 — linked financial records must remain synchronized.

### 6.4 Cancelled Journal

Cancelled journal entries **must not** be edited.

**Why:** Cancel is a void operation. Further edits would corrupt reversal audit and reporting integrity.

---

## 7. Journal Cancel Workflow

**Status:** All Journal Cancel rules in this section are **FROZEN CONSTITUTION RULES** pending Business Owner approval of this document.

Validate Active Financial Year

↓

Validate Cancel Date belongs to Active Financial Year

↓

Validate Cancel Reason (mandatory)

↓

Validate Journal Status = Active

↓

Reverse all related Account Transactions

↓

Mark Journal Cancelled

↓

Preserve Audit History

### 7.1 Cancellation Conditions (Mandatory)

Journal Cancel is permitted **only** when:

| Condition | Required |
|-----------|----------|
| Journal status is **Active** | Yes |
| Cancel date belongs to Active Financial Year | Yes |
| Cancel reason is provided (max length per ValidationService) | Yes |
| Journal is not already cancelled | Yes |

### 7.2 Cancellation Effects

| Effect | Rule |
|--------|------|
| Status | Set to **Cancelled** (0) |
| Account Transactions | **All rows reversed** via `AccountBalanceService` |
| Account Balance | Restored through ledger reversal |
| Stored amounts and detail rows | **Preserved** for audit — not zeroed |
| Physical deletion | **Forbidden** |
| Reports / totals | Excluded from all financial calculations |
| History | Preserved for audit, viewing, printing |

### 7.3 Cancel Audit Fields

On cancel, the system **must** record:

| Field | Source |
|-------|--------|
| `cancelled_by` | Authenticated user ID |
| `cancelled_date` | User-supplied cancel date (Business Date) |
| `cancel_reason` | User-supplied mandatory reason |

### 7.4 Reversal Pattern

Account reversal **must** follow the same architectural pattern used in Sales, Purchase, Income, and Expense cancel workflows:

- Call `AccountBalanceService::reverseTransaction` (or approved equivalent) for each active transaction linked to the journal
- Reversal `reference_type` suffix pattern: approved project convention (e.g. `journal_cancel`)
- Preserve original voucher number in audit trail

**Why:** Reversal must fully undo all debit and credit effects posted by the journal.

---

## 8. Journal Edit Rule

**Status:** FROZEN CONSTITUTION RULE — v1.1

This section defines the official Journal Edit policy for the Journal Module. It extends Section 6 (Journal Edit Workflow) and Section 10.2 (Journal Edit Rules) without replacing them.

### 8.1 Edit Eligibility

| Rule | Requirement |
|------|-------------|
| Active journal only | Only **Active** Journal Entries may be edited |
| Cancelled journal | **Cancelled** Journal Entries **cannot** be edited under any circumstance |
| Permission | User must hold `edit_journal` permission |

**Why:** Cancel is a final void state. Editing a cancelled journal would corrupt reversal audit, reporting integrity, and ledger reconciliation.

### 8.2 Account Transaction Synchronization on Edit

When an **Active** Journal Entry is edited, all financial changes **must** synchronize through the existing **`AccountBalanceService` transaction update workflow**.

Approved service operations (implementation must match Income / Expense / Sales parity):

| Edit scenario | Required service action |
|---------------|-------------------------|
| Detail row amount changed | `AccountBalanceService::updateTransaction` (or approved row-sync equivalent) |
| Detail row account changed | Reverse old account effect; post new account effect via service |
| Detail row added or removed | Create, update, or reverse transactions to match final detail set |
| `journal_date` changed | Update all linked transaction `transaction_date` values via service |

**Mandatory rule:** Journal Edit **must not** modify `accounts.current_balance` directly through Eloquent `increment`, `decrement`, or manual assignment.

### 8.3 Audit Preservation on Edit

Editing **must preserve complete audit history**.

| Field | Edit behaviour |
|-------|----------------|
| `created_by` | Never overwritten |
| `created_at` | Never overwritten |
| `updated_by` | Set on every successful edit |
| `updated_at` | System timestamp updated on every successful edit |
| Cancel audit fields | Never modified through edit workflow |

Historical header and detail values before edit remain traceable through audit fields and Account Transaction history.

### 8.4 Edit Integrity Requirements

After every successful edit:

1. Journal must remain **balanced** (Total Debit = Total Credit).
2. Every detail row must satisfy Journal Detail Validation (Section 9).
3. Every active detail row must have exactly one synchronized active Account Transaction.
4. Duplicate accounts in the same journal remain permitted (Section 10).
5. Only Journal-owned Account Transactions may be affected (Section 11).

---

## 9. Journal Detail Validation

**Status:** FROZEN CONSTITUTION RULE — v1.1

This section defines row-level validation for every Journal Detail line. These rules apply on **create**, **edit**, and any save operation. They extend Section 2.2 and Section 9.6 without replacing them.

### 9.1 Per-Row Validation Rules

Each Journal Detail row **must** satisfy **all** of the following before the Journal may be saved:

| # | Rule | Requirement |
|---|------|-------------|
| 1 | Account | Account is **required** on every row |
| 2 | Direction | Either **Debit** OR **Credit** is **required** on every row |
| 3 | Mutual exclusivity | Debit and Credit **cannot both contain values** in the same row |
| 4 | Non-zero row | Debit and Credit **cannot both be zero** in the same row |
| 5 | Amount | The entered debit or credit amount **must be greater than zero** |
| 6 | Save gate | Any **invalid row prevents saving** the entire Journal |
| 7 | Sub Ledger | When Account requires Sub Ledger, entity selection is **mandatory** (Section 12) |

**Clarification on row amount:** The active side of the row (debit or credit) must be strictly **> 0**. Zero, negative, or empty amounts on the active side are **forbidden**.

### 9.2 Validation Enforcement Points

Journal Detail Validation **must** be enforced at:

| Layer | Requirement |
|-------|-------------|
| Server | Mandatory — authoritative rejection before persist |
| UI | Recommended — inline row validation before submit |
| Save transaction | Entire journal save aborted if any row fails |

**Why:** A single invalid row corrupts double-entry integrity and trial balance reconciliation.

### 9.3 Relationship to Journal Balance Validation

Row validation and journal balance validation are **both mandatory**:

| Validation type | Rule |
|-----------------|------|
| Row validation (this section) | Every row individually valid |
| Balance validation (Section 3.5) | Sum of debits = sum of credits |
| Minimum rows (Section 2.2) | At least two detail rows |

All three must pass before save is permitted.

---

## 10. Duplicate Account Rule

**Status:** FROZEN CONSTITUTION RULE — v1.1

### 10.1 Rule Statement

**Duplicate accounts are allowed inside the same Journal.**

The same account may appear on **multiple detail rows** within one journal entry. The system **must not** reject duplicate accounts.

The **only** mandatory balancing rule remains:

```
Total Debit = Total Credit
```

### 10.2 Valid Example

The following is a **valid** accounting journal:

| Account | Debit | Credit |
|---------|------:|-------:|
| Cash | 100.00 | — |
| Cash | 200.00 | — |
| Capital | — | 300.00 |

**Totals:** Debit 300.00 = Credit 300.00 ✅

**Why this is valid:** Multiple debits to the same account (Cash) may represent separate allocation lines consolidated into one balanced voucher. The system must accept this pattern.

### 10.3 Invalid Rejection (Forbidden)

The following validation behaviour is **forbidden**:

| Forbidden behaviour | Why |
|---------------------|-----|
| Rejecting save because the same `account_id` appears twice | Violates approved accounting practice |
| Merging duplicate account rows automatically without user action | Alters user intent and audit detail |
| Requiring unique accounts per journal | Not a DG ERP business rule |

### 10.4 Ledger Posting with Duplicate Accounts

When duplicate accounts appear in one journal:

- Each detail row **must** still produce its **own** Account Transaction through `AccountBalanceService`.
- Account balance effect is the **net sum** of all row postings for that account within the journal.
- Cancel **must** reverse **every** row-level transaction — not a single merged reversal.

---

## 11. Accounting Integrity

**Status:** FROZEN CONSTITUTION RULE — v1.1

### 11.1 Journal Transaction Ownership

Journal Entries created by users are **independent manual accounting entries**.

The Journal Module owns **only** Account Transactions where:

| Field | Value |
|-------|-------|
| `reference_type` | `Journal` (and approved cancel suffix such as `journal_cancel`) |
| `reference_id` | Journal header ID (or approved cancel reference pattern) |

### 11.2 Source Module Isolation (Mandatory)

Account Transactions generated by the following modules **must never** be modified through the Journal Module:

| Source Module | Example reference types |
|---------------|-------------------------|
| **Sales** | Sales Invoice, Sales Payment, Sales Return, Sales Return Refund |
| **Purchase** | Purchase Invoice, Purchase Payment, Purchase Return, Purchase Return Refund |
| **Income** | Income, income cancel |
| **Expense** | Expense, expense cancel |

**Forbidden:** Using Journal Edit, Journal Cancel, or any Journal workflow to update, reverse, or replace ledger entries owned by Sales, Purchase, Income, or Expense.

**Why:** Each source module owns its own cancel, audit, and reversal workflow. Cross-module ledger modification through Journal would destroy audit trail integrity and corrupt financial reports.

### 11.3 Journal Scope Boundary

| Action | Permitted scope |
|--------|-----------------|
| Create Account Transaction | Journal-owned entries only |
| Update Account Transaction | Journal-owned entries only |
| Reverse Account Transaction | Journal-owned entries only (cancel workflow) |
| Read / report | All Account Transactions (read-only across modules) |

Journal **only manages its own transactions**.

### 11.4 Correction of Source Module Errors

If a Sales, Purchase, Income, or Expense entry was posted incorrectly:

| Correct action | Incorrect action |
|----------------|------------------|
| Edit or cancel within the **source module** | Create a Journal entry that modifies source-module ledger rows |
| Approved manual Journal adjustment for a **new** balanced correction entry | Direct ledger tampering outside Journal-owned transactions |

A new Journal entry may record a **separate** balanced correction voucher. It **must not** alter existing source-module Account Transactions.

---

## 12. Sub Ledger Architecture

**Status:** FROZEN CONSTITUTION RULE — v1.2

This section defines the approved Sub Ledger architecture for the Journal Module. It extends Section 3 (Accounting Constitution) without replacing the posting chain or `AccountBalanceService` requirements.

### 12.1 Purpose and Scope

Journal supports **Sub Ledger references** on detail rows.

Sub Ledger is used **only** for:

| Purpose | Description |
|---------|-------------|
| Subsidiary Ledger | Track party-level detail linked to a GL account row |
| Reference | Preserve audit context on the voucher |
| Reporting | Support subsidiary ledger and analytical reports |
| Voucher Display | Show readable party information on Show and Print |

Sub Ledger **must never** replace the General Ledger Account.

### 12.2 Mandatory Posting Chain

Accounting posting **always** follows this chain. Sub Ledger does **not** alter this chain:

```
Journal
    ↓
Account Transaction
    ↓
AccountBalanceService
    ↓
General Ledger
```

Sub Ledger data is stored as **reference metadata** on journal detail rows. It is **not** a second posting destination.

### 12.3 Current Implementation (Approved)

The current approved implementation supports:

```
One Account
    ↓
One configured Sub Ledger Type
    (Customer OR Supplier OR Employee OR Party)
```

This implementation remains **valid**, **approved**, and **fully backward compatible**.

#### 12.3.1 Account Configuration

Each Account in the Account Master may have **one configured Sub Ledger Type**, or **none**.

| Account classification (examples) | Sub Ledger Type | Sub Ledger entity |
|-----------------------------------|-----------------|-------------------|
| Accounts Receivable | Customer | Customer Master |
| Accounts Payable | Supplier | Supplier Master |
| Salary Payable | Employee | Employee Account Master |
| Party Ledger | Party | Party Account Master |
| Cash | None | Not required |
| Bank | None | Not required |
| Expense | None | Not required |
| Income | None | Not required |
| Asset | None | Not required |
| Equity | None | Not required |

When an Account requires a Sub Ledger, the Journal Entry UI **must** display the related selection field dynamically based on the selected Account.

When an Account does **not** require a Sub Ledger, no Sub Ledger field is shown.

#### 12.3.2 Journal Detail Storage

When Sub Ledger applies, the Journal Detail row stores:

| Field | Rule |
|-------|------|
| `sub_ledger_type` | Copied from Account configuration (`customer`, `supplier`, `employee`, `party`) |
| `sub_ledger_id` | ID of the selected Customer, Supplier, Employee Account, or Party Account |

Sub Ledger fields are **reference only**. They do **not** create Customer Transactions, Supplier Transactions, Employee Transactions, or Party Transactions from the Journal Module.

#### 12.3.3 Validation (Current)

| Rule | Requirement |
|------|-------------|
| Required when configured | If Account has Sub Ledger Type, `sub_ledger_id` is **mandatory** on that row |
| Not allowed when unset | If Account has no Sub Ledger Type, Sub Ledger fields must be empty |
| Company scope | Selected entity must belong to the same company and be active |
| Posting account | Account Transaction always posts to the selected **Account** only |

### 12.4 Future Architecture (Roadmap)

Future DG ERP versions **may** support **multiple Sub Ledger Types** for the same Account.

**Example:**

```
Advance Account
    ↓
Employee
Supplier
Customer
```

**Future architecture constraints:**

| Constraint | Rule |
|------------|------|
| Accounting Engine | **Must not change** |
| General Ledger posting | **Must remain unchanged** |
| Configuration layer | May evolve to support multi-type account mapping |
| Journal detail row | May store one selected Sub Ledger Type + ID per row from the allowed set |
| Backward compatibility | Existing single-type configuration remains valid |

Any future enhancement must extend configuration and UI only. It **must not** require changes to `AccountBalanceService`, Account Transaction posting rules, or General Ledger derivation logic.

### 12.5 Accounting Rule — Sub Ledger Is Not the Posting Account

| Concept | Rule |
|---------|------|
| **Sub Ledger** | Reference and subsidiary tracking only — **not** the posting account |
| **Posting Account** | Always the selected **Chart of Account** (`account_id`) |
| **Account Transaction** | Always linked to `account_id` |
| **Direct posting to Customer / Supplier / Employee / Party** | **Forbidden** from Journal Module |

**Why:** General Ledger integrity depends on a single posting path through Account Transactions. Sub Ledger provides analytical detail without duplicating ledger ownership.

### 12.6 Voucher Display

Journal **Voucher Show** and **Voucher Print** may display:

| Display element | Example |
|-----------------|---------|
| Account Name | Accounts Receivable |
| Sub Ledger Type | Customer |
| Sub Ledger Name | ABC Traders |
| Debit / Credit | 1,000.00 |

**Illustrative voucher row:**

```
Accounts Receivable
Customer : ABC Traders
Debit 1,000.00
```

Display is informational. It **must not** imply a separate ledger posting.

### 12.7 General Ledger and Sub Ledger Reporting

| Report type | Grouping rule |
|-------------|---------------|
| **General Ledger** | Continue grouping by **Chart of Account** |
| **Trial Balance** | Continue account-level totals from Account Transactions |
| **Sub Ledger reports** | Generated **separately** from Journal Sub Ledger reference fields |

General Ledger reports **must not** replace Chart of Account grouping with Sub Ledger grouping.

Sub Ledger analytical reports **must not** assume Sub Ledger rows were posted as Account Transactions.

### 12.8 Backward Compatibility

| Item | Rule |
|------|------|
| Current implementation | Remains **fully valid** |
| Existing Journal Entries | **No migration required** |
| Accounting behaviour | **No change** |
| Business workflow | **No change** |
| Accounts without Sub Ledger Type | Continue to work without Sub Ledger fields |
| Future multi-type enhancement | Must not invalidate v1.2 single-type entries |

---

## 13. Financial Year Rules

All Journal transactions must comply with `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md`.

This module does **not** redefine Financial Year behaviour.

### 13.1 Journal-Specific FY Application

| Rule | Journal application |
|------|---------------------|
| Business Date field | `journal_date` |
| Create | Only in Active Financial Year |
| Edit | Only while FY rules permit; cancelled records not editable |
| Cancel | Cancel date must belong to Active Financial Year |
| Filter | List and reports filter by `journal_date`, never `created_at` |
| Cancelled records (§10B) | May display when Status = All; never sum in totals |

If any conflict exists between this document and the Financial Year Standard, the Financial Year Standard always takes precedence.

---

## 14. Validation Rules

Every validation exists to protect account balances, double-entry integrity, and reporting accuracy.

Row-level detail rules are defined in **Section 9 (Journal Detail Validation)**. This section covers module-wide validation categories.

### 14.1 Company Validation

- Journal must belong to the authenticated user's company.
- Every account on every detail row must belong to the same company.
- Cross-company references are **forbidden**.

### 14.2 Financial Year Validation

- Active Financial Year must exist before create.
- `journal_date` must fall within Active Financial Year on create.
- On edit, `journal_date` must remain within the journal record's financial year unless FY Standard permits otherwise.
- Cancel date must fall within Active Financial Year.

### 14.3 Account Validation

- Account is **mandatory** on every detail row.
- Account must belong to company.
- Account must be active at create time (recommended on edit).

### 14.3a Sub Ledger Validation

- When Account has a configured Sub Ledger Type, `sub_ledger_id` is **mandatory** on that detail row.
- When Account has no Sub Ledger Type, Sub Ledger fields must remain empty.
- Selected Sub Ledger entity must belong to the same company and be active.
- Sub Ledger validation is **reference-only** — it does not alter Account Transaction posting rules (Section 12).

### 14.4 Narration Validation

- Narration is **mandatory**.
- Max length per ValidationService.

### 14.5 Reference No Validation

- Reference No is **optional**.
- Max length per ValidationService when provided.

### 14.6 Detail Row Validation

- Minimum **two** rows required.
- Each row must satisfy **Section 9 (Journal Detail Validation)** in full.
- Debit and credit amounts must be numeric and non-negative on the active side.

### 14.7 Balance Validation

- Sum of debits must equal sum of credits (two decimal places).
- Save is **prohibited** when unbalanced.

### 14.8 Attachment Validation

- Attachment is **optional**.
- Allowed types and size per ValidationService / FileUploadService.

### 14.9 Cancel Validation

- Cancel reason is **mandatory**.
- Cancel date is **mandatory**.
- Already cancelled journal cannot be cancelled again.

### 14.10 Permission Validation

- All create, edit, cancel, print, and view actions must enforce Laravel permission checks.
- UI visibility follows backend authorization only.

---

## 15. Business Rules

All rules below are approved business policy for the Journal Module.

### 15.1 Journal Entry Rules

1. Every journal must produce a Journal Entry with a unique **JRN** number.
2. Every journal must contain at least **two detail rows**.
3. Every journal must be **balanced** before save.
4. Every detail row must post an Account Transaction through `AccountBalanceService`.
5. Default status on create is **Active**.
6. Transactions covered by Sales, Purchase, Income, or Expense **must not** be duplicated here.

### 15.2 Journal Edit Rules

See also **Section 8 (Journal Edit Rule)** for the full constitution policy.

1. Only **Active** journals may be edited.
2. Cancelled journals are **not** editable.
3. Edit must keep journal balanced.
4. Edit must synchronize all Account Transactions through `AccountBalanceService`.
5. `updated_by` must be set on every successful edit.
6. Journal No and Financial Year must never change after create.

### 15.3 Journal Cancel Rules

1. Physical deletion of posted journals is **forbidden**.
2. Cancel sets status to **Cancelled** and preserves stored header and detail values.
3. Cancel must reverse **all** related Account Transactions and restore account balances through the ledger.
4. Cancel must record `cancelled_by`, `cancelled_date`, and `cancel_reason`.
5. Cancel cannot be applied twice to the same entry.
6. Cancelled journals are excluded from all financial calculations (FY §10B).

### 15.4 Reporting Rules

1. All journal reports use **`journal_date`** (Business Date).
2. Cancelled journals are excluded from totals unless explicitly viewing history with no summation.
3. Journal entries appear in Account Transactions with `reference_type = Journal`.

---

## 16. Forbidden Rules

The following actions are **strictly forbidden**. Violation may cause irreversible financial damage.

### ❌ Save Unbalanced Journal

**Forbidden:** Saving when Total Debit ≠ Total Credit.

**Why:** Violates double-entry accounting and corrupts trial balance.

---

### ❌ Save Single-Row Journal

**Forbidden:** Saving a journal with fewer than two detail rows.

**Why:** A valid journal requires at least one debit and one credit across accounts.

---

### ❌ Update Account Balance Directly

**Forbidden:** Using `increment` or `decrement` on `accounts.current_balance` in Journal Controller.

**Why:** Breaks Account Ledger as single source of truth; desynchronizes trial balance and Account Transactions report.

---

### ❌ Physically Delete Posted Journal

**Forbidden:** Hard-deleting journal headers or detail rows that have been posted to the ledger.

**Why:** Violates Master Business cancel philosophy and FY audit requirements.

---

### ❌ Client-Submitted Journal Number

**Forbidden:** Accepting `journal_no` from HTML form submission as authoritative.

**Why:** Race conditions, duplicate numbers, and audit integrity failure.

---

### ❌ Skip Account Transaction on Create

**Forbidden:** Creating journal detail rows without corresponding Account Transactions.

**Why:** Journal would be invisible in General Ledger and Trial Balance.

---

### ❌ Include Cancelled Journals in Financial Totals

**Forbidden:** Summing cancelled journals in list totals, dashboard, P&L, or trial balance.

**Why:** FY Standard §10B — cancelled records are audit-only for calculations.

---

### ❌ Use created_at for Reporting or Filtering

**Forbidden:** Using system timestamps for journal reports, filters, or ledger posting date.

**Why:** Business Date supremacy — FY Standard §19.

---

### ❌ Edit Cancelled Journal

**Forbidden:** Modifying business or financial fields after cancel.

**Why:** Cancel is a final void state for operational purposes; edits would desync reversal audit.

---

### ❌ Duplicate Source-Module Transactions

**Forbidden:** Recording sales, purchase, income, or expense events in Journal when the source module already owns the transaction.

**Why:** Double posting corrupts revenue, expense, and account balances.

---

### ❌ Reject Duplicate Accounts in Same Journal

**Forbidden:** Rejecting a journal save because the same account appears on more than one detail row.

**Why:** Duplicate accounts in one journal are valid accounting entries when Total Debit equals Total Credit (Section 10).

---

### ❌ Modify Source-Module Account Transactions Through Journal

**Forbidden:** Using Journal Create, Edit, or Cancel to update, reverse, or replace Account Transactions owned by Sales, Purchase, Income, or Expense.

**Why:** Violates Accounting Integrity (Section 11). Each source module owns its own ledger lifecycle.

---

### ❌ Post Directly to Sub Ledger Entity

**Forbidden:** Creating Customer Transactions, Supplier Transactions, Employee Transactions, or Party Transactions from the Journal Module in place of Account Transactions.

**Why:** Sub Ledger is reference and subsidiary tracking only. General Ledger posting must remain on the selected Chart of Account through `AccountBalanceService` (Section 12).

---

### ❌ Module-Specific CSS or Print Layout

**Forbidden:** Creating `journal.css`, standalone print HTML outside DG print framework, or custom ERP component naming (`erp-*`).

**Why:** UI Framework Standard — one framework for all modules.

---

## 17. UI Standard

Journal Module UI **must** comply with `01_DG_ERP_MASTER_UI_FRAMEWORK_STANDARD.md` and reuse patterns from Sales, Income, and Expense list/show/voucher screens.

### 17.1 Approved Layout — List Page

```
dg-page
  dg-toolbar              (title, New Journal, Print List, Refresh)
  dg-container
    dg-section
      dg-summary            (optional totals — Active records only)
      dg-filter             (FY, date range, search, status)
      dg-card
        dg-list-card-header (title + Show per-page selector)
        dg-table            (Journal list)
      dg-list-footer        (record meta + pagination)
```

**Table layout rule:** Summary → Filter → Table → Pagination.

**Per-page selector:** Top-right of list card header. Options: **10, 20, 50, 100, 200**. Preserve selected value through filters and pagination (Sales List standard).

### 17.2 Approved Layout — Create / Edit

```
dg-page
  dg-toolbar
  dg-container
    dg-section
      dg-card               (header fields: date, reference, narration, attachment)
      dg-card               (detail grid — multiple rows)
        account select
        sub ledger select   (dynamic — when account requires sub ledger)
        debit input
        credit input
        remark input
        add/remove row controls
      running totals        (Total Debit, Total Credit, Balance indicator)
      action buttons        (dg-btn)
```

**Detail grid rule:** User must see live debit total, credit total, and balance status before save.

### 17.3 Approved Layout — Show (Voucher)

```
dg-page dg-invoice
  dg-toolbar dg-invoice-toolbar    (Back, Print, Edit, Cancel)
  dg-invoice-sheet
    Company Information
    Voucher Summary
    Transaction Details (detail table — account, sub ledger, debit, credit, remark)
    Attachment
    Cancellation Details (when Cancelled)
    Audit Information
    Amount in Words (when applicable to voucher design)
    Signature Section
```

Show page layout **must** follow Sales Show / Income Show / Expense Show voucher architecture.

### 17.4 UI Components

Use approved DG components only:

- `dg-page`, `dg-toolbar`, `dg-container`, `dg-section`, `dg-card`
- `dg-table`, `dg-head`, `dg-body`, `dg-row`
- `dg-input`, `dg-select`, `dg-textarea`, `dg-btn`
- `dg-filter`, `dg-alert`, `dg-modal` (cancel confirmation)
- `dg-print`, `dg-attachment`, `dg-upload`
- `dg-list-card-header`, `dg-list-per-page`, `dg-list-footer`, `dg-pagination`

**No module-specific CSS.** Extend `common.css` only when approved.

### 17.5 Print Standard

| Print type | Rule |
|------------|------|
| Journal List Print | DG print framework, A4 portrait, `#printArea`, reusable header/footer |
| Journal Voucher Print | Same architecture as Sales / Income / Expense voucher prints |
| Auto-print | Permitted on dedicated print routes with approved user pattern |

Print must respect current filters and exclude cancelled records from totals unless Status = All with no summation.

### 17.6 Permission Rule

UI must not decide permissions in JavaScript. Laravel provides authorization; Blade shows or hides actions based on backend permissions.

### 17.7 Flash and Validation

- Success and error messages via `dg-alert`.
- Field validation errors displayed per field and per detail row where applicable.
- Cancel modal collects `cancel_date` and `cancel_reason` (Sales / Income / Expense pattern).

---

## 18. Permissions

| Permission | Purpose |
|------------|---------|
| `view_journal` | List and view journal vouchers |
| `create_journal` | Create new journal entries |
| `edit_journal` | Edit active journal entries |
| `cancel_journal` | Cancel active journal entries |
| `print_journal` | Print list and voucher |

**Role guidance (default seed intent):**

| Permission | Admin | Staff (typical) |
|------------|-------|-------------------|
| `view_journal` | ✅ | ✅ |
| `create_journal` | ✅ | — |
| `edit_journal` | ✅ | — |
| `cancel_journal` | ✅ | — |
| `print_journal` | ✅ | ✅ |

Exact role mapping is configured in Permission Seeder and may be adjusted by Business Owner without changing this constitution.

---

## 19. Reports and Integration

Journal Module participates in the following reports and modules:

| Report / Module | Journal role |
|-----------------|--------------|
| **Journal List** | Primary index with filters, summary, and pagination |
| **Journal Voucher** | Single-entry show / print view with detail rows and Sub Ledger reference when configured |
| **Journal Print** | Filtered list print |
| **Account Transactions** | Shows debit/credit entries with `reference_type = Journal` — grouped by Chart of Account |
| **General Ledger** | Derived from Account Transactions; groups by Chart of Account (Section 12) |
| **Sub Ledger reports** | Separate analytical reports from Journal Sub Ledger reference fields (Section 12) |
| **Trial Balance** | Includes journal debits and credits in account totals |
| **Profit & Loss** | Includes journal effects per chart/report mapping |
| **Dashboard** | Active journal summaries where implemented |

All reports **must** use `journal_date` as Business Date and **must** exclude cancelled records from calculations per FY §10B.

---

## 20. Audit Rules

Audit philosophy **must** match Sales Module and Master Business Standard.

| Event | Audit requirement |
|-------|-------------------|
| Create | `created_by`, `created_at` |
| Edit | `updated_by`, `updated_at` |
| Cancel | `cancelled_by`, `cancelled_date`, `cancel_reason`; status change |
| View / Print | Cancelled records remain visible for history |

Audit fields **must never** replace Business Date for financial behaviour.

Structured exception logging on failure is **recommended** (Sales / Purchase pattern).

---

## 21. Implementation Standard

Implementation **must** reuse existing DG ERP services and patterns before creating new ones.

| Layer | Required pattern |
|-------|------------------|
| Controller | Thin orchestration; company scope; FY validation; balance validation |
| Services | `AccountBalanceService`, `ValidationService`, `FileUploadService`, approved number service |
| Model | FK relationships; header/detail structure; no business logic duplication |
| Cancel | Status-based; reversal of all transactions via AccountBalanceService |
| UI | DG Framework blades only |
| Pagination | Sales List per-page standard (10 / 20 / 50 / 100 / 200) |
| Tests | Store, edit, cancel, balance validation, ledger sync, FY validation (recommended before production) |

### 21.1 Relationship to Sales Module

Sales Module is the **Master Business Standard** for architecture philosophy.

Journal Module is **not** a Sales mirror (no customer, supplier, stock, VAT invoice, or payment sub-documents).

Journal Module **must** reuse:

- Cancel philosophy  
- Audit philosophy  
- Validation philosophy  
- AccountBalanceService pattern  
- Financial Year compliance  
- UI and print framework  
- List pagination and per-page selector pattern  

### 21.2 Relationship to Income and Expense Modules

Income and Expense are **single-sided cash/bank voucher peers**.

Journal is a **multi-sided balanced voucher**.

| Aspect | Income / Expense | Journal |
|--------|------------------|---------|
| Detail rows | One account movement | Multiple account rows |
| Balance rule | Single amount | Total Debit = Total Credit |
| Typical use | Receipt / payment | Adjustment / GL entry |
| Service usage | AccountBalanceService | AccountBalanceService (required) |
| Cancel | Status-based void | Status-based void (all rows reversed) |

---

## 22. Out of Scope

The following are **explicitly out of scope** for Journal Module Phase documentation and initial approved implementation:

| Item | Status |
|------|--------|
| General Ledger implementation | Out of scope |
| Trial Balance implementation | Out of scope |
| Profit & Loss implementation | Out of scope |
| Performance optimization | Out of scope |
| Refactoring unrelated modules | Out of scope |
| Bug fixing outside Journal approval cycle | Out of scope |

Journal Module **must** post correctly to Account Transactions so downstream GL / TB / P&L modules can consume ledger data when implemented.

---

## 23. Change Management

Only the Business Owner may approve:

- Changes to this constitution  
- Exceptions to cancel or ledger rules  
- New journal use cases that blur module boundaries  
- Permanent deviation from AccountBalanceService architecture  

Any change to Financial Year behaviour requires updating `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md` first — not this document.

---

## 24. Production Approval Checklist

Before marking Journal Module **production-ready**, verify:

### Business
- [ ] Sales, Purchase, Income, and Expense transactions are not duplicated in Journal  
- [ ] Active FY enforced on create, edit, cancel  
- [ ] Business Date (`journal_date`) used for all filters and reports  
- [ ] Cancel workflow live; hard delete removed  
- [ ] Cancelled records excluded from totals (§10B)  
- [ ] Minimum two detail rows enforced  
- [ ] Balanced journal enforced on create and edit  
- [ ] Journal Detail Validation enforced (Section 9)  
- [ ] Duplicate accounts permitted within same journal (Section 10)  
- [ ] Source-module Account Transactions never modified through Journal (Section 11)  
- [ ] Sub Ledger reference stored when Account requires Sub Ledger Type (Section 12)  
- [ ] Sub Ledger does not post outside AccountBalanceService / Account Transactions (Section 12)  

### Accounting
- [ ] Account Transaction posted for every detail row via AccountBalanceService  
- [ ] Ledger synchronized on edit via AccountBalanceService update workflow (Section 8)  
- [ ] All transactions reversed on cancel  
- [ ] No direct `current_balance` manipulation  
- [ ] Visible in Account Transactions report  

### Data
- [ ] Server-side JRN numbering  
- [ ] Company ownership on header, detail rows, and accounts  
- [ ] Narration required; reference optional  

### UI
- [ ] DG Framework on all screens  
- [ ] DG print for list and voucher  
- [ ] Cancel modal with date and reason  
- [ ] Detail grid on create/edit (including dynamic Sub Ledger when configured)  
- [ ] Voucher Show / Print display Sub Ledger reference when present (Section 12)  
- [ ] Sales-standard per-page selector on list  
- [ ] Permissions from Laravel  

### Audit
- [ ] `created_by`, `updated_by`, cancel audit fields populated  

---

## 25. Final Rule

Journal Module records **manual balanced accounting entries** not owned by Sales, Purchase, Income, or Expense.

Every detail row **must** post through the Account Ledger.

Every journal **must** balance before save.

Every void **must** use cancel — never delete.

Business Date and Financial Year rules are **absolute**.

UI and print **must** follow the DG ERP Framework.

If source code conflicts with this document after approval, this document represents the approved business rules until Business Owner authorizes synchronized code changes.

---

**END OF DOCUMENT**

**Version:** 1.2  
**Status:** FINAL DRAFT — Pending Business Owner Approval  
**Next step:** Business Owner review and approval before Journal Module implementation or refactor.
