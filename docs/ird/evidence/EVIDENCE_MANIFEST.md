# DG ERP — IRD Evidence Manifest

This manifest indexes synthetic, local test evidence. Nothing here is a live tax document, IRD approval, or CBMS transmission evidence.

| Evidence ID | File | Description | Source | Status | Related control | Verification | IRD dependency | Notes |
|---|---|---|---|---|---|---|---|---|
| EVD-INV-001 | `samples/SAMPLE_FISCAL_TAX_INVOICE.md` | Representative Tax Invoice presentation and arithmetic | Application Blade plus isolated feature tests | Synthetic | Fiscal issuance, snapshots, Schedule 5 presentation | Compliance suite and source trace | Format requires IRD review | Not a live invoice |
| EVD-CN-001 | `samples/SAMPLE_FISCAL_CREDIT_NOTE.md` | Partial Credit Note and original-document linkage | Sales Return print path and fiscal report fixtures | Synthetic | Credit Note, frozen evidence, canonical reversal | Compliance suite and source trace | Format requires IRD review | Not a live Credit Note |
| EVD-PRINT-001 | `samples/ORIGINAL_REPRINT_EVIDENCE.md` | Original and two server-controlled reprints | `SalesInvoiceOriginalReprintControlTest` | Synthetic | Original/Reprint audit sequence | Focused test assertions | None for implemented control; presentation review pending | Forged query values are ignored |
| EVD-RPT-001 | `reports/FISCAL_SALES_ACCOUNT_EVIDENCE.md` | Fiscal Sales Account buckets, returns, filtering, NOT READY | `SalesFiscalReportTest` and report service | Synthetic | Fiscal reporting | Exact asserted fixture totals | Report format requires IRD/CA review | Not CBMS payload evidence |
| EVD-AUDIT-001 | `reports/FISCAL_AUDIT_TRAIL_EVIDENCE.md` | Representative append-only fiscal events | Audit service and fiscal tests | Synthetic | Issuance, print, mutation, return audit | Event assertions | None for internal control | Sensitive metadata omitted |
| EVD-ROLE-001 | `tests/AUDITOR_SECURITY_EVIDENCE.md` | Auditor read-only and company-isolation controls | `AuditorRoleTest` | Synthetic | Least privilege and isolation | Route-level feature tests | None | No passwords reproduced |
| EVD-TEST-001 | `tests/COMPLIANCE_TEST_EXECUTION_EVIDENCE.md` | Reproducible regression results | Local isolated PHPUnit execution | Synthetic test database | Compliance regression gate | Exit code and concise totals | None | Full console output intentionally omitted |
| EVD-LIST-001 | `checklists/IRD_SOFTWARE_LISTING_EVIDENCE_CHECKLIST.md` | Listing-readiness checklist | Technical documentation and evidence inventory | Review checklist | Submission readiness | Document review | Multiple open items | No approval claim |
| EVD-SCR-001 | `screenshots/README.md` | Screenshot capture status | Controlled-review policy | Pending | Visual inspection | Future controlled capture | Legal/demo data must be approved | No fabricated screenshots |
| EVD-CBMS-001 | `CBMS_PAYLOAD_EVIDENCE_BLOCKED.md` | Explicit CBMS evidence blocker | IRD clarification register | BLOCKED | `/api/bill` and `/api/billreturn` | Documentation review | Authoritative mapping required | No payload invented |

## Package controls

- All identities and numbers are synthetic test evidence.
- Evidence is traceable to application views, services, or automated tests.
- No live credentials, tokens, production identifiers, or customer data are included.
- Open legal and API questions remain linked to `../IRD_CLARIFICATION_REGISTER.md`.
