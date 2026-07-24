# DG ERP — Expense Module Standard

**Document Type:** Business Constitution  
**Module:** Expense  
**Version:** 1.0  
**Status:** FINAL DRAFT — Pending Business Owner Approval  
**Scope:** Financial business logic for all Expense-related operations  
**Authority:** Business Owner  

---

## Document Hierarchy

| Priority | Document | Role |
|----------|----------|------|
| 1 | `03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md` | Financial Year, Business Date, Cancelled Record Rule |
| 2 | `04_DG_ERP_MASTER_BUSINESS_STANDARD.md` | Master principles, cancel philosophy |
| 3 | `01_DG_ERP_MASTER_UI_FRAMEWORK_STANDARD.md` | UI framework, print framework |
| 4 | `05_DG_ERP_INCOME_MODULE_STANDARD.md` | Mirror architecture reference |
| 5 | **This document** | Expense-specific business constitution |

---

## 1. Module Purpose

The Expense Module records **non-purchase operating payments** from company cash or bank accounts.

Expense is the **mirror** of the Income Module. Architecture, UI, validation, cancel workflow, and reporting patterns are identical except for ledger direction.

---

## 2. Accounting Constitution

```
Expense → Account Transaction → AccountBalanceService → General Ledger → Trial Balance → P&L
```

| Rule | Requirement |
|------|-------------|
| Ledger posting | Always via `AccountBalanceService` |
| Direct balance update | **Forbidden** |
| Physical delete | **Forbidden** — Cancel only |
| Create | Credit = amount, Debit = 0 |
| Cancel | Reverse via `AccountBalanceService::reverseTransaction` |

**Mirror of Income:**

| | Income | Expense |
|---|--------|---------|
| Debit | Amount | 0 |
| Credit | 0 | Amount |

---

## 3. Voucher Number

Server-generated only at store time:

```
EXP-{companyId}-{financialYearName}-{sequence}
```

Example: `EXP-4-2026-0001`

---

## 4. Core Workflows

### 4.1 Expense Create
- Validate Active Financial Year and business date
- Validate Expense Category (active, company-scoped)
- Validate Account (active cash/bank)
- Generate EXP voucher number
- Post Account Transaction credit through `AccountBalanceService`
- Status = Active (1)

### 4.2 Expense Edit
- Active records only; closed FY blocked
- Sync Account Transaction via `AccountBalanceService::updateTransaction`
- Set `updated_by`

### 4.3 Expense Cancel
- Mandatory cancel date (active FY) and cancel reason
- Reverse transaction (`reference_type`: `expense_cancel`)
- Set status = Cancelled (0), preserve amount for audit
- Record `cancelled_by`, `cancelled_date`, `cancel_reason`

---

## 5. Expense Category Master

- Company-scoped CRUD
- Status Active/Inactive
- Cannot delete category in use
- Inactive categories blocked for new entries

---

## 6. Permissions

| Permission | Purpose |
|------------|---------|
| `view_expense` | List and view |
| `create_expense` | Create |
| `edit_expense` | Edit |
| `cancel_expense` | Cancel |
| `print_expense` | Print list and voucher |
| `view_expense_categories` | Category list |
| `manage_expense_categories` | Category CRUD |

---

## 7. Reporting Rules

- Business date field: `expense_date`
- Cancelled records excluded from active totals (FY §10B)
- Dashboard summary: active expense total for active FY

---

## 8. Forbidden Rules

- ❌ Direct `accounts.current_balance` update
- ❌ Physical delete of posted expense
- ❌ Client-submitted expense number
- ❌ Purchase payments recorded as expense (use Purchase Module)

---

## 9. Implementation Mirror Checklist

Expense implementation must mirror Income for:

- Controller structure and trait usage
- Route pattern (index, create, store, show, edit, update, cancel, print, print-voucher)
- DG UI (toolbar, summary, filter, table, pagination)
- Voucher print layout
- Permission guards
- Audit fields (`created_by`, `updated_by`, cancel audit)

**Only difference:** Account Transaction debit/credit direction.
