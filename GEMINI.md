# GEMINI CLI Project Instructions - DG ERP

These instructions are permanent project guardrails for DG ERP.

## Authority

- Always read the relevant authoritative documents in `docs/` before making changes.
- Follow `docs/01_DG_ERP_MASTER_DEVELOPMENT_STANDARD.md` and all task-relevant frozen standards strictly.
- When standards conflict, follow the documented authority/version hierarchy. Do not invent a resolution.
- Business Owner approved/frozen rules must not be changed without explicit instruction.

## Scope Control

- Read only files relevant to the current task.
- Never scan the entire project unless explicitly requested.
- Do not inspect or modify unrelated modules.
- Preserve existing architecture and completed/approved work.
- Do not redesign, replace, simplify, or bypass existing structures without explicit approval.
- Make the minimum changes required to complete the requested task.

## Modification Safety

- Modify only files necessary for the explicitly requested task.
- Route, Controller, Service, Model, Migration, Seeder, Database, Blade, CSS, JS, tests, and documentation may be changed only when required by the approved task scope.
- Never modify the database, run migrations, run seeders, delete data, backfill historical data, or perform destructive operations unless explicitly authorized.
- Never silently change frozen business rules.
- If implementation requires files or database changes outside the approved scope, stop and report the requirement before proceeding.
- Ask before making destructive, irreversible, security-sensitive, or architecture-breaking changes unless they were explicitly approved in the task.

## Security and Accounting

- Preserve company isolation, Financial Year rules, Business Date rules, permission boundaries, and Accounting Core authority.
- Never introduce direct role-based business authorization where the frozen permission standards require permission-based authorization.
- Never calculate official accounting reports from operational transaction tables when Accounting Core is the authoritative source.
- Never rewrite historical accounting records unless explicitly authorized by the applicable frozen standard and task.

## UI

- Follow DG naming and UI framework rules from the authoritative standards.
- Use approved reusable DG components and existing project conventions.
- Do not introduce module-specific reusable CSS/JS architecture when prohibited by the standards.

## Verification

- Run only tests/checks relevant to the task unless broader verification is explicitly requested.
- Do not claim PASS, COMPLETE, production-ready, or blocker-free unless verified by the performed checks.
- Report any remaining blocker or unverified assumption clearly.

## Output

- Follow the exact output format requested by the task.
- Report all files created or modified.
- Do not claim that unrelated files were unchanged unless verified.
