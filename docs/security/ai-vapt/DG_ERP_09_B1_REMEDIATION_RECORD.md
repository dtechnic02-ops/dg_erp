# DG ERP ⑨-B1 — Critical File & Public Exposure Remediation Record

Date: 2026-09-10
Scope: VAPT-001, VAPT-002, VAPT-003 only

## Implementation

- Sensitive company uploads now use the Laravel private/local disk (`storage/app/private/protected/companies/{company_id}/...` with the current filesystem configuration).
- Stored physical names are server-generated UUIDs; trusted extensions come from detected MIME, and images processed by the shared image uploader are re-encoded as JPEG.
- `company/protected-files/{type}/{id}/{field}` accepts record identity only. It resolves the path from an allowlisted model/field, scopes the query to the authenticated company, checks the existing view permission, rejects unsafe paths, and streams the file.
- Existing database path fields remain compatible. Authorized delivery can read a legacy source until controlled migration.
- `files:migrate-sensitive` is dry-run by default. `--execute` only moves record-linked files whose company namespace matches the record. Missing and ambiguous sources are reported without guessing ownership or logging filenames.
- Apache repository rules deny direct requests to company upload namespaces. This is defense-in-depth; private storage is the primary control.
- `public/info.php`, `public/test.php`, and `public/info.txt` were removed.
- A recursive test prevents diagnostic PHP artifacts and `phpinfo()` from returning under `public/`.

## Deployment sequence

1. `php artisan files:migrate-sensitive`
2. Review every missing/manual-review count; stop if non-zero ownership ambiguity exists.
3. Back up the affected public and private file roots.
4. `php artisan files:migrate-sensitive --execute`
5. Repeat the dry run and application authorization tests.

No migration is run automatically. The command is idempotent and leaves unverifiable files untouched.

## Retest status

Four legacy PNG files were reviewed. Exact-name searches of the loan, loan-payment, loan-saving-ledger, Sales Return, Purchase Return, all textual database columns, and repository references found no authoritative linkage.

- File A: 855,088 bytes; quarantined as `UNLINKED LEGACY FILE — MANUAL REVIEW`.
- File B: 1,020,553 bytes; quarantined as `UNLINKED LEGACY FILE — MANUAL REVIEW`.
- File C: 229,242 bytes; quarantined as `UNLINKED LEGACY FILE — MANUAL REVIEW`.
- File D: 229,242 bytes; quarantined as `UNLINKED LEGACY FILE — MANUAL REVIEW`.

Both were copied to private quarantine using server-generated UUID `.bin` names. Size and SHA-256 integrity matched before the public originals were removed. The internal manifest preserves original relative path and filesystem timestamps, but explicitly records null company ownership and null business-record linkage. No tenant or administrative download route exists for quarantine.

Focused B1: 5 tests / 23 assertions. Affected regression: 46 tests / 365 assertions. Compliance: 199 tests / 2,911 assertions. Safe non-Journal: 458 tests / 5,359 assertions.

`REMEDIATED — INTERNAL RETEST PASS`. This is not independent VAPT certification.

Residual: `REQUIRES CONTROLLED PRODUCTION VERIFICATION` for Apache/Nginx/CloudPanel/Cloudflare routing, filesystem permissions, backups, and an authenticated post-deployment historical-file sample.
