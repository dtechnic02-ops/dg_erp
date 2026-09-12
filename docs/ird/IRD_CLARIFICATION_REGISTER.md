# IRD Clarification Register

All items remain **OPEN — Written IRD clarification required** for production submission. Phase 1A/1B may isolate testable foundation and ERP-side operational behavior explicitly directed by the implementation evidence, but unresolved classifications stay fail-closed, duplicate success requires authoritative verification, and no live transport is enabled.

## IRD-CLR-001 — Domestic zero-rated sales

- **Question:** How must a domestic `zero_rated` sale be represented in `/api/bill` and `/api/billreturn`?
- **Why it matters:** DG ERP keeps zero-rated and exempt evidence distinct.
- **Affected component:** Future payload builders and CBMS readiness.
- **Risk if guessed:** Incorrect official sales bucket and tax reporting.
- **Final IRD answer:** _Pending_
- **Evidence/reference:** _Pending exact written IRD reference_
- **Implementation decision:** Phase 1A leaves generic zero-rated documents NOT READY; no export remapping.

## IRD-CLR-002 — Out-of-scope sales

- **Question:** Is `out_of_scope` included in `total_sales`, excluded, or represented in another field?
- **Why it matters:** The published payload has no named out-of-scope field.
- **Affected component:** Invoice/Credit Note payload and reconciliation.
- **Risk if guessed:** Overstatement, understatement, or misclassification.
- **Final IRD answer:** _Pending_
- **Evidence/reference:** _Pending_
- **Implementation decision:** Phase 1A leaves out-of-scope documents NOT READY; no exempt remapping.

## IRD-CLR-003 — Definition of `total_sales`

- **Question:** Is `total_sales` the pre-VAT base, VAT-inclusive grand total, sum of specified CBMS buckets, or another official value?
- **Why it matters:** DG ERP preserves net sales, VAT, and grand total separately.
- **Affected component:** Both payload builders and success reconciliation.
- **Risk if guessed:** Mathematically valid local data but invalid CBMS totals.
- **Final IRD answer:** _Pending_
- **Evidence/reference:** _Pending_
- **Implementation decision:** Phase 1A uses the authoritative VAT-inclusive grand total, with an explicit reconciliation test; written production confirmation remains pending.

## IRD-CLR-004 — Fiscal-year format

- **Question:** What is the current required `fiscal_year` format, including separator and year representation?
- **Why it matters:** DG ERP stores authoritative FY name and AD boundaries.
- **Affected component:** Payload formatting/readiness.
- **Risk if guessed:** Model-invalid or misfiled submission.
- **Final IRD answer / evidence / decision:** _Pending_

## IRD-CLR-005 — Invoice and Credit Note date formats

- **Question:** What current formats and timezone rules apply to `invoice_date`, `credit_note_date`, and `datetimeClient`?
- **Why it matters:** DG ERP stores AD business dates and derives BS centrally.
- **Affected component:** Payload date formatter.
- **Risk if guessed:** Rejection or incorrect fiscal period.
- **Final IRD answer / evidence / decision:** _Pending_

## IRD-CLR-006 — Production response envelope

- **Question:** Is the response body a scalar number, numeric string, JSON envelope, or environment-dependent structure?
- **Why it matters:** HTTP success alone cannot establish acknowledgement.
- **Affected component:** Authoritative CBMS success predicate.
- **Risk if guessed:** False success or duplicate retransmission.
- **Final IRD answer / evidence / decision:** _Pending_

## IRD-CLR-007 — Duplicate Bill code 101

- **Question:** After uncertain network delivery, may Bill code `101` be authoritative reconciliation evidence, or must it remain a failure requiring portal/IRD verification?
- **Why it matters:** A timeout can occur after remote persistence.
- **Affected component:** Retry and idempotency state machine.
- **Risk if guessed:** Duplicate attempts or false acknowledgement.
- **Final IRD answer / evidence / decision:** _Pending_

## IRD-CLR-008 — Endpoint-specific response codes

- **Question:** Confirm every current code and meaning independently for Bill and Bill Return, including duplicate/missing-reference cases.
- **Why it matters:** The same numeric code may have endpoint-specific meaning.
- **Affected component:** Failure classification and retry policy.
- **Risk if guessed:** Permanent errors retried or successful records misclassified.
- **Final IRD answer / evidence / decision:** _Pending_
