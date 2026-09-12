# DG ERP — IRD Electronic Billing Software Listing Readiness Summary

## Document status

**Preparation status:** READY FOR REVIEW

**Submission status:** NOT YET SUBMITTED

**Approval status:** Subject to IRD verification; no approval or listing is claimed

| Item | Current status | Evidence / note |
|---|---|---|
| Product name | READY | DG ERP |
| Developer/provider | READY | DGTAS — Digital Global Technology & Advanced Solutions |
| Software category | READY FOR REVIEW | Multi-company ERP with accounting, inventory, Sales, Purchase, and reporting |
| Electronic billing scope | READY FOR REVIEW | Nepal-gated fiscal Sales Invoice and fiscal Sales Return/Credit Note controls |
| Tax Invoice | READY FOR REVIEW | Frozen evidence, canonical arithmetic, print, accounting, stock, and audit coverage |
| Credit Note | READY FOR REVIEW | Original linkage, partial/full/residual return evidence, reversal, print, and audit coverage |
| Schedule 5 presentation | READY FOR REVIEW | Implemented presentation; subject to IRD format verification |
| Fiscal audit trail | READY | Append-only issuance, print, protected-action, and return evidence |
| Original/Reprint | READY | Server-controlled Original and numbered copies; request forgery ignored |
| Immutability | READY | Issued fiscal history protected from edit, delete, cancel, reset, FY deletion, and company deletion |
| Company isolation | READY | Authenticated company scope and forged cross-company request rejection |
| Auditor role | READY | Company-scoped read-only fiscal/accounting access with server-side mutation denial |
| Fiscal Sales Account | READY FOR REVIEW | Canonical buckets, Credit Note subtraction, filters, NOT READY handling; subject to IRD/CA review |
| Automated verification | READY | Compliance 199 tests/2,907 assertions; safe non-Journal 453 tests/5,332 assertions |
| CBMS transmission | PENDING IRD CLARIFICATION | No `/api/bill` or `/api/billreturn` implementation or payload evidence |
| External review | PENDING EXTERNAL REVIEW | Independent VAPT and CA accounting/VAT review not supplied |
| Applicant legal details | PENDING LEGAL DETAILS | Registration, PAN, address, representative, contact, signature, and stamp not finalized here |
| Formal application | READY FOR REVIEW | Draft only; not sent or uploaded |

## Product and fiscal architecture

DG ERP is a multi-company web ERP. Nepal fiscal behavior is enabled only when the authenticated company's canonical country is Nepal and its persisted IRD/CBMS setting is enabled. Successful fiscal issuance creates permanent document evidence that remains protected even if the current setting is later changed.

Implemented evidence includes transaction date and fiscal issue timestamp, frozen seller/buyer/item/unit attributes, HS/origin validation, tax classification, line discount/net/VAT evidence, canonical reconciliation, payment-mode presentation, server numbering, Original/Reprint sequencing, Credit Notes, accounting and inventory integration, fiscal reporting, and append-only audit history.

## Review package

- [Technical documentation](../DG_ERP_IRD_ELECTRONIC_BILLING_TECHNICAL_DOCUMENTATION.md)
- [Compliance matrix](../DG_ERP_IRD_COMPLIANCE_MATRIX.md)
- [Evidence manifest](../evidence/EVIDENCE_MANIFEST.md)
- [Submission document index](SUBMISSION_DOCUMENT_INDEX.md)
- [Missing-information register](MISSING_INFORMATION_REGISTER.md)

## Blocking qualification

This package is suitable for internal, CA, security, and preliminary IRD review. It is not final for submission until legal details are supplied, current submission requirements are confirmed, external reviews are completed as required, and written IRD clarification enables a non-guessed CBMS implementation.
