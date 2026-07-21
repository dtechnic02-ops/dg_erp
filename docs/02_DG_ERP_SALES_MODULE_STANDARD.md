# DG ERP — Sales Module Standard

**Document Type:** Business Constitution  
**Module:** Sales  
**Status:** OFFICIAL — BUSINESS APPROVED  
**Scope:** Financial business logic for all Sales-related operations

---

## 1. Module Purpose

The Sales Module is the official business engine for recording revenue, managing customer receivables, controlling inventory outflow, and processing returns and refunds within DG ERP. Every sales-related financial event must follow the rules defined in this document.

The module exists to ensure that every sale is recorded accurately, every payment is traceable, every return is controlled, and every refund is auditable — without corrupting stock, customer balances, account balances, or financial reports.

### Core Responsibilities

#### Cash Sales

A **Cash Sale** is a sale where the customer pays the full invoice amount at the time of invoicing.

Business effect:

- A Sales Invoice is created.
- Stock is reduced for all product line items.
- The customer ledger is debited for the full invoice amount.
- An Account Transaction is created for the payment received.
- A Customer Credit is posted for the same payment amount.
- Invoice status is set to **Paid**.
- Due amount is zero.

Cash sales must still follow the full invoice workflow. Payment at invoice time does not bypass any validation or ledger rule.

#### Credit Sales

A **Credit Sale** is a sale where the customer does not pay the full amount at the time of invoicing.

Business effect:

- A Sales Invoice is created.
- Stock is reduced for all product line items.
- The customer ledger is debited for the full invoice amount.
- No Account Transaction or Customer Credit is created unless a partial payment is received at invoice time.
- Due amount equals Grand Total minus Paid Amount.
- Invoice status is set to **Unpaid** or **Partial**, depending on payment received.

Credit sales establish a receivable. The customer owes the business until payment is received through the Sales Payment workflow.

#### Sales Payment

**Sales Payment** is the process of receiving money from a customer against an existing unpaid or partially paid invoice.

Business effect:

- Account balance increases.
- Customer ledger receives a credit entry.
- Invoice due amount is reduced.
- Invoice payment status is updated to **Paid**, **Partial**, or **Unpaid**.

Sales Payment is a separate workflow from invoice creation. It applies to credit sales and to any additional payment collected after a partial cash sale.

#### Sales Cancel

**Sales Cancel** voids an active sales invoice and reverses all financial and stock effects caused by that invoice.

Business effect:

- Product stock is restored.
- Customer ledger entries for the invoice and its payments are reversed.
- Account ledger entries for related payments are reversed.
- Invoice status is set to **Cancel**.

Sales Cancel ensures that a voided invoice leaves no residual effect on stock, customer balance, account balance, or due amount.

#### Sales Return

**Sales Return** records the return of sold invoice lines against an existing sales invoice. Sales Return consists of two official workflows: **Product Return** and **Service Return**.

**Product Return** business effect:

- References an existing Product Sales Line on a Sales Invoice.
- Available Quantity validation is mandatory.
- Stock IN is mandatory.
- Return totals are calculated.
- Refund Balance is created (`refund_amount` is set equal to the return Grand Total).
- **No** Customer Ledger entry is created.
- **No** Account Ledger entry is created.

**Service Return** business effect:

- References an existing Service Sales Line on a Sales Invoice.
- Available Quantity validation is mandatory.
- **No** stock movement. **No** inventory movement.
- Return totals are calculated.
- Refund Balance is created (`refund_amount` is set equal to the return Grand Total).
- **No** Customer Ledger entry is created.
- **No** Account Ledger entry is created.

Sales Return handles return value tracking only. Product Return additionally restores inventory. It does not move money. Money movement is handled exclusively by Sales Return Refund.

#### Sales Return Cancel

**Sales Return Cancel** voids an active Sales Return that has **no** posted Sales Return Refund.

Business effect:

- Permitted **only** when Total Refunded Amount is zero and no active Sales Return Refund exists.
- Product Return lines: product stock is reversed (Stock OUT).
- Service Return lines: no stock movement.
- Available Return Quantity on the original sales item is restored.
- Stored financial values (`grand_total`, `adjust_amount`, `refund_amount`) are preserved for audit and historical purposes. **Status = Cancel** excludes the record from active business processing.
- Sales Return status is set to **Cancel**.
- All records are preserved for audit. Physical deletion is **forbidden**.

If any Sales Return Refund has been posted, Sales Return Cancel is **strictly prohibited**.

#### Sales Return Refund

**Sales Return Refund** settles a previously recorded sales return through the **Settlement Model**.

Business effect:

- Remaining refund amount is validated.
- **Settlement Total** = Invoice Adjustment Amount + Cash Refund Amount (Cash Refund Amount is optional).
- Sales Invoice is adjusted when Invoice Adjustment Amount is greater than zero.
- Customer ledger receives credit for the settlement.
- Account balance is validated and reduced **only when Cash Refund Amount is greater than zero**.
- `refund_amount` on the sales return is reduced by the Settlement Total.

Cash Refund Amount represents actual cash or bank outflow. Invoice Adjustment Amount settles refund liability against outstanding invoice due without account movement.

#### Refund Cancel

**Refund Cancel** voids a previously recorded sales return refund.

Business effect:

- Customer ledger credit is reversed.
- Account ledger transaction is reversed.
- `refund_amount` on the sales return is increased by the cancelled refund amount.
- **No** stock change occurs.

Refund Cancel restores the business to the state before the refund was paid, without re-processing the sales return.

### Supporting Controls

#### Customer Ledger

The Customer Ledger tracks how much each customer owes the business or how much the business owes the customer. Sales Invoice creates debit. Sales Payment and Sales Return Refund create credit. Sales Return creates no entry.

#### Account Ledger

The Account Ledger tracks cash and bank balances. Sales Payment increases account balance. Sales Return Refund decreases account balance **only when Cash Refund Amount is greater than zero**. Sales Return creates no entry.

#### Stock Control

Stock Control ensures product quantity is accurate. Sales reduces stock. Product Return increases stock. Service Return does not affect stock. Refund and Refund Cancel do not affect stock because money movement is separate from goods movement.

#### Invoice Number

Every sales document receives a unique, sequential reference number under a fixed prefix: **SI**, **SP**, **SR**, or **SRR**. Invoice numbers provide audit trail and document identity.

#### Financial Year

Every sales transaction must belong to a valid Financial Year. Financial Year rules control date validation, posting eligibility, and cross-year restrictions. No transaction may be posted outside approved financial year boundaries.

---

## 2. Database Flow

This section documents every business table involved in the Sales Module, how they connect, and when each table is written.

### Table Overview and Relationships

```
Sales Invoice (1) ──────► (many) Sales Items
       │
       ├──────────────────► (many) Sales Payments
       │
       └──────────────────► (many) Sales Returns
                                    │
                                    ├──► (many) Sales Return Items
                                    │
                                    └──► (many) Sales Return Refunds

Sales Invoice ──────────► Customer Transactions (Debit on invoice; Credit on payment)
Sales Payment ──────────► Account Transactions (Debit on payment)
Sales Payment ──────────► Customer Transactions (Credit on payment)
Sales Return Refund ────► Account Transactions (Credit on Cash Refund Amount only, when Cash Refund Amount > 0)
Sales Return Refund ────► Customer Transactions (Credit on settlement)

Sales Items (products) ─► Stock Movements (OUT on sale; IN on Product Return or invoice cancel)
```

### Sales Invoice

**Purpose:** Master record of a sale. Holds invoice number, customer, sale date, totals, paid amount, due amount, payment status, and invoice status.

**Written when:**

- A new sale is created through the Invoice Workflow.

**Updated when:**

- A Sales Payment is received (paid amount, due amount, payment status).
- A Sales Invoice is cancelled (status set to Cancel).

**Connected to:** Customer, Financial Year, Sales Items, Sales Payments, Sales Returns.

### Sales Items

**Purpose:** Line-level detail of every product or service sold on an invoice. Holds item type, quantity, unit price, VAT, and line total.

**Written when:**

- Sales Items are created during the Invoice Workflow, after stock is checked and reduced for product lines.

**Updated when:**

- Return quantities are updated through Sales Return Items when a Product Return or Service Return is processed.
- Returned quantity is restored when Sales Return Cancel is processed.

**Connected to:** Sales Invoice, Product (for product lines), Service (for service lines).

### Sales Payments

**Purpose:** Records every payment received against a sales invoice, including payments taken at invoice time and payments received later.

**Written when:**

- Payment greater than zero is received during Invoice Workflow (cash or partial sale).
- A standalone Sales Payment is recorded against an existing invoice.

**Updated when:**

- Payment is cancelled as part of Sales Cancel (payment status invalidated).
- Individual payment cancellation reverses ledger entries and updates invoice totals.

**Connected to:** Sales Invoice, Customer, Account, Financial Year.

### Sales Returns

**Purpose:** Master record of a Product Return or Service Return against a sales invoice. Holds return number, return date, totals, `adjust_amount` (Total Refunded Amount), and `refund_amount` (Remaining Refund Balance).

**Written when:**

- A Sales Return is processed.

**Updated when:**

- `adjust_amount` and `refund_amount` are set when a Sales Return is processed (`adjust_amount` = 0; `refund_amount` = return Grand Total).
- `adjust_amount` and `refund_amount` are updated automatically when a Sales Return Refund is paid.
- `adjust_amount` and `refund_amount` are updated automatically when a Refund Cancel is processed.
- Sales Return Cancel is processed (status set to Cancel; stored financial values preserved for audit).

**Connected to:** Sales Invoice, Customer, Financial Year, Sales Return Items, Sales Return Refunds.

### Sales Return Items

**Purpose:** Line-level detail of each returned invoice line. Holds returned quantity, unit price, VAT, and line total linked to the original sales item. Each line is either a Product Return line or a Service Return line.

**Line identity rule:**

- Each Sales Return Item must contain **either** `product_id` **or** `service_id`.
- Exactly **one** must contain a value.
- Both cannot contain values.
- Both cannot be NULL.

**Written when:**

- Each return line is processed during the Product Return Workflow or Service Return Workflow.

**Updated when:**

- Sales Return Cancel is processed (line status set to Cancel; returned quantity reversed on the linked sales item).

**Connected to:** Sales Return, Sales Item, Product (Product Return lines), Service (Service Return lines).

### Sales Return Refunds

**Purpose:** Records settlement of a sales return refund. Holds Settlement Total, Invoice Adjustment Amount, Cash Refund Amount (optional), and audit fields.

**Written when:**

- A Sales Return Refund is processed.

**Updated when:**

- Refund is cancelled through Refund Cancel Workflow.

**Connected to:** Sales Return, Customer, Account (when Cash Refund Amount > 0), Financial Year, Sales Return Refund Adjustments.

### Customer Transactions

**Purpose:** Customer ledger entries. Every debit increases what the customer owes. Every credit reduces what the customer owes or records money returned to the customer.

**Written when:**

- Sales Invoice is created → **Debit**
- Sales Payment is received → **Credit**
- Sales Return Refund is paid → **Credit**
- Sales Cancel, payment cancel, or Refund Cancel → **Reverse** entries

**Not written when:**

- Sales Return is processed.

**Connected to:** Customer, Financial Year, reference document (invoice, payment, or refund).

### Account Transactions

**Purpose:** Account ledger entries. Every debit increases account balance. Every credit decreases account balance.

**Written when:**

- Sales Payment is received → **Debit** (account increases)
- Sales Return Refund Cash Refund Amount is paid → **Credit** (account decreases)
- Sales Cancel, payment cancel, or Refund Cancel → **Reverse** entries

**Not written when:**

- Sales Return is processed.
- Sales Return Refund is processed with Cash Refund Amount equal to zero (adjustment-only settlement).

**Connected to:** Account, Financial Year, reference document (payment or refund).

### Stock Movements

**Purpose:** Audit trail of every stock change. Records product, quantity, direction, reference document, and before/after stock levels.

**Written when:**

- Sales Invoice is created → Stock **OUT** for each product line
- Product Return is processed → Stock **IN** for each returned product line
- Product Return Cancel is processed → Stock **OUT** for each cancelled product return line
- Sales Invoice is cancelled → Stock **IN** (restored)

**Not written when:**

- Service Return is processed
- Service Return Cancel is processed
- Sales Return Refund is paid
- Refund Cancel is processed

**Connected to:** Product, Financial Year, reference document.

### Write Sequence Summary

| Business Event | Tables Written |
|----------------|----------------|
| Sales Invoice | Sales Invoice, Sales Items, Stock Movements, Customer Transactions; optionally Sales Payments, Account Transactions |
| Sales Payment | Sales Payments, Account Transactions, Customer Transactions; Sales Invoice updated |
| Sales Cancel | Stock Movements (restore), reverse Customer and Account Transactions; Sales Invoice and Sales Payments updated |
| Sales Return | Sales Returns, Sales Return Items; Stock Movements (Product Return lines only) |
| Sales Return Cancel | Stock Movements (Product Return lines only); Sales Returns, Sales Return Items, and linked Sales Items updated; status set to Cancel |
| Sales Return Refund | Sales Return Refunds, Sales Return Refund Adjustments, Customer Transactions; Sales Returns and Sales Invoices updated; Account Transactions (Cash Refund Amount only, when Cash Refund Amount > 0) |
| Refund Cancel | Reverse Customer Transactions; reverse Account Transactions (when Cash Refund Amount was posted); Sales Return Refunds and Sales Returns updated; Sales Invoices restored |

---

## 3. Invoice Workflow

This is the official, business-approved sequence for creating a sales invoice. **The order must not be changed.**

```
Sales Create
    ↓
Generate Invoice Number
    ↓
Validate Financial Year

The transaction date must satisfy all rules defined in
03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md.

This includes:

• Active Financial Year
• Business Date Validation
• Company Ownership
• Date Range Validation
• Back-Date Entry Rules

No Sales transaction may bypass those rules.
    ↓
Validate Customer
    ↓
Validate Product / Service
    ↓
Check Stock
    ↓
Reduce Stock
    ↓
Create Sales Items
    ↓
Create Customer Transaction (Debit)
    ↓
If Payment > 0
    Create Account Transaction
    Create Customer Credit
    ↓
Calculate Due
    ↓
Invoice Status
    Paid | Partial | Unpaid
```

### Step-by-Step Explanation

#### Sales Create

The user initiates a new sales invoice. All required header and line information must be provided before processing begins.

#### Generate Invoice Number

A unique **SI** (Sales Invoice) number is assigned to the document.

**Why:** Every invoice must have a permanent, unique identity for audit, printing, customer reference, and ledger voucher linkage.

#### Validate Financial Year

The sale date must fall within an active, valid Financial Year.

**Why:** All sales must be posted to the correct accounting period. Posting to an invalid or closed period corrupts financial reports.

#### Validate Customer

The customer must exist, belong to the company, and be eligible for sale.

**Why:** Every sale must be attributed to a valid customer. Anonymous or invalid customers break receivable tracking.

#### Validate Product / Service

Every line item must reference a valid product or service. Quantities and prices must be acceptable.

**Why:** Only authorised items may be sold. Invalid lines would corrupt stock, revenue, and reporting.

#### Check Stock

For every **product** line, available stock must be equal to or greater than the sale quantity.

**Why:** The business cannot sell goods it does not have. Stock must be verified before any commitment is recorded.

Service lines do not require stock validation.

#### Reduce Stock

For every validated product line, stock is reduced immediately.

**Why:** Stock must reflect physical outflow at the moment of sale. Delayed stock reduction causes overselling and inventory inaccuracy.

#### Create Sales Items

Line records are saved for every product and service on the invoice.

**Why:** The invoice header alone is insufficient. Line detail is required for stock history, returns, VAT reporting, and customer statements.

#### Create Customer Transaction (Debit)

The customer ledger is debited for the full invoice Grand Total.

**Why:** A sale creates a receivable. The customer owes the business the full invoice amount until payments and refunds adjust the balance.

#### If Payment > 0

When any payment is collected at invoice time:

**Create Account Transaction**

The selected account receives a debit entry equal to the payment amount. Account balance increases.

**Why:** Money has entered the business. The account ledger must reflect actual cash or bank receipt.

**Create Customer Credit**

The customer ledger receives a credit entry equal to the payment amount.

**Why:** Payment reduces what the customer owes. Debit (invoice) and credit (payment) together reflect the true outstanding balance.

If payment is zero, neither entry is created. This is a pure credit sale.

#### Calculate Due

```
Due Amount = Grand Total − Paid Amount
```

Due amount cannot be negative. Minimum due is zero.

**Why:** Due amount is the single source of truth for outstanding receivable on the invoice.

#### Invoice Status

Payment status is determined from paid amount versus grand total:

| Condition | Status |
|-----------|--------|
| Paid Amount ≥ Grand Total | **Paid** |
| Paid Amount > 0 but less than Grand Total | **Partial** |
| Paid Amount = 0 | **Unpaid** |

**Why:** Status gives immediate business visibility into collection state without recalculating totals.

---

## 4. Sales Payment Workflow

This is the official sequence for receiving payment against an existing sales invoice.

```
Receive Payment
    ↓
    Validate Financial Year

Payment Date must comply with the official Financial Year Standard before payment processing begins.
Validate Account
    ↓
Increase Account Balance
    ↓
Customer Credit
    ↓
Reduce Due
    ↓
Update Invoice Status
```

### Step-by-Step Explanation

#### Receive Payment

Payment is initiated against an existing sales invoice that has outstanding due amount.

**Why:** Credit sales and partial sales require a dedicated collection process separate from invoice creation.

#### Validate Account

The receiving account must exist, be active, and belong to the company.

**Why:** Payment must be deposited to a valid cash or bank account. Invalid accounts break the account ledger.

#### Increase Account Balance

An Account Transaction debit is created for the payment amount. The account balance increases.

**Why:** Money has been received. The business asset (cash/bank) must increase accordingly.

#### Customer Credit

A Customer Transaction credit is created for the payment amount.

**Why:** Payment reduces the customer's outstanding debt. Without this credit, the customer balance would remain incorrectly high.

#### Reduce Due

```
New Due Amount = Grand Total − Total Paid (including this payment)
```

**Why:** The invoice must always show the true remaining balance after every payment.

#### Update Invoice Status

After due is recalculated:

| Condition | Status |
|-----------|--------|
| Due Amount ≤ 0 | **Paid** |
| Paid Amount > 0 and Due Amount > 0 | **Partial** |
| Paid Amount = 0 | **Unpaid** |

**Why:** Invoice status must always reflect current collection state for operations, reporting, and customer management.

---

## 5. Sales Cancel Workflow

This is the official sequence for voiding an active sales invoice.

```
Cancel Invoice
    ↓
Restore Stock
    ↓
Validate Invoice

↓

Check Active Payments

↓

If Active Payment Exists

Reject Cancellation

↓

Otherwise

Restore Stock

↓

Reverse Customer Ledger

↓

Invoice Status = Cancel
Sales Invoice cannot be cancelled while Active Sales Payments exist.

The user must first cancel all related Sales Payments.

Only after all active payments are cancelled may the Sales Invoice be cancelled.

This prevents financial inconsistency and protects Customer and Account Ledgers.

This behaviour is Business Approved and Frozen.
```

### Step-by-Step Explanation

#### Cancel Invoice

An active sales invoice is selected for cancellation. Already cancelled invoices cannot be cancelled again.

**Why:** Cancellation is a controlled void operation. Duplicate cancellation would double-reverse transactions and corrupt all balances.

#### Restore Stock

For every product line on the invoice, stock is increased by the original sold quantity.

**Why:** Cancelled sales never physically left the business in an accounting sense. Stock must return to pre-sale levels so inventory remains accurate.

Service lines do not affect stock.

#### Reverse Customer Ledger

All customer ledger entries linked to the invoice and its payments are reversed.

**Why:** A cancelled invoice must not leave any receivable on the customer account. Reversal removes the invoice debit and any payment credits associated with that invoice.

#### Reverse Account Ledger

All account ledger entries linked to payments on the cancelled invoice are reversed.

**Why:** If payment was received, that money receipt must be undone in the account ledger when the invoice is voided. Otherwise account balance would reflect money for a sale that no longer exists.

#### Invoice Status = Cancel

The invoice is marked as cancelled.

**Why:** Cancelled invoices must remain in the system for audit history but must be clearly excluded from active sales, due collection, and operational reporting.

---

## 6. Sales Return Workflow

**Status:** All Product Return and Service Return rules in this section are **FROZEN CONSTITUTION RULES**. Modification requires explicit Business Owner approval.

Validate Financial Year

Return Date must comply with the official Financial Year Standard.

Sales Return consists of two official workflows:

- **Product Return Workflow**
- **Service Return Workflow**

Both workflows return sold invoice lines against an existing sales invoice. Shared steps apply to both. Stock handling differs by workflow.

### Shared Return Sequence

```
Select Invoice
    ↓
Available Qty
    Sales Qty − Previous Return Qty = Available Qty
    ↓
Calculate Return Total
    ↓
refund_amount = Grand Total
    ↓
NO Customer Ledger
    ↓
NO Account Ledger
```

### Product Return Workflow

Product Return references an existing **Product Sales Line** on the selected Sales Invoice.

```
Select Invoice
    ↓
Select Product Sales Line
    ↓
Available Qty
    ↓
Increase Stock
    ↓
Calculate Return Total
    ↓
refund_amount = Grand Total
    ↓
NO Customer Ledger
    ↓
NO Account Ledger
```

**Product Return rules (FROZEN):**

- Must reference an existing Product Sales Line.
- Available Quantity validation is mandatory on every line.
- Stock IN is **mandatory** for every returned product line.
- Return totals must be calculated.
- Refund Balance must be created (`refund_amount` = return Grand Total).
- Must **not** create Customer Ledger or Account Ledger entries.

### Service Return Workflow

Service Return references an existing **Service Sales Line** on the selected Sales Invoice.

```
Select Invoice
    ↓
Select Service Sales Line
    ↓
Available Qty
    ↓
Calculate Return Total
    ↓
refund_amount = Grand Total
    ↓
NO Customer Ledger
    ↓
NO Account Ledger
```

**Service Return rules (FROZEN):**

- Must reference an existing Service Sales Line.
- Available Quantity validation is mandatory on every line.
- **No** stock movement is allowed. **No** inventory movement is allowed.
- Return totals must be calculated.
- Refund Balance must be created (`refund_amount` = return Grand Total).
- Must **not** create Customer Ledger or Account Ledger entries.

### Available Quantity Formula

```
Available Qty = Sales Qty − Previous Return Qty
```

Where:

- **Sales Qty** = original quantity sold on the invoice line
- **Previous Return Qty** = total quantity already returned against that line on the same invoice through active (non-cancelled) returns

Return quantity entered must be greater than zero and must not exceed Available Qty. This rule applies to **both** Product Return and Service Return lines.

### Why Available Qty Validation Is Mandatory

**Prevent over-return:** A customer cannot return more units than were sold and not already returned. Over-return would inflate stock (Product Return) or refund liability (both workflows) beyond what was originally transacted.

**Protect refund integrity:** `refund_amount` is derived from returned lines. If return quantity is wrong, refund eligibility becomes wrong.

**Maintain invoice accuracy:** Each invoice line has a finite returnable balance. The formula ensures cumulative returns never exceed the original sale.

**Audit compliance:** Every returned unit must trace back to a sold unit. Available Qty enforces that traceability.

### Product Return — Why Stock Increases

Returned products physically re-enter inventory. Stock must increase at the moment of Product Return so inventory reflects goods back on hand.

Stock increase happens at **Product Return**, not at refund. Goods and money are separate events.

### Service Return — Why Stock Does Not Change

Services are not physical inventory. Service Return records the reversal of a sold service line for refund eligibility only. No stock movement occurs.

### Why Customer Ledger Is NOT Created

Sales Return records the **return of invoice lines**, not the **return of money**.

Creating a customer ledger entry at return would prematurely adjust the customer balance before any refund is actually paid. The customer balance must only change when money moves (payment or refund), not when a return is recorded.

This rule applies to **both** Product Return and Service Return.

`refund_amount` on the sales return tracks refund eligibility separately until Sales Return Refund is processed.

### Why Account Ledger Is NOT Created

No money leaves the business at return time. Account balance is unaffected until an actual refund is paid.

Creating an account entry at return would show cash outflow that has not occurred, corrupting cash position and bank reconciliation.

This rule applies to **both** Product Return and Service Return.

---

## 6A. Sales Return Cancel Workflow

**Status:** All Sales Return Cancel rules in this section are **FROZEN CONSTITUTION RULES**. Modification requires explicit Business Owner approval.

Validate Financial Year

Cancel Date must comply with the official Financial Year Standard.

Sales Return Cancel voids an active Sales Return **only** when no Sales Return Refund has been posted.

```
Check Sales Return Status
    ↓
Check Total Refunded Amount = ZERO
    ↓
Check No Active Sales Return Refund
    ↓
If Any Refund Posted → REJECT Cancel
    ↓
Reverse Product Stock (Product Return lines only)
    ↓
No Stock Movement (Service Return lines)
    ↓
Restore Available Return Quantity
    ↓
Preserve Stored Financial Values for Audit
    ↓
Mark Sales Return Cancelled
    ↓
Preserve Audit History
```

### Cancellation Conditions (Mandatory)

Sales Return Cancel is permitted **only** when **all** of the following are true:

1. Sales Return status is **Active**.
2. Total Refunded Amount is **ZERO**.
3. No active Sales Return Refund transaction exists.

If **any** Sales Return Refund has been posted, Sales Return Cancel is **STRICTLY PROHIBITED**.

### Cancellation Effects

| Effect | Product Return | Service Return |
|--------|----------------|----------------|
| Stock | Reverse product stock (Stock **OUT**) | **No** stock movement |
| Available Return Quantity | Restored on linked sales item | Restored on linked sales item |
| Stored financial values (`grand_total`, `adjust_amount`, `refund_amount`) | Preserved for audit — excluded from active processing | Preserved for audit — excluded from active processing |
| Sales Return status | Set to **Cancel** | Set to **Cancel** |
| Audit history | Preserved — no physical deletion | Preserved — no physical deletion |

### Why Cancellation Is Restricted to Zero Refund

Once money has been refunded through Sales Return Refund, cancelling the return would corrupt refund liability, customer balance, and account balance. Refund Cancel reverses payments; it does not replace Sales Return Cancel.

### Why Status-Based Cancellation Only

Sales Return Cancel must never physically delete records. Cancelled returns remain in the system for audit, history, investigation, viewing, and printing — consistent with the Financial Year Cancelled Record Rule.

### Cancelled Sales Return — Stored Financial Values (FROZEN)

Cancelled Sales Return records are audit records. They are **never** physically deleted. They preserve business history.

The following stored values **SHALL** remain on the cancelled record and **SHALL NOT** be reset to zero:

- `grand_total`
- `adjust_amount`
- `refund_amount`

These values represent historical business values at the time of the return.

**Status = Cancel** is sufficient to deactivate the record. Cancelled Sales Returns are permanently excluded from:

- Refund workflow
- Settlement workflow
- Financial calculations
- Available refund validation
- Active business processing

### Why Customer and Account Ledgers Are NOT Affected

Sales Return Cancel reverses the return document and Product Return stock only. No money movement occurs. Customer and account ledgers are unaffected because no refund was posted.

This behaviour is Business Approved and Frozen.

---

## 7. Sales Return Refund Workflow
Validate Financial Year

Refund Date must comply with the official Financial Year Standard.
This is the official sequence for settling a recorded sales return.

### Settlement Model (FROZEN)

Sales Return Refund Settlement consists of:

1. **Invoice Adjustment Amount**
2. **Cash Refund Amount** (optional)

```
Settlement Total = Invoice Adjustment Amount + Cash Refund Amount
```

### Account Transaction Rules (FROZEN)

**Rule 1:** If Cash Refund Amount > 0 → Account Transaction **SHALL** be created.

**Rule 2:** If Cash Refund Amount = 0 → Account Transaction **SHALL NOT** be created.

**Rule 3 — Adjustment Only Refund:** Customer Ledger **SHALL** be updated. Sales Invoice **SHALL** be adjusted. Account Transaction **SHALL NOT** be created.

**Rule 4 — Cash + Adjustment Refund:** Customer Ledger **SHALL** be updated. Sales Invoice **SHALL** be adjusted. Account Transaction **SHALL** be created **only** for the Cash Refund Amount.

```
Check Remaining Refund
    ↓
Validate Settlement Total
    Settlement Total = Adjustment Amount + Cash Refund Amount
    ↓
Adjust Sales Invoice(s) (if Adjustment Amount > 0)
    ↓
Customer Credit (Settlement Total)
    ↓
If Cash Refund Amount > 0
    Check Account Balance
    Reduce Account Balance (Cash Refund Amount only)
    ↓
refund_amount −= Settlement Total
```

### Step-by-Step Explanation

#### Check Remaining Refund

Before processing, the system verifies that Settlement Total does not exceed the remaining `refund_amount` on the sales return.

**Why:** A return may be refunded in one or more instalments, but total settlements must never exceed the return Grand Total. This prevents over-payment to the customer.

#### Validate Settlement Total

Settlement Total equals Invoice Adjustment Amount plus Cash Refund Amount. Settlement Total must be greater than zero.

**Why:** Every refund transaction must settle part of the remaining refund liability. Zero settlement has no business meaning.

#### Adjust Sales Invoice (when Adjustment Amount > 0)

Outstanding sales invoice due is reduced by the Invoice Adjustment Amount. Sales Payment is **not** created.

**Why:** Adjustment applies return value against existing customer receivables without cash movement.

#### Customer Credit

A Customer Transaction credit is created for the Settlement Total (covering adjustment and cash components).

**Why:** Settlement reduces the customer's net obligation to the business (or increases what the business owes them). The customer ledger must reflect this settlement.

#### Check Account Balance (Cash Refund Amount > 0 only)

When Cash Refund Amount is greater than zero, the paying account must have sufficient balance to cover the Cash Refund Amount.

**Why:** The business cannot pay cash it does not have in the selected account. Insufficient balance would create negative cash position and broken bank records.

When Cash Refund Amount equals zero, account balance check does not apply. Adjustment-only settlement creates no account movement.

#### Reduce Account Balance (Cash Refund Amount > 0 only)

When Cash Refund Amount is greater than zero, an Account Transaction credit is created for the Cash Refund Amount only. Account balance decreases.

**Why:** Cash is leaving the business. Cash or bank balance must decrease to reflect actual outflow. Adjustment-only settlement does not move cash.

#### refund_amount −= Settlement Total

The remaining refundable balance on the sales return is reduced by Settlement Total.

**Why:** `refund_amount` is the live tracker of how much refund is still owed. Every settlement must reduce this balance so the business always knows remaining refund liability.

When `refund_amount` reaches zero, the return is fully refunded.

---

## 8. Refund Cancel Workflow

This is the official sequence for voiding a previously recorded sales return refund.

```
Reverse Customer Ledger
    ↓
Reverse Account Ledger (if Cash Refund Amount was posted)
    ↓
refund_amount += refund
```

### Step-by-Step Explanation

#### Reverse Customer Ledger

The customer credit created by the refund is reversed.

**Why:** The refund payment is voided. The customer ledger must return to the state before that refund was recorded.

#### Reverse Account Ledger

When Cash Refund Amount was posted, the account credit (balance reduction) created for the cash portion is reversed.

**Why:** The cash outflow did not legitimately occur if the refund is cancelled. Account balance must be restored. Adjustment-only refunds have no account entry to reverse.

#### refund_amount += refund

The cancelled refund amount is added back to the remaining `refund_amount` on the sales return.

**Why:** Cancelling a refund restores the business obligation to pay that amount in the future. The return must again show the correct refundable balance.

### Why Stock Is NOT Changed

Stock was already adjusted during **Product Return** when returned products came back into inventory. Service Return never affects stock.

Refund and Refund Cancel are **financial events only**. They move money, not goods or services.

Changing stock during Refund Cancel would double-count or incorrectly reverse inventory that was correctly adjusted at return time.

---

## 9. Customer Ledger Rules

The customer ledger records all financial obligations between the customer and the business.

| Transaction | Entry | Explanation |
|-------------|-------|-------------|
| Sales Invoice | **Debit** | Customer owes the business the invoice amount |
| Sales Payment | **Credit** | Customer paid; obligation reduced |
| Sales Return | **NO ENTRY** | Product or Service Return recorded; no money moved yet |
| Sales Return Refund | **Credit** | Settlement recorded; customer obligation reduced (adjustment and/or cash) |
| Refund Cancel | **Reverse Credit** | Refund voided; prior credit undone |

### Why Each Rule Exists

**Sales Invoice — Debit**

A sale creates a receivable. The customer owes money. Debit increases the customer's outstanding balance.

**Sales Payment — Credit**

Payment reduces what the customer owes. Credit decreases the outstanding balance.

**Sales Return — NO ENTRY**

Return tracks refund eligibility only. Product Return additionally restores inventory. Service Return does not affect stock. No money has changed hands. Posting a ledger entry would falsely adjust the customer balance before refund.

**Sales Return Refund — Credit**

Settlement reduces the customer's net payable to the business. Credit reflects Invoice Adjustment Amount and Cash Refund Amount together.

**Refund Cancel — Reverse Credit**

The refund is voided. The original credit must be undone so the customer balance returns to its pre-refund state.

### Customer Balance Principle

```
Customer Balance = Total Debits − Total Credits
```

(active entries only)

Every workflow must preserve this formula. No workflow may bypass the customer ledger except Sales Return, where absence of entry is the approved rule.

---

## 10. Account Ledger Rules

The account ledger records all cash and bank movements linked to sales operations.

| Transaction | Effect | Explanation |
|-------------|--------|-------------|
| Sales Payment | **Increase Account** | Money received from customer |
| Sales Return Refund (Cash Refund Amount > 0) | **Decrease Account** | Cash paid back to customer |
| Sales Return Refund (Cash Refund Amount = 0) | **NO Account** | Adjustment-only; no cash movement |
| Refund Cancel | **Reverse Account Transaction** | Cash refund voided; balance restored (when cash was posted) |

### Why Each Rule Exists

**Sales Payment — Increase Account**

Money enters the business. The cash or bank account balance must increase when payment is received.

**Sales Return Refund — Decrease Account (Cash Refund Amount > 0 only)**

When Cash Refund Amount is greater than zero, cash leaves the business. The cash or bank account balance must decrease by the Cash Refund Amount only.

**Sales Return Refund — NO Account (Cash Refund Amount = 0)**

When settlement is adjustment-only, no cash moves. Account Transaction shall not be created.

**Refund Cancel — Reverse Account Transaction**

When cash was posted, the cash refund is voided and the account must be restored to its pre-refund balance.

### Account Balance Principle

```
Account Balance = Total Debits − Total Credits
```

(active entries only)

Product Return and Service Return do not create account entries because no money moves at return time. Account ledger is affected by Sales Payment and by Sales Return Refund **Cash Refund Amount only**.

---

## 11. Stock Rules

Stock reflects physical inventory. Only goods movement affects stock. Money movement does not.

| Event | Stock Effect | Explanation |
|-------|--------------|-------------|
| Sales | **Stock OUT** | Goods leave inventory when sold |
| Product Return | **Stock IN** | Returned products re-enter inventory |
| Product Return Cancel | **Stock OUT** | Returned products removed from inventory on cancel |
| Service Return | **NO Stock** | Service lines do not affect inventory |
| Service Return Cancel | **NO Stock** | Service lines do not affect inventory |
| Refund | **NO Stock** | Money event only; return already recorded |
| Refund Cancel | **NO Stock** | Financial reversal only; inventory unchanged |

### Why Each Rule Exists

**Sales — Stock OUT**

Selling a product removes it from available inventory. Stock must decrease at sale time to prevent overselling and maintain accurate inventory valuation.

**Product Return — Stock IN**

Returned products physically come back. Stock must increase so available quantity reflects goods on hand.

**Service Return — NO Stock**

Services are not inventory. Service Return must not create any stock movement.

**Product Return Cancel — Stock OUT**

Cancelling a Product Return reverses the original Stock IN. Product stock must decrease so inventory reflects goods no longer held as returned stock.

**Service Return Cancel — NO Stock**

Cancelling a Service Return is a document reversal only. No inventory movement occurs.

**Refund — NO Stock**

Refund pays money for a return already recorded. Product Return stock was adjusted at return time. Adjusting stock again would double-count inventory.

**Refund Cancel — NO Stock**

Cancelling a refund reverses a payment, not a return. Inventory and service records remain as recorded after the original return.

### Stock Control Principle

```
Current Stock = Opening Stock − Sales OUT + Product Returns IN − Product Return Cancel OUT + Cancel Restore IN
```

Service Return never affects stock. Stock, customer ledger, and account ledger are independent but must remain consistent with their respective business events.

---

## 12. Invoice Number Rules

Every sales document type has a fixed prefix. Prefix identifies document type. Number is unique per company and financial year.

| Prefix | Document Type | Purpose |
|--------|---------------|---------|
| **SI** | Sales Invoice | Identifies a sales invoice |
| **SP** | Sales Payment | Identifies a payment receipt |
| **SR** | Sales Return | Identifies a Product Return or Service Return |
| **SRR** | Sales Return Refund | Identifies a refund payment |

### SI — Sales Invoice

Assigned when a new sales invoice is created. Links to customer debit, sales items, and stock outflow.

### SP — Sales Payment

Assigned when payment is received — either at invoice time or through standalone payment. Links to account increase and customer credit.

### SR — Sales Return

Assigned when a Product Return or Service Return is processed. Links to return totals. Product Return additionally links to stock inflow.

### SRR — Sales Return Refund

Assigned when a Sales Return Refund settlement is processed. Links to customer credit. Links to account decrease **only when Cash Refund Amount is greater than zero**.

### Why SRR Format Must Never Change

The **SRR** number format is permanently fixed and must never be altered.

**Audit continuity:** Historical refund documents, bank records, and customer statements reference SRR numbers. Format change breaks traceability.

**External reference:** Customers, auditors, and banks may hold SRR numbers as payment references. Changing format creates confusion and reconciliation failure.

**System integrity:** SRR numbers are embedded in account and customer ledger voucher references. Format change severs the link between ledger entries and source documents.

**Legal and compliance:** Tax and financial audits depend on consistent document numbering. An altered format may invalidate historical records.

**Business rule:** SRR format is frozen. SI, SP, and SR follow the standard company numbering system. SRR follows its own approved format and that format is non-negotiable without explicit business approval.

---

## 13. Financial Year Rules

All Sales transactions must comply with the official DG ERP Financial Year and Business Date standards.

This module does not define Financial Year behaviour.

The official authority for Financial Year, Business Date, Posting Date, Reporting Date, Active Financial Year, Back-Date Entry, Company Isolation, Business Date Filtering, and Cancelled Record behaviour is:

**03_DG_ERP_FINANCIAL_YEAR_AND_DATE_STANDARD.md**

Every Sales operation must follow that document without exception.

The Sales Module is responsible only for validating and applying those rules during Sales, Sales Payment, Sales Return, Sales Return Refund, and related workflows.

If any conflict exists between this document and the Financial Year Standard, the Financial Year Standard always takes precedence.


---

## 14. Validation Rules

Every validation exists to protect stock, balances, and reporting integrity.

### Customer Validation

- Customer is mandatory on every sales invoice and return.
- Customer must belong to the same company.
- Invalid or missing customer must block the transaction.

**Why:** Receivables must always be attributable to a real customer.

### Product Validation

- Product lines must reference valid products belonging to the company.
- Product must be eligible for sale.

**Why:** Only authorised inventory may be sold or returned.

### Service Validation

- Service lines must reference valid services belonging to the company.
- Services do not require stock validation.

**Why:** Service revenue must be traceable to defined service items.

### Stock Validation

- Sale quantity must not exceed current available stock for product lines.
- Stock check must occur before stock reduction.

**Why:** Prevents overselling and negative inventory.

### Quantity Validation

- All quantities must be greater than zero.
- Non-numeric or negative quantities must be rejected.

**Why:** Zero or negative quantities have no business meaning and corrupt totals.

### Available Qty Validation

- Return quantity must not exceed Available Qty.
- Available Qty = Sales Qty − Previous Return Qty.
- Validation is mandatory and must never be skipped.

**Why:** Prevents over-return, Product Return stock inflation, and excess refund liability on both Product Return and Service Return lines.

### Payment Validation

- Payment amount must be greater than zero when payment is initiated.
- Payment must not exceed remaining due amount on the invoice.
- Payment account is mandatory when payment amount is greater than zero.
- Fully paid invoices must not accept further payment.

**Why:** Prevents over-collection, unallocated receipts, and duplicate payment.

### Refund Validation

- Settlement Total must be greater than zero.
- Settlement Total must not exceed remaining `refund_amount` on the sales return.
- Refund account is mandatory **when Cash Refund Amount is greater than zero**.
- Fully refunded returns must not accept further refund.

**Why:** Prevents over-refund and uncontrolled cash outflow. Adjustment-only settlement does not require a refund account.

### Account Balance Validation

- Account must have sufficient balance before Cash Refund Amount is paid.
- Account must be active when Cash Refund Amount is greater than zero.

**Why:** Prevents negative cash position and refunds from invalid accounts. Not applicable when Cash Refund Amount equals zero.

### Financial Year Validation

- Transaction date must fall within valid Financial Year dates.
- Active Financial Year must exist.
- Cross-year restrictions must be enforced on linked documents.

**Why:** Ensures all transactions post to the correct accounting period.

---

## 15. Business Rules

All rules below are approved business policy. Every rule exists to protect financial integrity.

### Invoice Rules

1. Every sale must produce a Sales Invoice with a unique **SI** number.
2. Every invoice must have at least one line item (product or service).
3. Grand Total is the authoritative invoice value for customer debit and due calculation.
4. Payment status must always be derived from Paid Amount versus Grand Total.
5. Due Amount must never be negative.
### Sales Cancel Rules

1. A Sales Invoice with Active Sales Payments cannot be cancelled.

2. All related active Sales Payments must be cancelled first.

3. Only after payment cancellation may the Sales Invoice be cancelled.

4. Cancelled Sales Invoices are excluded from all financial calculations.

**Why:** These rules ensure every sale is complete, traceable, and financially consistent.

### Payment Rules

1. Payment reduces due amount; it never increases it.
2. Total paid across all payments must never exceed Grand Total.
3. Every payment must produce an **SP** number.
4. Every payment must create both an Account Transaction and a Customer Credit.
5. Inline payment at invoice time follows the same ledger rules as standalone payment.

**Why:** Payment rules keep receivables, cash position, and invoice status synchronised.

### Cancel Rules

1. Cancelled invoices must restore all product stock.
2. Cancelled invoices must reverse all related customer and account ledger entries.
3. Cancelled invoices must be marked with Cancel status and excluded from active operations.
4. Cancel cannot be applied twice to the same invoice.

**Why:** Cancellation must leave zero residual financial or stock effect.

### Return Rules

**Status:** All Return Rules below are **FROZEN CONSTITUTION RULES**. Modification requires explicit Business Owner approval.

1. Return must always reference an existing Sales Invoice.
2. Sales Return consists of two official workflows: **Product Return Workflow** and **Service Return Workflow**.
3. Product Return must reference an existing Product Sales Line. Service Return must reference an existing Service Sales Line.
4. Available Quantity validation is mandatory on every return line (Product Return and Service Return).
5. Product Return must increase stock for every returned product line. Stock IN is mandatory.
6. Service Return must **not** affect stock. No stock movement and no inventory movement is allowed.
7. Each Sales Return Item must contain **either** `product_id` **or** `service_id`. Exactly one must contain a value. Both cannot contain values. Both cannot be NULL.
8. Refund Balance must be created at return time. `refund_amount` must be set equal to return Grand Total.
9. Return must **not** create Customer Ledger or Account Ledger entries.
10. Every return must produce a unique **SR** number.

**Why:** Returns handle Refund Balance separately from money movement. Product Return additionally restores inventory. Service Return records service line reversal without stock effect. Both workflows prevent premature balance adjustment.

### Sales Return Cancel Rules

**Status:** All Sales Return Cancel Rules below are **FROZEN CONSTITUTION RULES**. Modification requires explicit Business Owner approval.

1. Sales Return Cancel is permitted **only** when Sales Return status is **Active**.
2. Total Refunded Amount must be **ZERO**.
3. No active Sales Return Refund transaction may exist.
4. If **any** Sales Return Refund has been posted, Sales Return Cancel is **STRICTLY PROHIBITED**.
5. Product Return Cancel must reverse product stock (Stock **OUT**) for every cancelled product return line.
6. Service Return Cancel must **not** affect stock. No stock or inventory movement is allowed.
7. Available Return Quantity on the linked sales item must be restored.
8. Stored financial values (`grand_total`, `adjust_amount`, `refund_amount`) **SHALL** be preserved for audit. They **SHALL NOT** be reset to zero. **Status = Cancel** excludes the record from refund workflow, settlement workflow, financial calculations, available refund validation, and active business processing.
9. Sales Return must be marked with **Cancel** status. Status-based cancellation only.
10. Sales Return Cancel must **never** physically delete records. Complete audit history must be preserved.
11. Sales Return Cancel must **not** create customer or account ledger entries.

**Why:** Sales Return Cancel reverses an unrefunded return only. Once refund money moves, cancellation must be blocked to protect ledger and refund integrity.

### Refund Rules

1. Refund must reference an existing Sales Return with remaining `refund_amount`.
2. Refund Settlement must create Customer Ledger credit. Account balance must decrease **only when Cash Refund Amount is greater than zero**.
3. Refund must reduce `refund_amount` by Settlement Total.
4. Refund must **not** change stock.
5. Every refund must produce a unique **SRR** number.
6. Multiple partial refunds are allowed until `refund_amount` reaches zero.

**Why:** Refund rules ensure settlement is controlled, traceable, and linked to prior return liability. Cash movement is optional; adjustment-only settlement is permitted without account movement.

### Refund Cancel Rules

1. Refund cancel must reverse customer ledger entries. Account ledger entries must be reversed **when Cash Refund Amount was posted**.
2. Refund cancel must restore `refund_amount` by the cancelled refund amount.
3. Refund cancel must **not** change stock.

**Why:** Refund cancellation restores financial state without disturbing inventory already corrected at return.

### Ledger Integrity Rules

1. Every financial movement must produce the correct ledger entry type.
2. No workflow may create ledger entries outside its approved rules.
3. Reversal must fully undo the original entry effect on balance.

**Why:** Ledger integrity is the foundation of customer statements, account reconciliation, and financial reports.

### Stock Integrity Rules

1. Stock OUT occurs only on sale and Product Return Cancel.
2. Stock IN occurs on **Product Return** and invoice cancel.
3. Service Return and Service Return Cancel never affect stock.
4. Stock never changes on refund or refund cancel.

**Why:** Separating goods movement from money movement prevents inventory corruption. Service Return is a non-stock workflow. Product Return Cancel reverses Product Return stock only.

---

## 16. Forbidden Rules

The following actions are **strictly forbidden**. Violation may cause irreversible financial damage.

### ❌ Reduce Stock during Refund

**Forbidden:** Creating any stock OUT movement when processing Sales Return Refund.

**Why:** Stock was already increased at Product Return. Reducing stock at refund would remove returned products from inventory that are physically present, creating negative or incorrect stock.
❌ Cancel a Sales Invoice while Active Sales Payments exist.

Why:

This would create inconsistent Customer Balances, Account Balances and Financial Reports.

All active Sales Payments must be cancelled before cancelling the Sales Invoice.

---

### ❌ Increase Stock during Refund Cancel

**Forbidden:** Creating any stock IN movement when cancelling a refund.

**Why:** Refund Cancel is a financial reversal only. Stock was correctly adjusted at return. Increasing stock at cancel would inflate inventory beyond physical reality.

---

### ❌ Create Customer Ledger during Sales Return

**Forbidden:** Posting any customer debit or credit when processing a sales return.

**Why:** Return records invoice line reversal and refund eligibility, not money. A customer ledger entry would falsely change the customer balance before refund is paid, causing incorrect receivables and statements.

---

### ❌ Create Account Ledger during Sales Return

**Forbidden:** Posting any account debit or credit when processing a sales return.

**Why:** No money moves at return. An account entry would show false cash movement and break bank reconciliation.

---

### ❌ Skip Available Qty Validation

**Forbidden:** Processing a return line without verifying Available Qty.

**Why:** Over-return inflates Product Return stock, creates excess refund liability, and breaks the link between sold and returned quantities on both Product Return and Service Return lines.

---

### ❌ Cancel Sales Return after Refund Posted

**Forbidden:** Cancelling a Sales Return when Total Refunded Amount is greater than zero or when any active Sales Return Refund exists.

**Why:** Refund money has already moved. Cancelling the return would corrupt refund liability, customer balance, account balance, and audit trail. Use Refund Cancel to reverse payments — not Sales Return Cancel.

---

### ❌ Physically Delete Sales Return Records

**Forbidden:** Permanently deleting Sales Return or Sales Return Item records.

**Why:** Cancelled returns must remain for audit, history, investigation, viewing, and printing. Only status-based cancellation is permitted.

---

### ❌ Modify adjust_amount or refund_amount Manually

**Forbidden:** Directly editing `adjust_amount` or `refund_amount` outside the approved Sales Return Refund, Refund Cancel, and Sales Return Cancel workflows.

**Why:** `adjust_amount` and `refund_amount` are system-controlled refund balance fields. Manual change bypasses audit trail and may cause over-refund or under-refund.

---

### ❌ Change Reference Types

**Forbidden:** Altering ledger reference types that link transactions to their source documents (sales invoice, sales payment, sales return refund, and their cancel variants).

**Why:** Reference types connect ledger entries to business documents. Changing them breaks audit trail, reversal logic, and report drill-down.

---

### ❌ Change Invoice Number Format

**Forbidden:** Altering the approved numbering format for **SI**, **SP**, **SR**, or **SRR**, with **SRR** being absolutely frozen.

**Why:** Document numbers are permanent business identifiers used in ledgers, bank records, customer communication, and audits. Format change severs historical continuity.

---

### ❌ Change StockService Behaviour

**Forbidden:** Modifying the approved stock increase, decrease, and movement recording behaviour defined for sales operations.

**Why:** StockService is the single authority for inventory movement. Changed behaviour causes stock mismatch across sales, returns, and cancellations.

---

### ❌ Change CustomerTransactionService Behaviour

**Forbidden:** Modifying the approved customer debit, credit, and reversal behaviour defined for sales operations.

**Why:** CustomerTransactionService is the single authority for receivables. Changed behaviour causes wrong customer balances and statements.

---

### ❌ Change AccountBalanceService Behaviour

**Forbidden:** Modifying the approved account debit, credit, balance check, and reversal behaviour defined for sales operations.

**Why:** AccountBalanceService is the single authority for cash and bank balances. Changed behaviour causes wrong account balances and reconciliation failure.

---

## 17. Frozen Logic

The following workflows are **BUSINESS APPROVED** and **FROZEN**.

| # | Frozen Workflow |
|---|-----------------|
| 1 | Sales (Invoice Workflow) |
| 2 | Sales Payment |
| 3 | Sales Cancel |
| 4 | Product Return Workflow |
| 5 | Service Return Workflow |
| 6 | Sales Return Cancel |
| 7 | Sales Return Refund |
| 8 | Refund Cancel |

### Frozen Product Return and Service Return Rules

All Product Return and Service Return rules defined in **§6 Sales Return Workflow** and **§15 Return Rules** are **FROZEN CONSTITUTION RULES**. This includes:

- Product Return Workflow — invoice reference, Available Quantity validation, mandatory Stock IN, return totals, Refund Balance, no ledger entries, and Sales Return Item `product_id` line identity.
- Service Return Workflow — invoice reference, Available Quantity validation, no stock or inventory movement, return totals, Refund Balance, no ledger entries, and Sales Return Item `service_id` line identity.

Modification to any frozen Product Return or Service Return rule **requires explicit Business Owner approval** before implementation.

### Frozen Sales Return Cancel Rules

All Sales Return Cancel rules defined in **§6A Sales Return Cancel Workflow** and **§15 Sales Return Cancel Rules** are **FROZEN CONSTITUTION RULES**. Modification requires explicit Business Owner approval.

### Frozen Refund Balance Storage Rules

The following rules for `sales_returns.adjust_amount` and `sales_returns.refund_amount` are **FROZEN CONSTITUTION RULES**:

1. `sales_returns.adjust_amount` **SHALL** be stored.
2. `sales_returns.refund_amount` **SHALL** be stored.
3. `adjust_amount` represents the **Total Refunded Amount**.
4. `refund_amount` represents the **Remaining Refund Balance**.
5. Both fields **SHALL** be maintained automatically by the system.
6. Both fields **SHALL NOT** be edited manually.
7. Remaining Refund validation **MAY** calculate values from active refund records for verification, but the official business fields remain `adjust_amount` and `refund_amount` stored on `sales_returns`.

Modification requires explicit Business Owner approval.

### Approval Requirement

Any modification to step order, ledger effect, stock effect, validation rule, or balance calculation within these workflows **requires explicit business approval** before implementation.

Developer convenience, performance optimisation, or UI improvement does **not** authorise logic change.

### Consequences of Unapproved Change

| Risk | Result |
|------|--------|
| Wrong Stock | Inventory counts become inaccurate; overselling or phantom stock occurs |
| Wrong Customer Balance | Customer statements show incorrect dues; collection decisions become wrong |
| Wrong Account Balance | Cash and bank balances do not match reality; reconciliation fails |
| Wrong Due | Invoices show incorrect outstanding amounts; over-payment or under-collection occurs |
| Wrong Refund | Customers receive incorrect refund amounts; `refund_amount` tracker becomes unreliable |
| Wrong Financial Reports | Revenue, receivables, and period reports become untrustworthy; audit fails |

### Frozen Logic Principle

These eight workflows define the financial truth of the Sales Module. They are not implementation details. They are business law.

---

## 18. Future Development Rules

Development may continue on the Sales Module under the following conditions.

### Permitted Changes

| Area | Permitted |
|------|-----------|
| UI | May change |
| CSS | May change |
| Blade templates | May change |
| Validation | May improve (stricter is permitted; weaker is not) |
| Services | May be refactored (behaviour must remain identical) |
| Queries | May be optimised (results must remain identical) |

### Non-Negotiable Constraint

**Financial Business Logic must remain unchanged unless explicitly approved.**

This includes:

- Workflow step order
- Ledger entry type and timing
- Stock effect and timing
- Balance formulas
- `adjust_amount` and `refund_amount` rules
- Invoice number formats (especially SRR)
- Forbidden rules
- Frozen workflows

### Development Principle

Improve how the system works internally and visually. Never change what the system means financially without business approval.

### Future Development Rule Summary

```
UI, CSS, Blade     → May change
Validation         → May improve (never weaken)
Services, Queries  → May refactor (same business result)
Financial Logic    → Frozen unless business approves
```

---

## Document Authority

This document is the **official Business Constitution** for the DG ERP Sales Module.

It defines **business logic only**. It is not source code. It does not describe programming language, framework syntax, or technical implementation.

All developers, operators, auditors, and automated systems must comply with this document.

When business requirements change, this document must be updated and re-approved **before** any logic change is implemented.

---

**End of Document**

---

# Sales Return Refund Business Workflow Standard

Status

FROZEN

Version

2.0

====================================================

Business Principle

Sales Return is the Source Document.

Sales Return Grand Return Amount NEVER changes.

sales_returns.adjust_amount SHALL be stored.

sales_returns.refund_amount SHALL be stored.

adjust_amount represents the Total Refunded Amount.

refund_amount represents the Remaining Refund Balance.

Both fields SHALL be maintained automatically by the system.

Both fields SHALL NOT be edited manually.

Remaining Refund validation MAY calculate values from active refund records for verification, but the official business fields remain adjust_amount and refund_amount stored on sales_returns.

Sales Return Cancel is permitted ONLY when Total Refunded Amount is ZERO and no active Sales Return Refund exists.

If any Refund has been posted, Sales Return Cancel is STRICTLY PROHIBITED.

Sales Return Cancel must never physically delete records. Status-based cancellation only.

====================================================

Refund Principle

One Sales Return

↓

Many Sales Return Refund Transactions

are allowed.

Every Refund Transaction is independent.

====================================================

Refund Amount

Sales Return Total Refundable Amount

=

Grand Return Amount

====================================================

Total Refunded Amount

Stored on sales_returns.adjust_amount

The system maintains adjust_amount automatically from active Sales Return Refund settlements.

Verification MAY recalculate Total Refunded Amount from active refund records.

====================================================

Remaining Refund Balance

Stored on sales_returns.refund_amount

Remaining Refund Balance

=

Sales Return Grand Return Amount

-

Total Refunded Amount

The system maintains refund_amount automatically.

Verification MAY recalculate Remaining Refund Balance from active refund records for validation. The official business fields remain adjust_amount and refund_amount stored on sales_returns.

====================================================

Settlement

Each Refund Transaction contains

Settlement Amount

Settlement Amount

=

Adjustment Amount

+

Cash Refund Amount

====================================================

Validation

Settlement Amount

<=

Remaining Refund

Adjustment Amount

<=

Outstanding Due

Cash Refund Amount

>= 0

====================================================

Adjustment

One Refund Transaction

may adjust

one

or

many

Sales Invoices.

====================================================

Invoice Update

Adjustment

updates

Invoice Due

Invoice Payment Status

Do NOT create Sales Payment.

====================================================

Cash Refund

Cash Refund is optional.

If Cash Refund > 0

Create

Account Transaction

Customer Transaction

If Cash Refund = 0

Do NOT create Account Transaction.

====================================================

Refund Header

sales_return_refunds

stores

Settlement Amount

Adjustment Amount

Cash Amount

Reference

Attachment

Account

Audit

====================================================

Refund Details

sales_return_refund_adjustments

stores

Invoice

Adjustment Amount

====================================================

Refund Cancel

Refund Cancel must

Reverse

Invoice Adjustment

Restore

Invoice Due

Recalculate

Payment Status

Reverse

Cash Transaction

(if exists)

Reverse

Customer Transaction

Mark Refund

Cancelled

====================================================

Business Rules

✓ Sales Return Grand Return Amount never changes.

✓ Sales Return Cancel permitted only when no Refund posted.

✓ adjust_amount and refund_amount are stored on sales_returns and maintained automatically by the system.

✓ Multiple Refund Transactions are allowed.

✓ One Refund may adjust multiple invoices.

✓ Cash Refund is optional.

✓ Adjustment never creates Sales Payment.

✓ Adjustment never creates Account Transaction.

✓ All operations must be company scoped.

✓ All operations must follow Financial Year rules.

====================================================
