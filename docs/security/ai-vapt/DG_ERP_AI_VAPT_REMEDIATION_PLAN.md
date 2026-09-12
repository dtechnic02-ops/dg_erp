# DG ERP AI VAPT — Remediation Plan

No remediation was performed in Phase ⑨-A.

## P0 — Immediate

Status (2026-09-10): ⑨-B1 is implemented and internally retested. Four unlinked legacy files were checksum-verified and quarantined privately without inferred ownership; they remain manual records-review items. Production rollout must run the dry-run historical inventory first, review all missing/manual-review results, then run the explicit migration command. Origin denial rules require controlled production verification.

1. VAPT-001/VAPT-002: redesign uploads to private storage and authorized streaming; derive safe server extensions; disable execution; inventory and safely migrate/quarantine existing files.
2. VAPT-003: remove tracked diagnostic/public personal-information files and add deployment denial/smoke checks.

Dependency: private-storage design must precede file migration. Preserve company isolation and existing references with a controlled compatibility plan.

## P1 — Before external VAPT / IRD review

1. VAPT-004: per-request account-status enforcement and immediate session/token revocation.
2. VAPT-005: login throttling/monitoring and privileged-account MFA plan.
3. VAPT-006: centralized user-safe exceptions and redacted logs.
4. VAPT-007: safe spreadsheet value binding.
5. VAPT-008: security headers and trusted proxy/HTTPS verification.
6. VAPT-009: minimally upgrade Laravel Excel to a fixed compatible release and regress exports.

P1 retesting depends on P0 so an upload path cannot invalidate other controls.

## P2 — Medium hardening

1. VAPT-010: atomic sequence rows and production-engine concurrency tests.
2. VAPT-011: malware scanning/quarantine/content-disposition policy.
3. VAPT-012: production configuration fail-fast checklist/tests.

## P3 — Assurance improvements

1. VAPT-013: decide on hash chaining/external immutable audit retention with IRD/legal input.
2. VAPT-014: document and test encrypted backup, restore, retention, access, and disposal.

Each group should be implemented as separately approved, smallest-scope work with focused regression tests; no architecture rewrite is required.
