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

**Sales Return** records the physical return of sold products back into inventory against an existing sales invoice.

Business effect:

- Available return quantity is validated before processing.
- Product stock increases.
- Return totals are calculated.
- `refund_amount` is set equal to the return Grand Total.
- **No** Customer Ledger entry is created.
- **No** Account Ledger entry is created.

Sales Return handles goods and return value tracking only. It does not move money. Money movement is handled exclusively by Sales Return Refund.

#### Sales Return Refund

**Sales Return Refund** pays money back to the customer for a previously recorded sales return.

Business effect:

- Remaining refund amount is validated.
- Account balance is validated and reduced.
- Customer ledger receives a credit entry.
- `refund_amount` on the sales return is reduced by the refund paid.

This workflow represents actual cash or bank outflow to the customer.

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

The Account Ledger tracks cash and bank balances. Sales Payment increases account balance. Sales Return Refund decreases account balance. Sales Return creates no entry.

#### Stock Control

Stock Control ensures product quantity is accurate. Sales reduces stock. Sales Return increases stock. Refund and Refund Cancel do not affect stock because money movement is separate from goods movement.

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
Sales Return Refund ────► Account Transactions (Credit on refund)
Sales Return Refund ────► Customer Transactions (Credit on refund)

Sales Items (products) ─► Stock Movements (OUT on sale; IN on return or cancel)
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

- Not updated during normal sale flow. Return quantities are tracked through Sales Return Items against the original sales item.

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

**Purpose:** Master record of a product return against a sales invoice. Holds return number, return date, totals, and `refund_amount` (amount still eligible to be refunded).

**Written when:**

- A Sales Return is processed.

**Updated when:**

- `refund_amount` is reduced when a Sales Return Refund is paid.
- `refund_amount` is increased when a Refund Cancel is processed.

**Connected to:** Sales Invoice, Customer, Financial Year, Sales Return Items, Sales Return Refunds.

### Sales Return Items

**Purpose:** Line-level detail of each returned product. Holds returned quantity, unit price, VAT, and line total linked to the original sales item.

**Written when:**

- Each return line is processed during the Sales Return Workflow.

**Connected to:** Sales Return, Sales Item, Product.

### Sales Return Refunds

**Purpose:** Records actual money refunded to the customer for a sales return.

**Written when:**

- A Sales Return Refund is processed.

**Updated when:**

- Refund is cancelled through Refund Cancel Workflow.

**Connected to:** Sales Return, Customer, Account, Financial Year.

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
- Sales Return Refund is paid → **Credit** (account decreases)
- Sales Cancel, payment cancel, or Refund Cancel → **Reverse** entries

**Not written when:**

- Sales Return is processed.

**Connected to:** Account, Financial Year, reference document (payment or refund).

### Stock Movements

**Purpose:** Audit trail of every stock change. Records product, quantity, direction, reference document, and before/after stock levels.

**Written when:**

- Sales Invoice is created → Stock **OUT** for each product line
- Sales Return is processed → Stock **IN** for each returned product line
- Sales Invoice is cancelled → Stock **IN** (restored)

**Not written when:**

- Sales Return Refund is paid
- Refund Cancel is processed

**Connected to:** Product, Financial Year, reference document.

### Write Sequence Summary

| Business Event | Tables Written |
|----------------|----------------|
| Sales Invoice | Sales Invoice, Sales Items, Stock Movements, Customer Transactions; optionally Sales Payments, Account Transactions |
| Sales Payment | Sales Payments, Account Transactions, Customer Transactions; Sales Invoice updated |
| Sales Cancel | Stock Movements (restore), reverse Customer and Account Transactions; Sales Invoice and Sales Payments updated |
| Sales Return | Sales Returns, Sales Return Items, Stock Movements |
| Sales Return Refund | Sales Return Refunds, Account Transactions, Customer Transactions; Sales Returns updated |
| Refund Cancel | Reverse Customer and Account Transactions; Sales Return Refunds and Sales Returns updated |

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
Validate Financial Year

Return Date must comply with the official Financial Year Standard.
This is the official sequence for returning sold products against an existing invoice.

```
Select Invoice
    ↓
Available Qty
    Sales Qty − Previous Return Qty = Available Qty
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

### Available Quantity Formula

```
Available Qty = Sales Qty − Previous Return Qty
```

Where:

- **Sales Qty** = original quantity sold on the invoice line
- **Previous Return Qty** = total quantity already returned against that line on the same invoice through active (non-cancelled) returns

Return quantity entered must be greater than zero and must not exceed Available Qty.

### Why Available Qty Validation Is Mandatory

**Prevent over-return:** A customer cannot return more units than were sold and not already returned. Over-return would inflate stock and refund liability beyond what was originally transacted.

**Protect refund integrity:** `refund_amount` is derived from returned goods. If return quantity is wrong, refund eligibility becomes wrong.

**Maintain invoice accuracy:** Each invoice line has a finite returnable balance. The formula ensures cumulative returns never exceed the original sale.

**Audit compliance:** Every returned unit must trace back to a sold unit. Available Qty enforces that traceability.

### Why Stock Increases

Returned goods physically re-enter inventory. Stock must increase at the moment of return so inventory reflects goods back on hand.

Stock increase happens at **Sales Return**, not at refund. Goods and money are separate events.

### Why Customer Ledger Is NOT Created

Sales Return records the **return of goods**, not the **return of money**.

Creating a customer ledger entry at return would prematurely adjust the customer balance before any refund is actually paid. The customer balance must only change when money moves (payment or refund), not when goods move.

`refund_amount` on the sales return tracks refund eligibility separately until Sales Return Refund is processed.

### Why Account Ledger Is NOT Created

No money leaves the business at return time. Account balance is unaffected until an actual refund is paid.

Creating an account entry at return would show cash outflow that has not occurred, corrupting cash position and bank reconciliation.

---

## 7. Sales Return Refund Workflow
Validate Financial Year

Refund Date must comply with the official Financial Year Standard.
This is the official sequence for paying money back to a customer for a recorded sales return.

```
Check Remaining Refund
    ↓
Check Account Balance
    ↓
Reduce Account Balance
    ↓
Customer Credit
    ↓
refund_amount −= refund
```

### Step-by-Step Explanation

#### Check Remaining Refund

Before processing, the system verifies that the requested refund amount does not exceed the remaining `refund_amount` on the sales return.

**Why:** A return may be refunded in one or more instalments, but total refunds must never exceed the return Grand Total. This prevents over-payment to the customer.

#### Check Account Balance

The paying account must have sufficient balance to cover the refund amount.

**Why:** The business cannot refund money it does not have in the selected account. Insufficient balance would create negative cash position and broken bank records.

#### Reduce Account Balance

An Account Transaction credit is created for the refund amount. Account balance decreases.

**Why:** Money is leaving the business. Cash or bank balance must decrease to reflect actual outflow.

#### Customer Credit

A Customer Transaction credit is created for the refund amount.

**Why:** Refunding money to the customer reduces their net obligation to the business (or increases what the business owes them). The customer ledger must reflect this financial settlement.

#### refund_amount −= refund

The remaining refundable balance on the sales return is reduced by the amount just paid.

**Why:** `refund_amount` is the live tracker of how much refund is still owed. Every refund payment must reduce this balance so the business always knows remaining refund liability.

When `refund_amount` reaches zero, the return is fully refunded.

---

## 8. Refund Cancel Workflow

This is the official sequence for voiding a previously recorded sales return refund.

```
Reverse Customer Ledger
    ↓
Reverse Account Ledger
    ↓
refund_amount += refund
```

### Step-by-Step Explanation

#### Reverse Customer Ledger

The customer credit created by the refund is reversed.

**Why:** The refund payment is voided. The customer ledger must return to the state before that refund was recorded.

#### Reverse Account Ledger

The account credit (balance reduction) created by the refund is reversed.

**Why:** The money outflow did not legitimately occur if the refund is cancelled. Account balance must be restored.

#### refund_amount += refund

The cancelled refund amount is added back to the remaining `refund_amount` on the sales return.

**Why:** Cancelling a refund restores the business obligation to pay that amount in the future. The return must again show the correct refundable balance.

### Why Stock Is NOT Changed

Stock was already adjusted during **Sales Return** when goods came back into inventory.

Refund and Refund Cancel are **financial events only**. They move money, not goods.

Changing stock during Refund Cancel would double-count or incorrectly reverse inventory that was correctly adjusted at return time.

---

## 9. Customer Ledger Rules

The customer ledger records all financial obligations between the customer and the business.

| Transaction | Entry | Explanation |
|-------------|-------|-------------|
| Sales Invoice | **Debit** | Customer owes the business the invoice amount |
| Sales Payment | **Credit** | Customer paid; obligation reduced |
| Sales Return | **NO ENTRY** | Goods returned; no money moved yet |
| Sales Return Refund | **Credit** | Money returned to customer; obligation reduced |
| Refund Cancel | **Reverse Credit** | Refund voided; prior credit undone |

### Why Each Rule Exists

**Sales Invoice — Debit**

A sale creates a receivable. The customer owes money. Debit increases the customer's outstanding balance.

**Sales Payment — Credit**

Payment reduces what the customer owes. Credit decreases the outstanding balance.

**Sales Return — NO ENTRY**

Return tracks goods and refund eligibility only. No money has changed hands. Posting a ledger entry would falsely adjust the customer balance before refund.

**Sales Return Refund — Credit**

Money is returned to the customer. Their net payable to the business decreases. Credit reflects this financial settlement.

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
| Sales Return Refund | **Decrease Account** | Money paid back to customer |
| Refund Cancel | **Reverse Account Transaction** | Refund voided; balance restored |

### Why Each Rule Exists

**Sales Payment — Increase Account**

Money enters the business. The cash or bank account balance must increase when payment is received.

**Sales Return Refund — Decrease Account**

Money leaves the business. The cash or bank account balance must decrease when refund is paid.

**Refund Cancel — Reverse Account Transaction**

The refund payment is voided. The account must be restored to its pre-refund balance.

### Account Balance Principle

```
Account Balance = Total Debits − Total Credits
```

(active entries only)

Sales Return does not create account entries because no money moves at return time. Only payment and refund workflows affect the account ledger.

---

## 11. Stock Rules

Stock reflects physical inventory. Only goods movement affects stock. Money movement does not.

| Event | Stock Effect | Explanation |
|-------|--------------|-------------|
| Sales | **Stock OUT** | Goods leave inventory when sold |
| Sales Return | **Stock IN** | Returned goods re-enter inventory |
| Refund | **NO Stock** | Money event only; goods already returned |
| Refund Cancel | **NO Stock** | Financial reversal only; goods unchanged |

### Why Each Rule Exists

**Sales — Stock OUT**

Selling a product removes it from available inventory. Stock must decrease at sale time to prevent overselling and maintain accurate inventory valuation.

**Sales Return — Stock IN**

Returned products physically come back. Stock must increase so available quantity reflects goods on hand.

**Refund — NO Stock**

Refund pays money for goods already returned. Stock was adjusted at return time. Adjusting stock again would double-count inventory.

**Refund Cancel — NO Stock**

Cancelling a refund reverses a payment, not a goods movement. Inventory remains as recorded after the original return.

### Stock Control Principle

```
Current Stock = Opening Stock − Sales OUT + Returns IN + Cancel Restore IN
```

Stock, customer ledger, and account ledger are independent but must remain consistent with their respective business events.

---

## 12. Invoice Number Rules

Every sales document type has a fixed prefix. Prefix identifies document type. Number is unique per company and financial year.

| Prefix | Document Type | Purpose |
|--------|---------------|---------|
| **SI** | Sales Invoice | Identifies a sales invoice |
| **SP** | Sales Payment | Identifies a payment receipt |
| **SR** | Sales Return | Identifies a product return |
| **SRR** | Sales Return Refund | Identifies a refund payment |

### SI — Sales Invoice

Assigned when a new sales invoice is created. Links to customer debit, sales items, and stock outflow.

### SP — Sales Payment

Assigned when payment is received — either at invoice time or through standalone payment. Links to account increase and customer credit.

### SR — Sales Return

Assigned when a sales return is processed. Links to stock inflow and return totals.

### SRR — Sales Return Refund

Assigned when refund money is paid to the customer. Links to account decrease and customer credit.

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

**Why:** Prevents over-return, stock inflation, and excess refund liability.

### Payment Validation

- Payment amount must be greater than zero when payment is initiated.
- Payment must not exceed remaining due amount on the invoice.
- Payment account is mandatory when payment amount is greater than zero.
- Fully paid invoices must not accept further payment.

**Why:** Prevents over-collection, unallocated receipts, and duplicate payment.

### Refund Validation

- Refund amount must be greater than zero.
- Refund must not exceed remaining `refund_amount` on the sales return.
- Refund account is mandatory.
- Fully refunded returns must not accept further refund.

**Why:** Prevents over-refund and uncontrolled cash outflow.

### Account Balance Validation

- Account must have sufficient balance before refund is paid.
- Account must be active.

**Why:** Prevents negative cash position and refunds from invalid accounts.

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

1. Return must always reference an existing Sales Invoice.
2. Only product lines may be returned. Services are not returnable through stock return.
3. Available Qty validation is mandatory on every return line.
4. Return must increase stock for every returned product line.
5. `refund_amount` must be set equal to return Grand Total at the time of return.
6. Return must **not** create customer or account ledger entries.
7. Every return must produce a unique **SR** number.

**Why:** Returns handle goods and refund eligibility separately from money movement, preventing premature balance adjustment.

### Refund Rules

1. Refund must reference an existing Sales Return with remaining `refund_amount`.
2. Refund must reduce account balance and create customer credit.
3. Refund must reduce `refund_amount` by the amount paid.
4. Refund must **not** change stock.
5. Every refund must produce a unique **SRR** number.
6. Multiple partial refunds are allowed until `refund_amount` reaches zero.

**Why:** Refund rules ensure money outflow is controlled, traceable, and linked to prior return liability.

### Refund Cancel Rules

1. Refund cancel must reverse customer and account ledger entries.
2. Refund cancel must restore `refund_amount` by the cancelled refund amount.
3. Refund cancel must **not** change stock.

**Why:** Refund cancellation restores financial state without disturbing inventory already corrected at return.

### Ledger Integrity Rules

1. Every financial movement must produce the correct ledger entry type.
2. No workflow may create ledger entries outside its approved rules.
3. Reversal must fully undo the original entry effect on balance.

**Why:** Ledger integrity is the foundation of customer statements, account reconciliation, and financial reports.

### Stock Integrity Rules

1. Stock OUT occurs only on sale.
2. Stock IN occurs on return and invoice cancel.
3. Stock never changes on refund or refund cancel.

**Why:** Separating goods movement from money movement prevents inventory corruption.

---

## 16. Forbidden Rules

The following actions are **strictly forbidden**. Violation may cause irreversible financial damage.

### ❌ Reduce Stock during Refund

**Forbidden:** Creating any stock OUT movement when processing Sales Return Refund.

**Why:** Stock was already increased at Sales Return. Reducing stock at refund would remove returned goods from inventory that are physically present, creating negative or incorrect stock.
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

**Why:** Return records goods, not money. A customer ledger entry would falsely change the customer balance before refund is paid, causing incorrect receivables and statements.

---

### ❌ Create Account Ledger during Sales Return

**Forbidden:** Posting any account debit or credit when processing a sales return.

**Why:** No money moves at return. An account entry would show false cash movement and break bank reconciliation.

---

### ❌ Skip Available Qty Validation

**Forbidden:** Processing a return line without verifying Available Qty.

**Why:** Over-return inflates stock, creates excess refund liability, and breaks the link between sold and returned quantities.

---

### ❌ Modify refund_amount Manually

**Forbidden:** Directly editing `refund_amount` outside the approved Sales Return Refund and Refund Cancel workflows.

**Why:** `refund_amount` is the system-controlled tracker of remaining refund obligation. Manual change bypasses audit trail and may cause over-refund or under-refund.

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
| 4 | Sales Return |
| 5 | Sales Return Refund |
| 6 | Refund Cancel |

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

These six workflows define the financial truth of the Sales Module. They are not implementation details. They are business law.

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
- `refund_amount` rules
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

Sales Return amount NEVER changes.

Sales Return NEVER stores Remaining Refund.

Sales Return NEVER stores Running Balance.

Sales Return NEVER stores Refunded Amount.

Sales Return is immutable after posting.

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

Total Refunded

Total Refunded

=

SUM(

sales_return_refunds.refund_amount

)

WHERE

status = Active

====================================================

Remaining Refund

Remaining Refund

=

Sales Return Grand Return Amount

-

Total Refunded

System must ALWAYS calculate Remaining Refund.

Never trust stored remaining values.

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

✓ Sales Return never changes.

✓ Remaining Refund is always calculated.

✓ Multiple Refund Transactions are allowed.

✓ One Refund may adjust multiple invoices.

✓ Cash Refund is optional.

✓ Adjustment never creates Sales Payment.

✓ Adjustment never creates Account Transaction.

✓ All operations must be company scoped.

✓ All operations must follow Financial Year rules.

====================================================
