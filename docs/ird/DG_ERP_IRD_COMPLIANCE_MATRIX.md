# DG ERP — IRD Readiness Compliance Matrix

| Requirement / Control | DG ERP implementation | Status | Evidence/service | Test evidence | IRD clarification? | Notes |
|---|---|---|---|---|---|---|
| Nepal fiscal mode | Country ISO + persisted toggle through one service | COMPLETE | `NepalIrdCbmsModeService` | Mode foundation | No | New-document rule only |
| Activation lock | Successful issuance permanently prevents ordinary disable | COMPLETE | Mode service + audit predicate | Mode foundation | No | Rollback does not lock |
| Invoice numbering | Company/FY-scoped server reservation | COMPLETE | `InvoiceNumberService`, Sales controller | Fiscal immutability | Verify with IRD | Format is product-defined |
| Business/issue dates | AD authority, derived BS, frozen issue timestamp | COMPLETE | Nepali date and issue-time services | AD/BS and timestamp tests | Yes | CBMS wire format open |
| Seller PAN | Validated current identity and frozen snapshot | COMPLETE | Tax identity/snapshot services | Identity and snapshot tests | No | No credential in snapshot |
| Buyer identity/PAN | Company-scoped validation and frozen snapshot | COMPLETE | Snapshot service | Fiscal immutability | Verify with IRD | Optionality depends on official rule |
| Item/service evidence | Frozen names, units, physical attributes | COMPLETE | Snapshot service | Snapshot tests | No | Services do not fabricate physical detail |
| HS/origin | Imported physical product validation | IMPLEMENTED / VERIFY WITH IRD | Snapshot/readiness services | HS/origin matrix | Yes | Domestic HS optional in current rules |
| Tax classifications | Six explicit states including unresolved legacy | COMPLETE | Tax classification service | Classification matrix | Yes | CBMS zero/out-of-scope mapping blocked |
| Discount | Frozen line gross/discount/net evidence | COMPLETE | Line amount service | Discount tests | No | No guessed global allocation |
| VAT/reconciliation | Two-decimal canonical line/header checks | COMPLETE | Reconciliation service | Reconciliation tests | Yes | `total_sales` wire meaning open |
| Payment presentation | Frozen issuance-time mode | COMPLETE | Payment mode service | Payment tests | Verify with IRD | No Cheque claim |
| Schedule 5 presentation | Service-fed fiscal invoice view | IMPLEMENTED / VERIFY WITH IRD | Sales print view/services | Print tests | Yes | Not claimed IRD-certified |
| Original/Reprint | Locked server sequence and append-only events | COMPLETE | Fiscal audit service | Original/Reprint tests | No | View alone does not increment |
| Invoice immutability | Update/cancel/delete blocked after issuance | COMPLETE | Fiscal policy | Immutability tests | No | Durable after setting OFF |
| Credit Note | Original link, reason, snapshots, residual math | COMPLETE | Return controller/reconciliation | Credit Note tests | Verify with IRD | Rule 20 support; no certification claim |
| Credit Note immutability | Issued return protected permanently | COMPLETE | Fiscal policy | Credit Note tests | No | Current toggle not authoritative |
| Fiscal audit | Company/document-scoped append-only events | COMPLETE | Fiscal audit service | Audit tests | No | Secrets excluded |
| FY protection | App + restrictive FK protection | COMPLETE | FY model/policy/migration | Deletion-protection tests | No | Material mutation blocked |
| Reset/company deletion | Blocked when permanent fiscal evidence exists | COMPLETE | Destructive services | Reset/delete tests | No | Pre-destructive gate |
| Fiscal Sales Account | Frozen evidence, CN subtraction, NOT READY handling | IMPLEMENTED / VERIFY WITH IRD | Fiscal report service | Report tests | Yes | Not called an approved IRD format |
| Company isolation | Authenticated company scope throughout | COMPLETE | Middleware/controllers/services | Cross-company tests | No | Forged IDs rejected |
| Auditor | Company-bound read-only role | COMPLETE | Authorization + Auditor middleware | Auditor role tests | No | Role-assignment audit gap remains |
| Credential storage | Encrypted, hidden, company-scoped configuration | COMPLETE | CBMS configuration model | Configuration tests | No | No real credentials used |
| CBMS payload mapping | Dedicated invoice and Credit Note builders; supported mappings only | FOUNDATION IMPLEMENTED | CBMS Phase 1A services | `CbmsPhaseOneFoundationTest` | Yes | Generic zero-rated/out-of-scope remain fail-closed |
| CBMS HTTP transport | Injected abstraction with a default hard network block and in-process fake | ERP-SIDE OPERATIONAL | `CbmsHttpTransport`, `CbmsTransmissionProcessor` | Phase 1A/1B zero-request and response matrix | Yes | No live request performed |
| CBMS acknowledgement/retry | After-commit queue, bounded retry, immutable attempts, endpoint parsers, verifier-only duplicate reconciliation | ERP-SIDE OPERATIONAL | transmission state/queue/processor/reconciliation services | `CbmsPhaseOneOperationalTest` | Yes | Real external verification remains unavailable |
| Purchase/Payment CBMS | Deliberately outside Sales CBMS boundary | NOT APPLICABLE | Scope decision | Regression suite | No | Remain ERP modules |
