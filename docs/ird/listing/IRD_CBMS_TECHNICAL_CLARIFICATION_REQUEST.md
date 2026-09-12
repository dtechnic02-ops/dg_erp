# Request for Written IRD CBMS Technical Clarification

**Draft communication — not yet sent**

**To:** Inland Revenue Department, Government of Nepal — appropriate IT/CBMS technical team

**From:** [PENDING FINAL LEGAL DETAILS]

**Software:** DG ERP

**Developer/provider:** DGTAS — Digital Global Technology & Advanced Solutions

DG ERP's fiscal document foundation is implemented and under preparation for technical review. Before implementing CBMS transport, we respectfully request authoritative written clarification of the following points so the software does not guess legal or payload semantics.

| ID | Question | Status |
|---|---|---|
| CLR-01 | For a domestic sale classified as zero-rated, which published `/api/bill` field must receive the sales base, and how should it affect `total_sales`, `taxable_sales_vat`, `vat`, `export_sales`, and `tax_exempted_sales`? | Written clarification requested |
| CLR-02 | How must an out-of-scope transaction be represented? Should it be excluded from the Bill payload, included in `total_sales`, or mapped to a particular published field? | Written clarification requested |
| CLR-03 | What is the exact definition of `total_sales`: gross before discount, net after discount before VAT, amount including VAT, or another prescribed value? | Written clarification requested |
| CLR-04 | What exact fiscal-year value and format are required, including separators, AD/BS basis, and examples? | Written clarification requested |
| CLR-05 | What exact date format and time-zone rules apply to Sales Bill and Bill Return/Credit Note dates? Please distinguish transaction date from issue timestamp if both are expected. | Written clarification requested |
| CLR-06 | What is the authoritative production response envelope for success and failure, including field names, data types, acknowledgement/reference identifiers, and HTTP status behavior? | Written clarification requested |
| CLR-07 | For duplicate Sales Bill submission, what does response code 101 mean, and what response data permits safe idempotent confirmation rather than treating an accepted Bill as failed? | Written clarification requested |
| CLR-08 | Please confirm endpoint-specific meanings and handling requirements for response codes 100–105 for `/api/bill` and `/api/billreturn`, including retryable versus permanent failures. | Written clarification requested |

We also request confirmation of the current sandbox/test process, production credential issuance, taxpayer Electronic Billing User Permission prerequisites, and the correct channel for technical conformance testing.

No answer is assumed in DG ERP. CBMS payload mapping and transport remain blocked until written clarification is received, archived, and reviewed.

**Authorized contact:** [PENDING FINAL LEGAL DETAILS]

**Date:** [PENDING FINAL LEGAL DETAILS]
