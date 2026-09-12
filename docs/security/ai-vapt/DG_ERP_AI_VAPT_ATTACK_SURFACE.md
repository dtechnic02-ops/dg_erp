# DG ERP AI VAPT — Attack Surface

| Surface | Exposure | Primary controls | Residual concern |
|---|---|---|---|
| Public login/password reset | Internet | Validation, hashed passwords, reset TTL/OTP, session regeneration | Login throttle absent; production headers/config unverified |
| Platform administration | Authenticated platform roles | Platform middleware, explicit permissions, country scope | Session revocation gap |
| Company ERP (Sales/Purchase/accounting/HR/CRM) | Authenticated company roles | Company middleware, permissions, subscription/module checks, company queries | Broad legacy controller surface; safe exception handling inconsistent |
| Auditor | Authenticated read-only role | GET/HEAD named-route allowlist plus permissions | Static public files bypass middleware |
| Fiscal invoice/Credit Note | High-value financial history | Permanent evidence, policy service, transactions, locks, audit, restrictive FKs | Infrastructure/database tamper evidence remains external |
| Upload/download | Authenticated upload; some static public retrieval | MIME/size validation; some private CRM delivery | Public execution/access risk and malware controls |
| Exports/PDF/prints | Authenticated routes | Company-scoped queries and Blade escaping | Spreadsheet formulas; static attachments |
| CBMS configuration | Super Admin or owning Company Admin | company/Nepal authorization, encrypted cast, hidden credential | Future transport/retry/logging threat model required |
| Framework local storage routes | Signed URLs | relative signatures, traversal detection, CSP/no-store on serving | No issue confirmed in framework path |
| Queue/scheduler | Local configured workers | database queue support | Future CBMS must use after-commit, idempotency, secret-safe jobs |
| Deployment/webroot | Internet | front controller | tracked diagnostics and direct public company files |

No shell/process execution or unsafe deserialization call site was found in application code. Raw SQL usage located was static/parameterized query-builder composition, not a confirmed SQL injection sink. Blade raw-output sites wrap values with escaping.
