# DG ERP AI VAPT — Authorization Matrix

| Capability | Unauthenticated | Company Staff | Auditor | Company Admin | Country Admin | Super Staff | Global Super Admin |
|---|---|---|---|---|---|---|---|
| ERP read | Denied | Assigned permission, own company | Approved read routes, own company | Own company | Denied | Denied | Denied |
| ERP mutation | Denied | Assigned permission, own company | Denied | Own company | Denied | Denied | Denied |
| Fiscal print/report | Denied | Permission, own company | Read-only, own company | Own company | Platform scope only | Platform permission only | Global platform scope |
| Fiscal settings | Denied | Denied | Denied | Own-company permitted setting surface | Own country where authorized | Compliance change denied | Global |
| CBMS credentials | Denied | Denied | Denied | Own Nepal company | Denied by configuration authorization | Denied | Global Nepal companies |
| User permissions | Denied | Denied | Denied | Own-company staff within rules | Denied | Assigned platform management | Global |
| Company/FY destructive action | Denied | Permission-restricted | Denied | Own company, challenge/protection rules | Country scope where authorized | Assigned platform permission | Global with protection rules |
| Public static company file | **Potentially accessible** | **Potentially accessible** | **Potentially accessible** | **Potentially accessible** | **Potentially accessible** | **Potentially accessible** | **Potentially accessible** |

Auditor mutation enforcement is server-side through a named-route GET/HEAD allowlist plus normal permission checks. No Auditor mutation route bypass was confirmed. The static-file row is the material exception and does not reflect intended authorization.

Account blocking currently does not invalidate existing sessions for any role family; see VAPT-004.
