# DG ERP CBMS Phase 1A — Core Foundation

Date: 2026-09-11  
Status: Internal implementation foundation; no live IRD submission

## Implemented

- The existing company-scoped `company_cbms_api_configurations` foundation remains authoritative. The taxpayer username is `client_identifier`; the encrypted/hidden credential is the taxpayer login password. Seller PAN continues to come from Company Profile and is frozen on fiscal issuance.
- Dedicated Sales Bill and Bill Return builders consume frozen seller/buyer identity, immutable document numbers, authoritative business dates, financial-year dates, frozen tax classifications, fiscal line amounts, and canonical reconciliation.
- `total_sales` maps to the VAT-inclusive canonical grand total. VAT-taxable, VAT-exempt, and explicit export amounts map independently. Excise, HST, and ESF fields remain numeric zero because DG ERP has no authoritative evidence for them.
- Generic `zero_rated`, `out_of_scope`, and `legacy_unclassified` are fail-closed with `UNRESOLVED_CBMS_TAX_CLASSIFICATION`; their amounts are not silently omitted or reclassified.
- Financial-year wire values derive from authoritative start/end dates converted to consecutive BS years. No display-label parsing occurs. CBMS document dates use the existing AD-to-BS converter and `YYYY.MM.DD` wire format; canonical AD persistence is unchanged.
- The realtime decision is isolated in `CbmsRealtimeClassifier`, configured at 300 seconds, and tested at its boundaries. This threshold is implementation evidence, not claimed as final written IRD semantics. `datetimeClient` is the actual injected attempt time.
- Readiness returns structured status/reason codes for company scope, Nepal/fiscal issuance, identity, configuration/credentials, fiscal-year/date resolution, classification support, reconciliation, and Credit Note reason.
- One company-scoped polymorphic transmission table provides a unique document/endpoint identity, explicit states, attempts/timestamps, response classification, credential-free payload hash, and redacted response evidence.
- Sales and Bill Return response parsers remain separate. Code 101 never becomes automatic success; Bill Return 101 remains reconciliation-required and code 105 is referenced-bill-not-found.
- `CbmsHttpTransport` is mockable. Its Phase 1A binding always throws before networking. Configuration records JSON, TLS-verification, timeout, and official endpoint metadata for a later reviewed transport implementation.
- Company Admin can manage its own configuration and view its own status. Auditor can view company-scoped status only and never credentials. Company Staff and cross-company requests are denied.

## Transaction boundary

No Sales or Credit Note issuance flow dispatches CBMS. The intended later boundary is local fiscal commit, then transmission record/queue, then remote submission. Remote availability cannot currently affect accounting, stock, or fiscal issuance transactions.

## Test evidence

`CbmsPhaseOneFoundationTest` verifies pure VAT and mixed totals, exempt/export mapping, unsupported classifications, frozen identities, BS FY/date formatting, realtime boundaries and attempt datetime, encrypted/hidden/redacted credentials, partial Credit Note fields and amounts, mandatory return reason, endpoint-specific response codes, transmission idempotency/isolation, Auditor read-only access, and zero HTTP requests.

## Not yet claimed

- Real IRD connectivity or production submission
- IRD approval, listing, or taxpayer permission
- Queue dispatch, retry execution, remote reconciliation, or operational monitoring
- A final official interpretation for generic zero-rated or out-of-scope amounts
- Final written confirmation of the five-minute realtime threshold
- Final written resolution of Bill Return response-code 101/105 ambiguity

No real taxpayer credentials are present in source, tests, documentation, transmission evidence, or logs.
