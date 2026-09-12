# DG ERP — IRD Open Items and Risk Register

| ID | Open item | Severity | Blocking impact | Current treatment |
|---|---|---|---|---|
| OI-001 | Written mapping for generic `zero_rated` and `out_of_scope` | Critical | Those documents remain transmission NOT READY | See clarification register; `total_sales` foundation uses authoritative VAT-inclusive grand total |
| OI-002 | Real IRD transport and authoritative remote reconciliation remain disabled | Critical | Blocks live CBMS integration | Phase 1B local queue/retry/attempt/reconciliation seams are implemented with fake transport; networking is hard-disabled |
| OI-003 | Live/test IRD taxpayer credentials not configured or exercised | High | Blocks connectivity verification | Obtain only through approved secure process |
| OI-004 | IRD Electronic Billing Software Listing not approved | Critical | Blocks any approval/listing claim | Submit only after technical/legal readiness |
| OI-005 | Customer Electronic Billing User Permission not configured | High | May block authorized deployment/use | Confirm IRD onboarding requirements |
| OI-006 | Independent security VAPT pending | High | Security assurance incomplete | Commission before production approval |
| OI-007 | Independent CA accounting/tax audit pending | High | External accounting/tax assurance incomplete | Obtain formal review |
| OI-008 | No dedicated general role-assignment audit logging | Medium | Governance evidence gap | Design separately; do not duplicate fiscal audit engine |
| OI-009 | `company.financial-years.show` route references a missing controller method | Medium | Dead read route; Auditor uses FY index | Separate scoped bug fix |
| OI-010 | Production deployment pending final approval | Critical | No production readiness claim | Require approved deployment checklist |
| OI-011 | Final external confirmation of FY/date format, realtime threshold, and Bill Return 101/105 semantics | Critical | Blocks production submission approval | Phase 1A isolates assumptions and never treats ambiguity as success |
| OI-012 | Schedule 5 and Fiscal Sales Account require external IRD/CA format review | High | Prevents certification claim | Marked IMPLEMENTED / VERIFY WITH IRD |

Historical documents must not be auto-submitted, backfilled successful, or inferred from issuance age, print history, audit history, or current CBMS setting.
