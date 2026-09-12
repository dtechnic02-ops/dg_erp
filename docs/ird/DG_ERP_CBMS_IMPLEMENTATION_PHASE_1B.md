# DG ERP CBMS Phase 1B — ERP-Side Operational Completion

## 1. Scope

Phase 1B adds the local queue, controlled transport seam, retry classification, immutable attempt evidence, reconciliation boundary, and company-scoped operations for fiscally issued Sales Invoices and Credit Notes. It does not enable real IRD traffic.

## 2. Phase 1A foundation reused

The Phase 1A configuration, encrypted credentials, payload builders, readiness result, tax mapper, date/FY formatter, realtime classifier, endpoint parsers, transmission model, authorization service, and status names remain authoritative. Phase 1B does not introduce parallel definitions.

## 3. Transport architecture

`CbmsHttpTransport` is injected into `CbmsTransmissionProcessor`. Tests bind `FakeCbmsTransport`; controllers, models, and jobs contain no direct HTTP calls. Endpoint identity comes only from trusted application configuration.

## 4. Hard network safety gate

The default `PhaseOneDisabledCbmsHttpTransport` throws before DNS or HTTP. `cbms.network_submission_enabled` remains `false`. No UI field can supply an endpoint URL.

## 5. Queue and after-commit flow

Fiscal issuance registers `CbmsQueueService::queueAfterCommit()` within the existing transaction. The callback reloads the document only after commit, creates or reuses its unique transmission identity, rechecks readiness, moves it to `queued`, and dispatches `TransmitCbmsDocumentJob`. Rollback creates no transmission and dispatches no job.

## 6. Retry policy

Connection, DNS, timeout, HTTP 408/429/5xx, codes 102/103, malformed, empty, and unknown responses are retryable. Authentication/model errors, Return 105, non-retryable HTTP 4xx, invalid scope/type, and readiness failures are not retried. Automatic attempts are bounded at five with 1, 5, 15, and 60 minute delays. Evidence is retained after exhaustion; a Company Admin may explicitly retry only a retryable failure.

## 7. State machine

`CbmsTransmissionStateMachine` centrally permits `pending -> not_ready|queued`, `not_ready -> queued`, `queued -> processing|not_ready`, `processing -> submitted|duplicate_requires_reconciliation|retryable_failure|permanent_failure|not_ready`, and `retryable_failure -> queued`. Submitted and permanent states cannot be reset. Duplicate-to-submitted additionally requires verifier confirmation.

## 8. Duplicate reconciliation

Sales 101 and Return 101 remain `duplicate_requires_reconciliation`. The UI offers only external recheck, never manual success. `CbmsReconciliationVerifier` defaults to unavailable. Only a verifier-confirmed exact seller PAN, FY, document identity/type, total, and payload hash may produce `submitted`.

## 9. Sales handling

Sales attempts rebuild the Phase 1A payload from frozen fiscal evidence, recheck company ownership/readiness, calculate realtime at the actual attempt, and interpret endpoint-specific responses. Duplicate 101 is not success.

## 10. Credit Note handling

Credit Notes require their own fiscal evidence/reason and a locally submitted original Bill transmission. Return 101 remains ambiguous and Return 105 means the referenced Bill was not found remotely. Return processing never changes the original Bill transmission state.

## 11. Realtime implementation evidence note

The isolated, configurable 300-second classifier is retained as implementation evidence only. Issuance and attempt timestamps remain separate. Written confirmation of official semantics is still required.

## 12. Fail-closed classifications

`vat_taxable`, `vat_exempt`, and `export` remain supported. Generic `zero_rated`, `out_of_scope`, and `legacy_unclassified` remain NOT READY without guessing or remapping.

## 13. Security and redaction

Credentials remain encrypted and hidden and are never stored in transmission or attempt evidence. Recursive key redaction and bounded 4 KiB response excerpts prevent secret leakage and database bloat. Mutation routes use POST/CSRF and resolve the authenticated company server-side.

## 14. Company Admin controls

Company Admin can view summary/list/detail evidence, queue a resolved NOT READY record, retry only retryable failures, and request verifier-backed reconciliation. There is no force-success, response edit, payload edit, history delete, or attempt reset.

## 15. Auditor controls

Auditor access is company-scoped and GET-only. It exposes redacted transmission/attempt evidence without credentials or mutation controls.

## 16. Concurrency and idempotency

The database unique key prevents duplicate document/endpoint transmissions. Queue and processing acquisition use row locks. Duplicate queue/retry clicks and stale jobs do not create duplicate rows or reprocess terminal success.

## 17. Test evidence

`CbmsPhaseOneOperationalTest` provides more than 40 operational cases using an in-process fake transport. It covers after-commit behavior, response/network matrices, transitions, idempotency, retry authorization, reconciliation, company isolation, Credit Note semantics, redaction, bounded evidence, and realtime boundaries. Phase 1A and broader compliance/regression evidence is recorded in `DG_ERP_IRD_TEST_EVIDENCE.md`.

Final local evidence: Phase 1B 54 tests / 149 assertions; Phase 1A 8 / 70; B1+B2 10 / 72; IRD compliance 199 / 2,924; safe non-Journal 525 / 5,640. All passed.

## 18. Remaining official research items

Generic zero-rated mapping, out-of-scope mapping, official realtime-delay semantics, Bill Return 101/105 ambiguity, and the real IRD response envelope/connectivity remain unresolved. Phase 1B does not claim answers.

## 19. Remaining work before real IRD connection

Written IRD clarification, approved sandbox/LAN-simulator validation, verified response-envelope parsing, security review, credentialed conformance testing, deployment approval, and an explicit reviewed switch away from the disabled transport are required.

## 20. Network statement

**NO REAL IRD REQUEST WAS SENT.** Phase 1B was implemented and tested locally with a fake transport while the default network transport remained hard-disabled.
