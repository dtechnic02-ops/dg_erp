# DG ERP AI VAPT — Tenant Isolation Review

## Verdict

**Database/application route layer: PASS with strong coverage. Overall: FAIL pending file-storage remediation.**

Reviewed company controllers consistently derive company identity from the authenticated user and scope primary records and related products/customers/suppliers/accounts/FY. Sales, Returns, payments, reports, fiscal settings, CBMS configuration, print routes, destructive services, and audit evidence have explicit company checks. Existing feature tests exercise forged IDs and cross-company access.

Platform Country Admin scope uses canonical persisted country relationships. Global Super Admin remains global. Company Admin remains tenant-bound. Request `company_id` is not authoritative for fiscal/destructive workflows.

## Confirmed boundary failure

Files placed under `public/companies/{company}` or public storage are served before Laravel authentication. A disclosed URL bypasses company queries, permissions, Auditor restrictions, subscription checks, and account status. See VAPT-001 and VAPT-002.

## Retest priorities

- Anonymous and Company B access to every Company A file class.
- Cross-company IDs for invoice/return/payment/account/FY/report/print/export.
- Auditor direct requests to all non-allowlisted GET and every mutation verb.
- Country Admin forged country/company IDs.
- Static-file and signed-download behavior after private-storage migration.
