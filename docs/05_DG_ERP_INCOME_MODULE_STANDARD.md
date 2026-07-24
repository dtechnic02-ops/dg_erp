# DG ERP — Income Module Standard

**Document Type:** Business Constitution  
**Module:** Income  
**Version:** 1.0  
**Status:** FINAL DRAFT — Pending Business Owner Approval  
**Scope:** Financial business logic for all Income-related operations  
**Authority:** Business Owner  

---

## Document Hierarchy

This document is subordinate to the following frozen DG ERP standards. Where any conflict exists, the higher document always wins.

| Priority | Document | Role |
|----------|----------|------|
| 1 | `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md` | Financial Year, Business Date, Cancelled Record Rule |
| 2 | `04_DG_ERP_MASTER_BUSINESS_STANDARD.md` | Master principles, cancel philosophy, reuse architecture |
| 3 | `01_DG_ERP_MASTER_UI_FRAMEWORK_STANDARD.md` | UI framework, print framework, DG components |
| 4 | `02_DG_ERP_SALES_MODULE_STANDARD.md` | Reference architecture for validation, audit, cancel, ledger integrity |
| 5 | **This document** | Income-specific business constitution |

**Note:** `01_DG_ERP_MASTER_DEVELOPMENT_STANDARD.md` currently mirrors the UI Framework Standard. Development implementation shall follow UI Framework + this document + Financial Year Standard.

---

## 1. Module Purpose

The Income Module is the official business engine for recording **non-sales income** received by the company within DG ERP.

The module exists to ensure that every non-sales receipt is recorded accurately, posted to the correct cash or bank account, traceable in the Account Ledger, auditable through status-based cancellation, and correctly reflected in financial reports — without corrupting account balances, trial balance, profit and loss, or dashboard summaries.

### 1.1 What Income Module Records

Income Module records money received that is **not** created through the Sales Module.

**Examples (illustrative, not exhaustive):**

| Category | Example |
|----------|---------|
| Employment / personal | Salary, Part Time Job |
| Commission | Insurance Commission, Service Commission |
| Property | Rental Income |
| Finance | Interest Income |
| Other | Miscellaneous non-sales receipts |

Each entry represents **money received into a company cash or bank account**, classified by Income Category, with optional supporting documentation.

### 1.2 What Income Module Does NOT Record

The following transactions **must never** be recorded in the Income Module:

| Prohibited in Income | Correct Module |
|----------------------|----------------|
| Sales revenue | **Sales Module** (Sales Invoice, Sales Payment) |
| Customer receipts against sales invoices | **Sales Module** (Sales Payment) |
| Purchase-related transactions | **Purchase Module** |
| Supplier payments or refunds | **Purchase Module** / **Purchase Return Refund** |
| Stock-related receipts | **Sales Module** or approved inventory modules |
| Journal adjustments without a receipt event | **Journal Module** (when implemented) |
| Contra transfers between accounts | **Contra Module** (when implemented) |

**Golden boundary rule:** If the income originates from a **customer sale**, it belongs in Sales. If it originates from **operating receipt not linked to a sales invoice**, it belongs in Income.

### 1.3 Core Responsibilities

#### Income Entry

An **Income Entry** records a single receipt of money into a selected cash or bank account.

Business effect:

- An Income record is created with a unique **INC** voucher number.
- Income Category is validated against Income Category Master.
- `income_date` (Business Date) is validated against the Active Financial Year.
- An **Account Transaction** debit is posted through `AccountBalanceService`.
- Account balance increases through the ledger (never by direct balance update).
- Income status is set to **Active**.

#### Income Edit

**Income Edit** updates documentation fields and, when permitted, financial fields of an active income entry within the Active Financial Year.

Business effect:

- Allowed fields may be updated per Edit Rules (Section 6).
- Linked Account Transaction is updated through `AccountBalanceService`.
- Account balance remains consistent with the ledger.
- `updated_by` is recorded.

#### Income Cancel

**Income Cancel** voids an active income entry and reverses all account ledger effects caused by that entry.

Business effect:

- Income status is set to **Cancelled**.
- Account Transaction is reversed through `AccountBalanceService`.
- Account balance is restored through the ledger.
- Stored financial values (`amount`, category, account reference) are preserved for audit.
- Cancelled income is excluded from all financial calculations and active reporting totals.

Income Cancel ensures that a voided entry leaves no residual effect on account balance, trial balance, profit and loss, or dashboard summaries.

### 1.4 Supporting Controls

#### Income Category Master

Income Category Master defines the classification list for non-sales income. Every income entry must reference a valid category from this master.

#### Account Ledger

The Account Ledger tracks cash and bank balances. Income Entry **increases** account balance through a debit entry. Income Cancel **reverses** the debit entry.

#### Financial Year

Every income transaction must belong to a valid Financial Year and comply with the Active Financial Year rule. Financial Year behaviour is defined exclusively in `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md`.

#### Voucher Number

Every income entry receives a unique, sequential reference number under the fixed prefix **INC**. Voucher numbers provide audit trail and document identity.

---

## 2. Database Flow

This section documents the business tables involved in the Income Module, how they connect, and when each table is written.

### 2.1 Table Overview and Relationships

```
Income Category Master (1) ──► (many) Income Entries
                                        │
                                        └──► (1) Account
                                        └──► (1) Financial Year
                                        └──► (1) Company

Income Entry ──────────► Account Transactions (Debit on create; Reverse on cancel)
```

### 2.2 `income_categories`

**Purpose:** Master list of income classification categories.

**Written when:**

- A new Income Category is created through Income Category Master workflow.

**Updated when:**

- Category name, code, note, or status is edited (when edit workflow is permitted).

**Deleted:**

- Physical deletion is **discouraged**. Deactivation via status is preferred. Physical deletion of categories in use is **forbidden**.

**Examples (illustrative):**

| Name | Typical Use |
|------|-------------|
| Job | Salary, part-time earnings |
| Business Income | Non-sales business receipts |
| Commission Income | Insurance, service commissions |
| Rental Income | Property rental receipts |
| Interest Income | Bank or investment interest |
| Other Income | Miscellaneous receipts |

### 2.3 `incomes`

**Purpose:** Master record of a non-sales income receipt. Holds voucher number, business date, category, account, amount, note, attachment, status, and audit fields.

**Business fields:**

| Field | Rule |
|-------|------|
| Income No | Server-generated only; unique per company and financial year |
| Income Date | Business Date; must belong to Active Financial Year on create |
| Income Category | FK to `income_categories`; mandatory |
| Account | Cash or bank account; must belong to company |
| Amount | Must be greater than zero |
| Note | Optional |
| Attachment | Optional |
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

**Written when:**

- A new Income Entry is created through the Income Entry Workflow.

**Updated when:**

- Income Edit is processed (permitted fields only).
- Income Cancel is processed (status and cancel audit fields).

**Must never:**

- Be physically deleted after posting (cancel only).

**Connected to:** Company, Financial Year, Income Category, Account, Account Transactions.

### 2.4 Account Transactions

**Purpose:** Account ledger entries linked to income receipts. Every debit increases account balance. Every reversal restores balance.

**Written when:**

- Income Entry is created → **Debit** (account increases)

**Updated when:**

- Income Edit changes amount, account, or business date → existing transaction updated via `AccountBalanceService`

**Reversed when:**

- Income Cancel is processed → **Reverse** entry (or equivalent reversal workflow)

**Reference:**

| Field | Value |
|-------|-------|
| `reference_type` | `Income` |
| `reference_id` | Income record ID |
| `voucher_no` | Income No |
| `transaction_date` | `income_date` (Business Date) |

**Connected to:** Account, Financial Year, Income Entry.

### 2.5 Write Sequence Summary

| Business Event | Tables Written / Updated |
|----------------|--------------------------|
| Income Create | `incomes`, Account Transactions (debit) |
| Income Edit | `incomes`, Account Transactions (update) |
| Income Cancel | `incomes` (status + cancel audit), Account Transactions (reverse) |
| Income Category Create | `income_categories` |

---

## 3. Accounting Rule

Income increases company assets. All account effects **must** flow through the Account Ledger.

### 3.1 Posting Chain (Mandatory)

```
Income Create
    ↓
Account Transaction (Debit)
    ↓
Account Balance (via AccountBalanceService)
    ↓
General Ledger / Account Transactions Report
    ↓
Trial Balance
    ↓
Profit & Loss
    ↓
Dashboard (where income totals are implemented)
```

### 3.2 Income Create — Account Effect

When an Income Entry is saved:

1. Validate company, Active Financial Year, Business Date, category, account, and amount.
2. Create the `incomes` record with status **Active**.
3. Post Account Transaction through **`AccountBalanceService::createTransaction`**:

| Field | Value |
|-------|-------|
| `debit` | Income amount |
| `credit` | 0 |
| `transaction_date` | `income_date` |
| `voucher_no` | Income No |
| `reference_type` | `Income` |
| `reference_id` | Income ID |
| `description` | Income title or approved description pattern |

**Why debit:** Money is received into the account. Account balance increases. This is the mirror of Expense (which credits the account for money paid out).

### 3.3 Account Balance Principle

```
Account Balance = Total Debits − Total Credits
```

(active entries only)

The Income Module **must never** update `accounts.current_balance` directly through Eloquent `increment` or `decrement`. All balance changes must originate from `AccountBalanceService` and active Account Transactions.

### 3.4 Ledger Integrity

Every income receipt must produce exactly one active Account Transaction debit while status is Active. Cancel must fully reverse that effect. Edit must keep ledger and stored amount synchronized.

---

## 4. Income Entry Workflow

Validate Active Financial Year

↓

Validate Business Date belongs to Active Financial Year

↓

Validate Category exists in Income Category Master

↓

Validate Account belongs to Company and is Active

↓

Validate Amount > 0

↓

Generate Income No (server-side only)

↓

Create Income Record (status = Active)

↓

Post Account Transaction (Debit)

↓

Save Attachment (optional)

### 4.1 Step-by-Step Explanation

#### Validate Financial Year and Business Date

`income_date` must fall within the date range of the **currently Active Financial Year**.

**Why:** Financial truth is determined by Business Date and Active Financial Year. See `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md`.

#### Validate Category

Selected category must exist in Income Category Master for the same company and must be active.

**Why:** Classification must be consistent for reporting (Income List, P&L grouping, dashboards).

#### Validate Account

Account must belong to the same company and must be active (cash or bank).

**Why:** Receipts must post to a valid company account visible in Account Transactions and reconciliation.

#### Generate Income No

Income number is generated **server-side only** at store time. The client must never submit or control the final voucher number.

**Format (approved pattern):**

```
INC-{companyId}-{financialYearName}-{sequence}
```

Example: `INC-4-2026-0001`

**Why:** Sequential voucher identity, audit continuity, and race-safe numbering.

#### Create Income Record

Persist all business and audit fields. Default status = **Active**.

#### Post Account Transaction

Create debit entry through `AccountBalanceService`. Never update account balance directly.

---

## 5. Income Edit Workflow

Validate Income Status = Active

↓

Validate Financial Year is Active (for the income record's FY context per FY Standard)

↓

Validate Business Date still belongs to Financial Year

↓

Validate Category and Account

↓

Validate Amount > 0

↓

Update Income Record

↓

Update Account Transaction via AccountBalanceService

↓

Set updated_by

### 5.1 Editable Fields

| Field | Editable when Active |
|-------|----------------------|
| Income Date | Yes — must sync ledger `transaction_date` |
| Income Category | Yes |
| Account | Yes — must reverse old account effect and post to new account via service |
| Amount | Yes — must sync ledger amount |
| Title | Yes (if implemented as display field) |
| Note | Yes |
| Attachment | Yes — replace via approved upload service |

### 5.2 Non-Editable Fields

| Field | Rule |
|-------|------|
| Income No | Never changes after create |
| Company | Never changes |
| Financial Year | Never changes after create |
| Status | Changed only through Cancel workflow |
| `created_by` / `created_at` | Audit — never overwritten |

### 5.3 Business Date Edit Synchronization

When `income_date` is edited, the linked Account Transaction `transaction_date` **must** be updated to the same Business Date.

**Why:** FY Standard §19.6 — linked financial records must remain synchronized. Amounts must not change when only the date changes unless amount is also edited.

### 5.4 Cancelled Income

Cancelled income entries **must not** be edited.

**Why:** Cancel is a void operation. Further edits would corrupt reversal audit and reporting integrity.

---

## 6. Income Cancel Workflow

**Status:** All Income Cancel rules in this section are **FROZEN CONSTITUTION RULES** pending Business Owner approval of this document. Modification requires explicit Business Owner approval.

Validate Active Financial Year

↓

Validate Cancel Date belongs to Active Financial Year

↓

Validate Cancel Reason (mandatory)

↓

Validate Income Status = Active

↓

Reverse Account Transaction

↓

Mark Income Cancelled

↓

Preserve Audit History

### 6.1 Cancellation Conditions (Mandatory)

Income Cancel is permitted **only** when:

| Condition | Required |
|-----------|----------|
| Income status is **Active** | Yes |
| Cancel date belongs to Active Financial Year | Yes |
| Cancel reason is provided (max length per ValidationService) | Yes |
| Income is not already cancelled | Yes |

There are **no** dependent sub-documents (payments, returns, refunds) on a simple income entry. Cancel is always permitted for Active records within FY rules unless Business Owner defines future dependencies.

### 6.2 Cancellation Effects

| Effect | Rule |
|--------|------|
| Status | Set to **Cancelled** (0) |
| Account Transaction | Reversed via `AccountBalanceService` |
| Account Balance | Restored through ledger reversal |
| Stored amount | **Preserved** for audit — not zeroed |
| Physical deletion | **Forbidden** |
| Reports / totals | Excluded from all financial calculations |
| History | Preserved for audit, viewing, printing |

### 6.3 Cancel Audit Fields

On cancel, the system **must** record:

| Field | Source |
|-------|--------|
| `cancelled_by` | Authenticated user ID |
| `cancelled_date` | User-supplied cancel date (Business Date) |
| `cancel_reason` | User-supplied mandatory reason |

Optional: append cancel reason to note with standardized prefix for human-readable audit (implementation detail — must not replace structured cancel fields).

### 6.4 Why Status-Based Cancellation Only

Physical deletion destroys audit history and violates Master Business Standard Principle 9. Cancelled income remains available for:

- Audit  
- History  
- Investigation  
- Viewing  
- Printing  

Cancelled income is excluded from active totals per FY Standard §10B.

### 6.5 Reversal Pattern

Account reversal **must** follow the same architectural pattern used in Sales Payment Cancel and Expense void:

- Create reversal entry or call `AccountBalanceService::reverseTransaction` (or approved equivalent)
- `voucher_no` reversal prefix: `REV-{original voucher}` (consistent with DG ERP convention)
- `reference_type` cancel suffix pattern: approved project convention (e.g. `income_cancel` or `Income_cancel` — implementation must match AccountBalanceService conventions)

**Why:** Reversal must fully undo the original debit effect on account balance.

---

## 7. Income Category Master

### 7.1 Purpose

Income Category Master maintains the official classification list for non-sales income. It supports consistent reporting and user selection at income entry time.

### 7.2 Category Fields

| Field | Rule |
|-------|------|
| Name | Required; unique per company (recommended) |
| Code | Optional |
| Note | Optional |
| Status | Active / Inactive |
| Company | Mandatory isolation |
| Created By | Audit |

### 7.3 Category Rules

1. Every Income Entry **must** reference a valid active category.
2. Category is stored as **`income_category_id` FK**, not as free text.
3. Deactivating a category must not break historical income records.
4. Physical deletion of a category **in use** is **forbidden**.
5. Category master is maintained separately but linked from Income Module navigation.

### 7.4 Example Categories

| Category Name | Description |
|---------------|-------------|
| Job | Salary, wages, part-time job income |
| Business Income | General business receipts not from sales invoices |
| Commission Income | Insurance, service, referral commissions |
| Rental Income | Property or asset rental receipts |
| Interest Income | Bank or investment interest |
| Other Income | Miscellaneous non-sales receipts |

Examples are illustrative. Companies may define their own categories within master rules.

---

## 8. Account Ledger Rules

The account ledger records all cash and bank movements linked to income operations.

| Transaction | Effect | Explanation |
|-------------|--------|-------------|
| Income Create | **Increase Account (Debit)** | Money received into cash or bank |
| Income Edit (amount increase) | **Increase Account** | Additional receipt value |
| Income Edit (amount decrease) | **Decrease Account** | Correction of receipt value |
| Income Edit (account change) | **Transfer ledger effect** | Reverse old account; post new account |
| Income Cancel | **Reverse Account Transaction** | Receipt voided; balance restored |

### 8.1 Why Income Creates a Debit

Non-sales income represents an asset inflow. The receiving account balance must increase. Debit equals receipt amount; credit equals zero.

### 8.2 Why Direct Balance Update Is Forbidden

Direct `current_balance` manipulation bypasses Account Transactions, breaks Account Transactions report, breaks trial balance reconciliation, and violates Master Business reuse of Account Services architecture.

### 8.3 Relationship to Expense Module

| Module | Direction | Account Entry |
|--------|-----------|---------------|
| Expense | Money out | Credit |
| Income | Money in | Debit |

Architecture (service, validation, cancel, audit) **must** mirror Expense. Business direction is inverted.

---

## 9. Voucher Number Rules

Every income entry receives a fixed-prefix voucher number.

| Prefix | Document Type | Purpose |
|--------|---------------|---------|
| **INC** | Income Entry | Identifies a non-sales income receipt |

### 9.1 INC — Income Entry

Assigned when a new income entry is created. Links to account debit and income record.

### 9.2 Generation Rules

1. Generated **server-side only** at store time.
2. Unique per **company** and **financial year**.
3. Sequential within prefix scope.
4. Must not be submitted from browser form fields.
5. Database uniqueness enforcement is **recommended**.

### 9.3 Why Format Must Remain Stable

**Audit continuity:** Historical income documents, bank records, and account statements reference INC numbers.

**Ledger linkage:** INC numbers are embedded in Account Transaction voucher references.

**Legal and compliance:** Financial audits depend on consistent document numbering.

---

## 10. Financial Year Rules

All Income transactions must comply with `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md`.

This module does **not** redefine Financial Year behaviour.

The official authority for Financial Year, Business Date, Active Financial Year, Back-Date Entry, Company Isolation, Business Date Filtering, and Cancelled Record behaviour is:

**`03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md`**

### 10.1 Income-Specific FY Application

| Rule | Income application |
|------|-------------------|
| Business Date field | `income_date` |
| Create | Only in Active Financial Year |
| Edit | Only while FY rules permit; cancelled records not editable |
| Cancel | Cancel date must belong to Active Financial Year |
| Filter | List and reports filter by `income_date`, never `created_at` |
| Cancelled records (§10B) | May display when Status = All; never sum in totals |

If any conflict exists between this document and the Financial Year Standard, the Financial Year Standard always takes precedence.

---

## 11. Validation Rules

Every validation exists to protect account balances and reporting integrity.

### 11.1 Company Validation

- Income must belong to the authenticated user's company.
- Account and category must belong to the same company.
- Cross-company references are **forbidden**.

### 11.2 Financial Year Validation

- Active Financial Year must exist before create.
- `income_date` must fall within Active Financial Year on create.
- On edit, `income_date` must remain within the income record's financial year.
- Cancel date must fall within Active Financial Year.

### 11.3 Category Validation

- Category is **mandatory**.
- Category must exist in Income Category Master.
- Category must be active at create time (recommended on edit).

### 11.4 Account Validation

- Account is **mandatory**.
- Account must belong to company.
- Account must be active.

### 11.5 Amount Validation

- Amount is **mandatory**.
- Amount must be numeric.
- Amount must be **greater than zero**.
- Negative and zero amounts are **forbidden**.

### 11.6 Attachment Validation

- Attachment is **optional**.
- Allowed types: jpg, jpeg, png, pdf (or ValidationService document rule).
- Maximum size per ValidationService.

Upload must use approved **`FileUploadService`** — not ad-hoc public folder manipulation.

### 11.7 Cancel Validation

- Cancel reason is **mandatory** (max length per ValidationService).
- Cancel date is **mandatory**.
- Already cancelled income cannot be cancelled again.

### 11.8 Title / Description Validation

- Title (or primary description field) is **mandatory**.
- Max length per ValidationService.

---

## 12. Business Rules

All rules below are approved business policy for the Income Module.

### 12.1 Income Entry Rules

1. Every income receipt must produce an Income Entry with a unique **INC** number.
2. Every income entry must reference a valid Income Category.
3. Every income entry must post an Account Transaction debit equal to the income amount.
4. Income amount is the authoritative receipt value for account and reporting.
5. Default status on create is **Active**.
6. Sales revenue must **never** be recorded in this module.

### 12.2 Income Edit Rules

1. Only **Active** income may be edited.
2. Cancelled income is **not** editable.
3. Edit must synchronize Account Transaction through `AccountBalanceService`.
4. `updated_by` must be set on every successful edit.
5. Income No and Financial Year must never change after create.

### 12.3 Income Cancel Rules

1. Physical deletion of posted income is **forbidden**.
2. Cancel sets status to **Cancelled** and preserves stored amounts.
3. Cancel must reverse Account Transaction and restore account balance through the ledger.
4. Cancel must record `cancelled_by`, `cancelled_date`, and `cancel_reason`.
5. Cancel cannot be applied twice to the same entry.
6. Cancelled income is excluded from all financial calculations (FY §10B).

### 12.4 Category Master Rules

1. Categories are company-scoped.
2. Income entries reference category by FK.
3. Categories in use must not be physically deleted.
4. Inactive categories must not be used for new entries.

### 12.5 Reporting Rules

1. All income reports use **`income_date`** (Business Date).
2. Cancelled income is excluded from totals unless explicitly viewing history with no summation.
3. Income appears in Account Transactions with `reference_type = Income`.

---

## 13. Forbidden Rules

The following actions are **strictly forbidden**. Violation may cause irreversible financial damage.

### ❌ Record Sales Revenue in Income Module

**Forbidden:** Posting customer sales receipts or invoice collections as Income entries.

**Why:** Sales has its own ledger, VAT, customer, and stock rules. Duplicate recording corrupts revenue recognition.

---

### ❌ Update Account Balance Directly

**Forbidden:** Using `increment` or `decrement` on `accounts.current_balance` in Income Controller.

**Why:** Breaks Account Ledger as single source of truth; desynchronizes trial balance and Account Transactions report.

---

### ❌ Physically Delete Posted Income

**Forbidden:** Hard-deleting income records that have been posted to the ledger.

**Why:** Violates Master Business cancel philosophy and FY audit requirements.

---

### ❌ Client-Submitted Income Number

**Forbidden:** Accepting `income_no` from HTML form submission as authoritative.

**Why:** Race conditions, duplicate numbers, and audit integrity failure.

---

### ❌ Store Category as Free Text Only

**Forbidden:** Saving category name string without FK to Income Category Master.

**Why:** Orphan classifications when categories are renamed or removed.

---

### ❌ Skip Account Transaction on Create

**Forbidden:** Creating income without corresponding Account Transaction debit.

**Why:** Income would be invisible in General Ledger and Trial Balance.

---

### ❌ Include Cancelled Income in Financial Totals

**Forbidden:** Summing cancelled income in list totals, dashboard, P&L, or trial balance.

**Why:** FY Standard §10B — cancelled records are audit-only for calculations.

---

### ❌ Use created_at for Reporting or Filtering

**Forbidden:** Using system timestamps for income reports, filters, or ledger posting date.

**Why:** Business Date supremacy — FY Standard §19.

---

### ❌ Edit Cancelled Income

**Forbidden:** Modifying business or financial fields after cancel.

**Why:** Cancel is a final void state for operational purposes; edits would desync reversal audit.

---

### ❌ Module-Specific CSS or Print Layout

**Forbidden:** Creating `income.css`, standalone print HTML outside DG print framework, or custom ERP component naming (`erp-*`).

**Why:** UI Framework Standard — one framework for all modules.

---

## 14. UI Standard

Income Module UI **must** comply with `01_DG_ERP_MASTER_UI_FRAMEWORK_STANDARD.md`.

### 14.1 Approved Layout — List Page

```
dg-page
  dg-toolbar          (title, Add Income, Print)
  dg-container
    dg-section
      dg-summary      (optional totals — Active records only)
      dg-filter       (date, FY, category, account, status, search)
      dg-card
        dg-table      (Income list)
      pagination
```

**Table layout rule:** Summary → Filter → Table → Pagination.

### 14.2 Approved Layout — Create / Edit

```
dg-page
  dg-toolbar
  dg-container
    dg-section
      dg-card
        form fields (dg-input, dg-select, dg-textarea, dg-upload)
      action buttons (dg-btn)
```

### 14.3 Approved Layout — Show (Voucher)

```
dg-page
  dg-toolbar          (Back, Edit, Cancel, Print)
  voucher content
  dg-print area       (#printArea for DG print framework)
```

### 14.4 UI Components

Use approved DG components only:

- `dg-page`, `dg-toolbar`, `dg-container`, `dg-section`, `dg-card`
- `dg-table`, `dg-head`, `dg-body`, `dg-row`
- `dg-input`, `dg-select`, `dg-textarea`, `dg-btn`
- `dg-filter`, `dg-alert`, `dg-modal` (cancel confirmation)
- `dg-print`, `dg-attachment`, `dg-upload`

**No module-specific CSS.** Extend `common.css` only when approved.

### 14.5 Print Standard

| Print type | Rule |
|------------|------|
| Income List Print | DG print framework, A4 portrait, `#printArea`, reusable header/footer |
| Income Voucher Print | Same architecture as other DG voucher prints |
| Auto-print | Permitted on dedicated print routes with user approval pattern |

Print must respect current filters and exclude cancelled records from totals unless Status = All with no summation.

### 14.6 Permission Rule

UI must not decide permissions in JavaScript. Laravel provides authorization; Blade shows or hides actions based on backend permissions.

### 14.7 Flash and Validation

- Success and error messages via `dg-alert`.
- Field validation errors displayed per field.
- Cancel modal collects `cancel_date` and `cancel_reason` (Sales/Purchase pattern).

---

## 15. Reports and Integration

Income Module participates in the following reports and modules:

| Report / Module | Income role |
|-----------------|-------------|
| **Income List** | Primary index with filters and Active-only totals |
| **Income Voucher** | Single-entry show / print view |
| **Income Print** | Filtered list print |
| **Account Transactions** | Shows debit entries with `reference_type = Income` |
| **General Ledger** | Derived from Account Transactions |
| **Trial Balance** | Includes income debits in account totals |
| **Profit & Loss** | Includes non-sales income per chart/report mapping |
| **Dashboard** | Active income summaries where implemented |

All reports **must** use `income_date` as Business Date and **must** exclude cancelled records from calculations per FY §10B.

---

## 16. Audit Rules

Audit philosophy **must** match Sales Module and Master Business Standard.

| Event | Audit requirement |
|-------|-------------------|
| Create | `created_by`, `created_at` |
| Edit | `updated_by`, `updated_at` |
| Cancel | `cancelled_by`, `cancelled_date`, `cancel_reason`; status change |
| View / Print | Cancelled records remain visible for history |

Audit fields **must never** replace Business Date for financial behaviour.

Structured exception logging on failure is **recommended** (Sales/Purchase pattern).

---

## 17. Implementation Standard

Implementation **must** reuse existing DG ERP services and patterns before creating new ones.

| Layer | Required pattern |
|-------|------------------|
| Controller | Thin orchestration; company scope; FY validation |
| Services | `AccountBalanceService`, `ValidationService`, `FileUploadService`, `InvoiceNumberService` (or approved number service) |
| Model | FK relationships; no business logic duplication |
| Cancel | Status-based; reversal via AccountBalanceService |
| UI | DG Framework blades only |
| Tests | Store, edit, cancel, ledger sync, FY validation (recommended before production) |

### 17.1 Relationship to Sales Module

Sales Module is the **Master Business Standard** for architecture philosophy.

Income Module is **not** a Sales/Purchase mirror (no customer, stock, returns, VAT invoice).

Income Module **must** reuse:

- Cancel philosophy  
- Audit philosophy  
- Validation philosophy  
- AccountBalanceService pattern (as Expense mirror with inverted debit/credit)  
- Financial Year compliance  
- UI and print framework  

### 17.2 Relationship to Expense Module

Expense Module is the **closest implementation peer** for accounting workflow.

| Aspect | Expense | Income |
|--------|---------|--------|
| Account entry | Credit (money out) | Debit (money in) |
| Service usage | AccountBalanceService | AccountBalanceService (required) |
| Category | FK to expense category | FK to income category |
| Cancel | Status-based void (target state) | Status-based void (required) |

Parity with Expense accounting architecture is **mandatory** before Income Module is production-approved.

---

## 18. Change Management

Only the Business Owner may approve:

- Changes to this constitution  
- Exceptions to cancel or ledger rules  
- New income types that blur Sales/Income boundary  
- Permanent deviation from Expense accounting parity  

Any change to Financial Year behaviour requires updating `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md` first — not this document.

---

## 19. Production Approval Checklist

Before marking Income Module **production-ready**, verify:

### Business
- [ ] Sales revenue is never recorded in Income  
- [ ] Active FY enforced on create, edit, cancel  
- [ ] Business Date (`income_date`) used for all filters and reports  
- [ ] Cancel workflow live; hard delete removed  
- [ ] Cancelled records excluded from totals (§10B)  

### Accounting
- [ ] Account Transaction debit on create via AccountBalanceService  
- [ ] Ledger update on edit; reversal on cancel  
- [ ] No direct `current_balance` manipulation  
- [ ] Visible in Account Transactions report  

### Data
- [ ] Server-side INC numbering  
- [ ] Category FK to master  
- [ ] Company ownership on all references  

### UI
- [ ] DG Framework on all screens  
- [ ] DG print for list and voucher  
- [ ] Cancel modal with date and reason  
- [ ] Permissions from Laravel  

### Audit
- [ ] `created_by`, `updated_by`, cancel audit fields populated  

---

## 20. Final Rule

Income Module records **non-sales money received** into company cash or bank accounts.

Every receipt **must** post through the Account Ledger.

Every void **must** use cancel — never delete.

Business Date and Financial Year rules are **absolute**.

UI and print **must** follow the DG ERP Framework.

If source code conflicts with this document after approval, this document represents the approved business rules until Business Owner authorizes synchronized code changes.

---

**END OF DOCUMENT**

**Version:** 1.0  
**Status:** FINAL DRAFT — Pending Business Owner Approval  
**Next step:** Business Owner review and approval before any Income Module code implementation.
