# Compliance Test Execution Evidence

**Evidence ID:** EVD-TEST-001

**Execution date:** 2026-09-10

**Environment:** PHP 8.4.20, PHPUnit 11.5.56, local isolated SQLite in-memory test database; no production/staging access

## Compliance gate

Command:

```text
php vendor/bin/phpunit --configuration phpunit.compliance.xml
```

Verified result:

```text
OK (199 tests, 2907 assertions)
Exit code: 0
Duration: 00:46.535
```

## Safe non-Journal gate

The suite enumerates all `*Test.php` files except Journal-named test classes and executes them with the standard test configuration.

Command (PowerShell):

```powershell
$testFiles = Get-ChildItem tests -Recurse -Filter '*Test.php' |
    Where-Object { $_.FullName -notmatch '\\Journal[^\\]*Test\.php$' } |
    ForEach-Object { $_.FullName }
php vendor/bin/phpunit @testFiles
```

Verified result:

```text
OK (453 tests, 5332 assertions)
Exit code: 0
Duration: 02:06.754
```

## Coverage categories

- Nepal country/CBMS mode matrix and activation lock
- PAN/VAT identity and frozen seller/buyer/item/unit evidence
- Fiscal timestamps, classification, HS/origin, discounts, payment mode, and reconciliation
- Tax Invoice and Credit Note issuance, accounting, COGS, and stock integration
- Original/Reprint sequencing and append-only audit
- Immutability, FY/reset/permanent-delete/FK protection
- Fiscal Sales Account, NOT READY handling, filters, and company isolation
- Auditor least privilege and legacy-mode compatibility
- Historical incomplete evidence remains unresolved rather than guessed

The full console stream is omitted to keep the package reviewable. Exit status and final PHPUnit totals are the authoritative concise evidence.
