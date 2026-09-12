# DG ERP — SAMPLE / TEST EVIDENCE — NOT A LIVE CREDIT NOTE

**Evidence ID:** EVD-CN-001

**Evidence type:** Partial-return implementation evidence from isolated fixtures

**Approval status:** Pending IRD verification

## Representative Credit Note

| Field | Synthetic evidence value |
|---|---|
| Credit Note number | CN-1 |
| Issue date | 2026-05-02 |
| Seller | Report A |
| Seller PAN | Frozen synthetic seller identifier |
| Recipient | Frozen Buyer |
| Recipient PAN | Frozen synthetic 9-digit test identifier |
| Original invoice | SI-RET |
| Original invoice date | 2026-05-01 |
| Reason | Partial return |
| Presentation | Original on first successful print |

## Partial return line

| Particular | Returned share | Gross credited | Discount credited | Net credited | VAT rate | VAT credited | Total credited |
|---|---:|---:|---:|---:|---:|---:|---:|
| VAT-taxable original line | 40% | 40.00 | 0.00 | 40.00 | 13% | 5.20 | 45.20 |

The source fixture later creates CN-2 for the exact residual taxable amount and the exempt line. Together, CN-1 and CN-2 reverse gross **180.00**, taxable sales **100.00**, exempt sales **80.00**, VAT **13.00**, and grand total **193.00**, leaving net report totals at zero.

The application Credit Note print path uses the original invoice link, frozen original identity/item evidence, return reason, fiscal issue timestamp, and server-controlled print sequence. Imported-product return tests also prove that the original frozen HS code and item attributes survive later master mutation.

## Source trace

- `resources/views/company/sales-return/print.blade.php`
- `app/Services/SalesFiscalReconciliationService.php`
- `tests/Feature/SalesFiscalReportTest.php`
- `tests/Feature/NepalFiscalDocumentImmutabilityTest.php`

This evidence is synthetic and does not represent an issued customer Credit Note.
