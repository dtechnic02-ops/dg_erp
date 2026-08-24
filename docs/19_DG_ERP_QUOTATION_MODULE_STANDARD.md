# DG ERP
# 19_DG_ERP_QUOTATION_MODULE_STANDARD.md

Version : 1.0

Status : FINAL (FROZEN)

Owner : DG ERP

Document Type : Business / Module Standard

Applies To :

- Quotation
- Quotation Items
- Quotation Approval
- Quotation Print
- Quotation to Sales Invoice Conversion
- Sales / CRM navigation entry for Quotation

---

# 1. PURPOSE

This document defines the official business, workflow, data-integrity, permission, date, conversion, and audit rules for the DG ERP Quotation Module.

Quotation is a pre-sales commercial document used to offer products/services and prices to a customer before a Sales Invoice is created.

Quotation is NOT a financial transaction.

The Quotation Module must remain independent from financial posting until a real Sales Invoice is generated through the existing Sales workflow.

---

# 2. CORE PRINCIPLE

Quotation is a commercial offer only.

Creating, editing, approving, deleting, viewing, or printing a Quotation MUST NOT directly:

- Reduce or increase stock
- Create StockMovement
- Create COGS
- Create or change Inventory Valuation
- Change Customer balance
- Create CustomerTransaction
- Change Cash/Bank operational account balances
- Create AccountTransaction
- Create AccountingEntry
- Post to Accounting Core
- Affect VAT ledger/report
- Affect Profit & Loss
- Affect Trial Balance
- Affect Balance Sheet

Financial effects begin only after a valid Sales Invoice is created through the existing Sales workflow.

This rule is FINAL and FROZEN.

---

# 3. QUOTATION WORKFLOW

The approved lifecycle is:

Draft
→ Approved
→ Converted to Sales Invoice

No workflow stage may be silently skipped.

## 3.1 Draft

A Draft Quotation may be:

- Created
- Edited
- Deleted
- Viewed
- Printed
- Approved

Draft is the only editable state.

## 3.2 Approved

After approval:

- Edit is BLOCKED
- Delete is BLOCKED
- Commercial content is locked
- View is allowed
- Print is allowed
- Generate Invoice is allowed

Approval itself does NOT create an invoice and does NOT create any financial posting.

## 3.3 Converted

After successful Sales Invoice generation:

- Quotation remains as historical evidence
- Edit is BLOCKED
- Delete is BLOCKED
- A second Invoice generation is BLOCKED
- The generated Sales Invoice reference is retained
- The user may view/open the linked Sales Invoice

Converted Quotation history must remain available for audit and traceability.

---

# 4. QUOTATION NUMBERING

Quotation Number is independent from Sales Invoice Number.

Quotation Number must use the approved Quotation numbering logic.

Quotation Number MUST NOT be reused as the Sales Invoice number.

When a Sales Invoice is generated, the Sales Invoice number MUST be generated through the existing Sales Invoice numbering architecture.

---

# 5. QUOTATION DATE RULE

Quotation uses its own `quotation_date`.

`quotation_date` is the authoritative commercial document date for the Quotation.

It must never be replaced by:

- created_at
- updated_at
- approved_at
- converted_at
- server time
- login time

Quotation Date is NOT automatically the Sales Invoice Business Date.

When an approved Quotation is converted, the Sales Invoice must continue to use the existing Sales Business Date workflow and existing Financial Year validation.

---

# 6. AD / BS DATE RULE

DG ERP Financial Year and Date Standard v1.1 remains fully applicable.

For Nepal companies (`Country.iso_code = NP`):

- Quotation Date in AD remains authoritative
- BS date is automatically derived from the AD Quotation Date
- BS is read-only / derived
- Central `NepaliDateService` must be used
- No module-specific AD→BS algorithm is allowed

For non-Nepal companies:

- Nepal BS controls are hidden or not applicable

The BS value is only a calendar representation.

It is NOT a second independent Business Date.

No independent BS database authority is permitted.

---

# 7. QUOTATION DATA

The implemented Quotation data model may include the following approved concepts, using the actual application schema:

- company_id
- financial_year_id where required by the implemented design
- quotation_no
- quotation_date
- customer_id
- reference_no
- description / remarks / note
- subtotal
- discount
- VAT/tax amounts
- grand_total
- status
- created_by
- approved_by
- approved_at
- converted_by
- converted_at
- sales_invoice_id

Quotation Items may include:

- product/service identity
- item type
- quantity
- unit price
- VAT rate
- VAT amount
- discount/line adjustments where implemented
- line amount
- line total
- description / reference where implemented

Documentation must follow the actual schema and implementation. Fields that do not exist must not be invented.

---

# 8. CALCULATION RULE

Quotation calculations must follow the approved commercial calculation rules used by the existing Sales architecture where applicable.

This includes:

- Quantity
- Unit Price
- Line Amount
- VAT
- Discount
- Subtotal
- Grand Total

Quotation calculations are commercial calculations only.

They must NOT create stock, ledger, accounting, customer balance, or VAT posting effects.

---

# 9. CUSTOMER / PRODUCT / SERVICE VALIDATION

Before saving or approving a Quotation, the system must validate applicable company ownership and data integrity.

At minimum:

- Customer belongs to the current Company
- Product belongs to the current Company
- Service belongs to the current Company
- VAT/tax data belongs to the allowed company/context where applicable
- Quantities and prices satisfy existing validation rules
- Cross-company references are rejected

Company isolation is mandatory.

---

# 10. APPROVAL RULE

Only a Draft Quotation may be approved.

Approval must:

- Validate Company ownership
- Validate quotation integrity
- Validate customer/item references
- Change status to Approved
- Record approval actor/time using the implemented audit fields
- Lock the Quotation from normal Edit/Delete

Approval must NOT:

- Post Accounting Core
- Change stock
- Change customer balance
- Create financial transactions
- Create a Sales Invoice automatically

A future change to automatic invoice creation on approval requires explicit Business Owner approval.

---

# 11. GENERATE INVOICE RULE

An Approved Quotation may generate ONE real Sales Invoice.

The Generate Invoice action must use the existing Sales workflow rather than creating a parallel Sales implementation inside Quotation.

The generated Sales Invoice must preserve the existing Sales architecture for:

- Invoice numbering
- Sales Business Date
- Active Financial Year validation
- Customer validation
- Product/service validation
- Quantity/price/VAT/discount calculation
- Sales Items
- Stock reduction
- Inventory Valuation
- COGS snapshot
- CustomerTransaction / Customer balance
- VAT accounting
- Accounting Core posting
- Atomicity

Quotation must not duplicate or replace these rules.

---

# 12. INVOICE BUSINESS DATE / FINANCIAL YEAR RULE

Quotation Date must NOT silently become the Sales Invoice Business Date.

When generating an Invoice:

- Sales uses its existing authoritative `sale_date`
- `sale_date` must belong to the active Financial Year
- Existing Financial Year validation must remain unchanged
- Sales posting/stock/VAT/ledger continue to use `sale_date`

The following MUST NOT become Sales Business Date:

- quotation created_at
- quotation updated_at
- quotation approved_at
- quotation converted_at

---

# 13. CONVERSION ATOMICITY

Quotation → Sales Invoice conversion must be atomic.

Approved conceptual flow:

Approved Quotation
→ Existing Sales pipeline executes
→ Real Sales Invoice created
→ Sales Items / Stock / COGS / Customer / VAT / Accounting complete successfully
→ Quotation `sales_invoice_id` or equivalent link saved
→ Quotation marked Converted

If ANY Sales step fails:

- Entire conversion must rollback
- Quotation remains Approved
- Conversion link remains empty
- No partial Sales Invoice remains
- No partial Sales Item remains
- No partial StockMovement remains
- No partial COGS remains
- No partial CustomerTransaction remains
- No partial AccountingEntry remains

Partial conversion is prohibited.

---

# 14. DUPLICATE CONVERSION PROTECTION

A Quotation may generate only ONE Sales Invoice.

Backend protection is mandatory.

If a Quotation is already converted or already has a Sales Invoice link:

- Another Generate Invoice request must be rejected
- A duplicate Sales Invoice must not be created

UI hiding alone is not sufficient.

---

# 15. EDIT PROTECTION

Only Draft Quotation may be edited.

Approved and Converted Quotation direct editing is prohibited.

Backend enforcement is mandatory even if the UI hides the Edit action.

---

# 16. DELETE PROTECTION

Only Draft Quotation may be deleted according to the implemented safe delete policy.

Approved and Converted Quotation must remain as historical records and must not be physically deleted through normal Quotation workflow.

Backend enforcement is mandatory.

---

# 17. FINANCIAL NON-EFFECT RULE

Until a real Sales Invoice is generated, Quotation must have ZERO financial effect.

Quotation alone must not change:

- Inventory quantity
- Inventory value
- COGS
- Customer receivable
- Cash/Bank balances
- VAT payable/receivable
- Revenue
- Profit
- Trial Balance
- Balance Sheet
- General Ledger

This is a permanent business rule.

---

# 18. SALES CONVERSION EFFECT RULE

After successful conversion, all financial effects belong to the Sales Invoice and the existing Sales pipeline.

Quotation remains the source commercial document and conversion reference.

The Quotation itself does not independently post accounting entries.

---

# 19. COMPANY ISOLATION

All Quotation operations must be scoped to the authenticated Company.

The system must reject cross-company access to:

- Quotation
- Customer
- Product
- Service
- Conversion target Invoice

A Company user must never view or manipulate another Company's Quotation data.

---

# 20. PERMISSION STANDARD

Quotation uses the existing DG ERP permission architecture.

Implemented permissions are:

- `module_quotation`
- `view_quotation`
- `create_quotation`
- `edit_quotation`
- `delete_quotation`
- `approve_quotation`
- `generate_quotation_invoice`
- `print_quotation`

`generate_quotation_invoice` belongs to `module_quotation` in the Permission Module Resolver.

Permission names must not be silently renamed without updating the approved permission architecture.

Company Admin access continues to follow the existing DG ERP Role/Permission standard.

Company Staff access depends on assigned permissions.

Super Admin / Super Staff must not gain access to internal Company Quotation data contrary to the existing Company-isolation rules.

---

# 21. SIDEBAR / NAVIGATION RULE

The SAME Quotation Module may be accessible from two navigation locations:

Sales → Quotation

CRM → Quotation

This is navigation duplication only.

It MUST NOT create:

- Duplicate Quotation modules
- Duplicate routes
- Duplicate controllers
- Duplicate tables
- Duplicate Quotation data

Both navigation items must open the same existing Quotation module and respect the same permissions.

---

# 22. UI ACTION RULES

Quotation List should expose actions according to state.

## Draft

Allowed actions may include:

- View
- Edit
- Approve
- Delete
- Print

## Approved

Allowed actions may include:

- View
- Generate Invoice
- Print

Edit/Delete must not be shown as usable actions.

## Converted

Allowed actions may include:

- View
- View Generated Invoice
- Print

Edit/Delete/Generate another Invoice must be unavailable.

Backend validation remains the final authority.

---

# 23. GENERATED INVOICE LINK

After successful conversion, Quotation must retain an explicit reference to the generated Sales Invoice using the implemented relationship, such as `sales_invoice_id`.

The Quotation UI should allow the user to identify/open the generated Sales Invoice.

The link must point to the exact Invoice created from that Quotation.

---

# 24. AUDIT / TRACEABILITY

Quotation must retain implemented audit/traceability information such as:

- created_by
- approved_by
- approved_at
- converted_by
- converted_at
- sales_invoice_id
- created_at / updated_at as system metadata

System timestamps remain audit metadata only.

They must not become Sales Business Date or financial posting authority.

---

# 25. PRINT RULE

Quotation may be printed in Draft, Approved, or Converted state according to the implemented workflow and permissions.

Print output must represent Quotation data only.

For Nepal companies, the derived BS representation may be shown alongside the authoritative AD Quotation Date using the centralized date mechanism.

Printing a Quotation must have no financial effect.

---

# 26. SALES / CRM RELATIONSHIP

Quotation belongs to the pre-sales workflow.

Conceptual flow:

CRM / Sales
→ Quotation
→ Approve
→ Generate Invoice
→ Sales Invoice
→ Sales Payment / downstream Sales workflow

Quotation may be entered from either Sales or CRM navigation while remaining one canonical module.

---

# 27. ERROR HANDLING

User-facing failures must not expose unsafe internal exception details.

Conversion errors must preserve atomicity.

A failed conversion must not show the Quotation as Converted.

The user must be able to retry conversion after the underlying issue is corrected, provided the Quotation remains Approved and no Invoice link exists.

---

# 28. TEST / ACCEPTANCE STANDARD

Before production release, focused Quotation tests must verify at minimum:

- Draft create
- Draft edit
- Draft delete
- Draft approval
- Approved edit blocked
- Approved delete blocked
- Quotation financial effect = none
- Quotation stock effect = none
- Real Sales Invoice generation
- Sales list visibility
- Sales Invoice numbering
- Sales Items copied correctly
- Product stock update
- Inventory Valuation / COGS
- CustomerTransaction / Customer balance
- VAT / Accounting Core
- Sales Business Date / Financial Year
- Generated Invoice link
- Duplicate conversion blocked
- Atomic rollback
- AD/BS behavior
- Non-Nepal BS hidden
- Permission enforcement
- Company isolation

A passing Quotation test that only creates an Invoice header is insufficient.

Product-based conversion must verify the real Sales pipeline.

---

# 29. PROHIBITED IMPLEMENTATIONS

DO NOT:

- Post financial entries directly from Quotation
- Change stock directly from Quotation
- Change Customer balance directly from Quotation
- Create COGS directly from Quotation
- Treat Quotation approval as automatic Sales Invoice creation without Business Owner approval
- Allow more than one Sales Invoice per Quotation
- Allow Approved/Converted Quotation direct edit
- Allow Approved/Converted Quotation delete
- Bypass existing Sales Business Date validation
- Bypass active Financial Year validation
- Reuse Quotation number as Sales Invoice number
- Duplicate Sales accounting logic inside Quotation
- Create a second independent AD→BS converter
- Treat BS as an authoritative financial date
- Allow cross-company Quotation access
- Use created_at / updated_at as Sales Business Date

---

# 30. FROZEN BUSINESS DECISIONS

The following are BUSINESS APPROVED and FROZEN:

- Quotation is non-financial
- Quotation has no direct stock effect
- Draft is editable/deletable
- Approved is locked from edit/delete
- Converted is locked from edit/delete
- Approval does not automatically create Sales Invoice
- Approved Quotation may generate one Sales Invoice
- Conversion uses the existing Sales pipeline
- Duplicate conversion is blocked
- Conversion is atomic
- Quotation Date and Sales Business Date are separate authorities
- Sales Invoice continues to follow Sales Financial Year / Business Date rules
- Nepal BS date is derived/read-only only
- Company isolation is mandatory
- Permission enforcement is mandatory
- Sales and CRM sidebar entries refer to the same canonical Quotation Module

Any modification to these rules requires explicit Business Owner approval.

---

# 31. GOLDEN RULE

Quotation is the commercial offer.

Sales Invoice is the financial transaction.

Quotation must never become a second Sales accounting engine.

The approved flow is:

Draft Quotation
→ Approve
→ Generate ONE real Sales Invoice
→ Existing Sales pipeline controls stock, customer balance, VAT, COGS, accounting, and financial reporting.

---

END OF DOCUMENT
