# DG ERP — Missing Information Register

| ID | Missing item | Owner / authoritative source | Blocking? | Needed before | Status |
|---|---|---|---|---|---|
| MIS-001 | Legal applicant company name | Applicant legal records | Yes | Final application | PENDING FINAL LEGAL DETAILS |
| MIS-002 | Registration number/certificate and issuing details | Applicant legal records | Yes | Final attachments | PENDING FINAL LEGAL DETAILS |
| MIS-003 | Applicant PAN/VAT certificate/details | Applicant tax records | Yes | Final application/attachments | PENDING FINAL LEGAL DETAILS |
| MIS-004 | Registered address and official contact details | Applicant authorized representative | Yes | Final application | PENDING FINAL LEGAL DETAILS |
| MIS-005 | Authorized representative, authority, signature, and stamp | Applicant governance/legal records | Yes | Signing/submission | PENDING FINAL LEGAL DETAILS |
| MIS-006 | Current authoritative IRD submission form, channel, and mandatory attachment list | Inland Revenue Department | Yes | Package finalization | CURRENT CONFIRMATION REQUIRED |
| MIS-007 | Written answers to eight CBMS mapping/response questions | IRD IT/CBMS technical team | Yes | CBMS design and completion | WRITTEN CLARIFICATION REQUESTED |
| MIS-008 | CBMS payload, transport, acknowledgement, retry, and reconciliation implementation | Engineering after MIS-007 | Yes | CBMS conformance evidence | BLOCKED BY MIS-007 |
| MIS-009 | CA review of accounting, VAT, Schedule 5, and fiscal report presentation | Independent Chartered Accountant | Yes for external assurance | Final submission decision | PENDING EXTERNAL REVIEW |
| MIS-010 | Independent VAPT/security report and retest | Qualified independent assessor | Yes for external assurance | Final submission decision | PENDING EXTERNAL REVIEW |
| MIS-011 | Controlled application screenshots | DGTAS controlled review | No for this draft; likely supporting evidence | Final evidence packaging | CAPTURE PENDING |
| MIS-012 | Final DG ERP release/version identifier | Release owner | Yes | Application finalization and reproducible build | NOT FROZEN |
| MIS-013 | Final production architecture, database, backup, recovery, rollback, monitoring, retention, and incident contacts | Operations/security owners | Yes before controlled deployment; requirement for listing to be confirmed | Deployment and operational review | PENDING VERIFIED OPERATIONS DETAILS |
| MIS-014 | IRD sandbox/test access, credentials, taxpayer permission, and conformance process | IRD and participating taxpayer | Yes | CBMS testing | PENDING IRD ACTION |

No missing value is inferred from development configuration or test fixtures.
