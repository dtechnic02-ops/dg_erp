# Auditor / Security Evidence

**Evidence ID:** EVD-ROLE-001

Automated feature tests verify the dedicated company Auditor role:

| Control | Result |
|---|---|
| Login and company dashboard access | PASS |
| View Sales, Sales Returns, Purchases, payments, journals, ledger, fiscal report, FY, profile, and CBMS status | PASS |
| Create/edit/cancel Sales | DENIED server-side |
| Create Credit Note | DENIED server-side |
| Create Purchase/payment/expense/income/journal | DENIED server-side |
| Create/edit Financial Year | DENIED server-side |
| Change company profile or IRD/CBMS setting | DENIED server-side |
| Execute factory reset or permanent delete | DENIED server-side |
| Receive arbitrary custom permissions | DENIED |
| Read another company's invoice, return, FY, or fiscal evidence | DENIED / isolated |
| Submit/retry CBMS | Not available: transport does not exist |

Evidence source: `tests/Feature/AuditorRoleTest.php`. Synthetic test identities are used; credentials are not reproduced here.
