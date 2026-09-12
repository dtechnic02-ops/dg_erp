# DG ERP — Functional Compliance Declaration Draft

**Draft implementation declaration only — not an official certificate, approval, or listing.**

DGTAS — Digital Global Technology & Advanced Solutions declares that the current reviewed DG ERP working tree implements and tests the following controls. Statements marked **Subject to IRD verification** require external confirmation of legal or prescribed presentation requirements.

| Control | Draft declaration | Qualification |
|---|---|---|
| Prescribed invoice information | Tax Invoice view presents invoice number, seller/buyer identity and PAN evidence, transaction and issue dates, payment mode, applicable HS/item details, quantity/unit, price, discount, classification/tax, VAT, line totals, summaries, and signature area. | Subject to IRD verification |
| Fiscal issuance evidence | Successful Nepal fiscal issuance freezes legal evidence in the same database transaction as the document and integrations. | Verified by automated tests |
| Numbering | Invoice and Credit Note numbers are generated server-side and scoped by company/financial year. | Verified by automated tests |
| Dates | Business transaction date and immutable fiscal issue timestamp are distinct; Nepal presentation uses the configured authoritative date services/time zone. | Subject to IRD verification of required external format |
| Frozen identity | Seller and buyer identity/PAN evidence persists independently of later mutable master data. | Verified by automated tests |
| Item evidence | Item/service name, unit, and applicable origin/HS/product attributes are frozen; services do not receive fabricated physical-product evidence. | Subject to IRD verification of HS rules |
| VAT classification | Internal classifications include VAT-taxable, VAT-exempt, zero-rated, export, and out-of-scope; only taxable lines carry positive VAT; unresolved legacy evidence is NOT READY. | CBMS mapping pending written IRD clarification |
| Discounts and arithmetic | Line gross, discount, fiscal net, VAT, and total are persisted and reconciled to canonical header buckets without guessed global allocation. | Verified by automated tests |
| Original/Reprint | First successful print is Original; subsequent prints are server-numbered copies; request forgery cannot select the copy number. | Verified by automated tests |
| Credit Notes | Fiscal Sales Returns link to the original invoice, require a reason, freeze evidence, support partial/full/final residual reversal, and reverse accounting/stock as applicable. | Subject to IRD verification of prescribed format |
| Audit trail | Issuance, Original/Reprint, protected actions, and Credit Note linkage create append-only company/document-scoped evidence; rollbacks create no false event. | Verified by automated tests |
| Immutability | Issued fiscal history remains protected from edit, direct cancellation, deletion, FY destructive change, company reset, and company permanent deletion even after current CBMS status changes. | Verified by application and database tests |
| Fiscal reporting | Fiscal Sales Account uses frozen evidence, classification buckets, Credit Note subtraction, filters, company isolation, and excludes NOT READY values from official totals. | Subject to IRD/CA verification |
| Company isolation | Fiscal documents, relationships, reports, FY, account/payment references, and audit evidence are company-scoped server-side. | Verified by automated tests |
| Auditor | Dedicated Auditor receives approved read-only access and cannot mutate transactions/settings or access another company. | Verified by automated tests |

## Explicit limitation

CBMS `/api/bill` and `/api/billreturn` payload mapping, HTTP transmission, acknowledgement, duplicate handling, retry, and reconciliation are not implemented. The required mapping decisions are **Pending written IRD clarification**.

## Sign-off placeholders

| Field | Value |
|---|---|
| Legal declaring entity | [PENDING FINAL LEGAL DETAILS] |
| Authorized representative | [PENDING FINAL LEGAL DETAILS] |
| Title | [PENDING FINAL LEGAL DETAILS] |
| Date | [PENDING FINAL LEGAL DETAILS] |
| Signature/stamp | [PENDING FINAL LEGAL DETAILS] |
