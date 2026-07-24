# 05_DG_ERP_DELIVERY_MANAGEMENT_MODULE_STANDARD.md

## Production Version 1.0 (FINAL FREEZE)

# PART 01 --- MODULE OVERVIEW & BUSINESS PHILOSOPHY

## 1. Document Information

-   **Document Name:** DG ERP Delivery Management Module Standard
-   **Version:** Production Version 1.0
-   **Status:** FINAL FREEZE
-   **Framework:** Laravel 12
-   **Architecture:** Multi Company SaaS ERP
-   **Priority:** Core ERP Module
-   **Document Type:** Official DG ERP Constitution

## 2. Purpose

Delivery Management Module को उद्देश्य Customer सम्म सामान सुरक्षित, सही
Quantity सहित, प्रमाण (Proof of Delivery) सहित पुर्‍याउने सम्पूर्ण प्रक्रिया
व्यवस्थापन गर्नु हो।

यो Module कुनै पनि Financial Transaction, Stock Movement वा Accounting
सञ्चालन गर्ने Module होइन।

यस Module को मुख्य उद्देश्य Delivery Operation लाई व्यवस्थित गर्नु हो।

## 3. Business Philosophy

-   Delivery Operational Module हो।
-   Financial Module परिवर्तन गर्दैन।
-   Stock Module परिवर्तन गर्दैन।
-   Sales Invoice परिवर्तन गर्दैन।
-   Payment Module परिवर्तन गर्दैन।
-   उद्देश्य Proof of Delivery (POD) हो।

## 4. Module Responsibility

-   Delivery Create
-   Delivery Assignment
-   Delivery Processing
-   Partial Delivery
-   Pending Quantity Tracking
-   Delivery Completion
-   Customer Signature
-   Photo Attachment
-   Document Attachment
-   Delivery PDF Generation
-   Customer Email Delivery
-   Delivery History
-   Delivery Reports
-   Delivery Dashboard
-   Delivery Audit Trail

## 5. Module Non Responsibility

-   Stock Increase / Decrease
-   Payment Receive / Update
-   Sales Invoice Update
-   Sales Amount Calculation
-   VAT / Discount Calculation
-   Customer Ledger Update
-   Account Transaction Update
-   Financial Posting

## 6. Independence Rule

Delivery Module अन्य Module लाई Reference मात्र गर्छ। Customer, Sales
Invoice र Employee बाहेक कुनै Module को Data परिवर्तन गर्दैन।

## 7. Delivery Workflow

Delivery Create → Employee Select → Customer Select → Sales Invoice
Select → Invoice Item Auto Load → Delivery Quantity Entry → Submit →
Delivery Ready → Employee Opens Delivery → Customer Receives Goods → Customer Signature → Photo Attachment → Document Attachment → Submit Delivery → Delivery Completed→ PDF
Generated → PDF Sent to Customer Email → Delivery Closed

## 8. Delivery Quantity Philosophy

Display: - Product Name - Service Name - Invoice Quantity - Delivered
Quantity - Remaining Quantity

Do Not Display: - Rate - Amount - Discount - VAT - Tax - Grand Total -
Net Amount

## 9. Pending Quantity Rule

Remaining Qty = Invoice Qty − Total Delivered Qty

Invoice Table मा Remaining Qty Save गरिने छैन।

## 10. Proof of Delivery

Mandatory: - Customer Signature

Optional: - Photo - Additional Photo - Attachment

## 11. Delivery Completion Rule

Delivered भएपछि: - Delivery Lock - Final PDF Generate - PDF Archive -
Customer Email - Delivery History

## 12. Delivery Cancellation Rule

Cancel गर्दा: - Stock परिवर्तन हुँदैन। - Invoice परिवर्तन हुँदैन। - Payment
परिवर्तन हुँदैन। - Ledger परिवर्तन हुँदैन। - Cancel Reason अनिवार्य हुन्छ।

## 13. Golden Principles

1.  Delivery is Operational.
2.  Sales is Financial.
3.  Delivery never modifies Sales.
4.  Delivery never modifies Stock.
5.  Delivery never modifies Payment.
6.  Delivery keeps Proof of Delivery.
7.  Delivery keeps Complete History.
8.  Every Delivery must be Auditable.
9.  Every Completed Delivery generates an official PDF.
10. Customer receives the official Delivery PDF automatically.
14. Database Design Philosophy

Delivery Module को Database Structure पूर्ण रूपमा Normalize हुनेछ।

प्रत्येक Table को एउटा मात्र जिम्मेवारी हुनेछ।

Database मा Duplicate Business Data राखिने छैन।

Delivery Module ले Sales Module लाई Reference गर्नेछ तर Sales Table मा कुनै परिवर्तन गर्ने छैन।

15. Database Naming Standard

सभी Table हरू DG ERP Naming Standard अनुसार रहनेछन्।

Example

delivery_notes

delivery_note_items

delivery_status_histories

delivery_attachments

delivery_documents

delivery_signatures

Plural Snake Case प्रयोग गरिनेछ।

16. Master Tables (Reference Only)

Delivery Module ले निम्न Table हरू Reference मात्र गर्नेछ।

companies

employees

customers

sales_invoices

sales_invoice_items

users

यी Table मा Delivery Module ले कुनै Update गर्ने छैन।

17. Delivery Tables

Version 1.0 मा निम्न Table रहनेछन्।

1. delivery_notes

Purpose

Delivery Header Information

Contains

Delivery Number

Company

Customer

Employee

Sales Invoice

Delivery Date

Status

Remarks

Created By

Updated By

Cancelled By

Created At

Updated At

Cancelled At

One Delivery Note

↓

Many Delivery Items

2. delivery_note_items

Purpose

Delivery Item Details

Contains

Delivery Note

Sales Invoice Item

Product

Service

Invoice Qty

Delivered Qty

Remaining Qty

Status

Remarks
3. delivery_signatures

Purpose

Proof of Delivery Signature

Contains

Delivery Note

Customer Name

Receiver Name

Receiver Mobile

Signature Image

Created At

One Delivery

↓

One Signature

4. delivery_attachments

Purpose

Delivery Evidence

Contains

Delivery Note

Photo

Additional Photo

Attachment

Document Type

Remarks

One Delivery

↓

Many Attachments

5. delivery_status_histories

Purpose

Complete Delivery Timeline

Contains

Delivery Note

Previous Status

Current Status

Changed By

Changed At

Remarks

History कहिल्यै Delete हुने छैन।

18. Relationship Structure
Customer

│

├── Sales Invoice

│

└── Delivery Note

         │

         ├── Delivery Items

         │

         ├── Signature

         │

         ├── Attachments

         │

         └── Status History
19. Delivery Status

Version 1.0 मा निम्न Status मात्र रहनेछन्।

Draft
Ready
Delivered
Partial
Rejected
Cancelled

Status Hardcoded हुने छैन।

Enum अथवा Configuration अनुसार सञ्चालन हुनेछ।

20. Delivery Number Rule

प्रत्येक Delivery Note को Unique Number हुनेछ।

Example

DN-2026-000001

DN-2026-000002

DN-2026-000003

Delivery Number कहिल्यै परिवर्तन हुने छैन।

21. Quantity Rule

Delivery Module मा

Display

Invoice Qty

Delivered Qty

Remaining Qty

Financial Data रहने छैन।

Amount

Discount

VAT

Rate

Tax

Total
22. Remaining Quantity Rule

Remaining Qty Database मा Manual Save गरिने छैन।

सधैं Calculate हुनेछ।

Formula

Remaining Qty

=

Invoice Qty

-

Total Delivered Qty
23. Customer Rule

एक Delivery Note

↓

एक Customer

एक Customer

↓

धेरै Delivery Note

24. Employee Rule

एक Delivery Note

↓

एक Employee

एक Employee

↓

धेरै Delivery Note

25. Invoice Rule

एक Sales Invoice

↓

धेरै Delivery Note

यसले Partial Delivery लाई पूर्ण समर्थन गर्नेछ।

26. Attachment Rule

प्रत्येक Delivery Note मा

अनुमति

Customer Signature

Photo

Photo

Attachment

भविष्यमा

Video

GPS File

QR Evidence

जोड्न मिल्ने Architecture हुनेछ।

27. Delete Philosophy

Delivery Record कहिल्यै Permanently Delete गरिने छैन।

Delete को सट्टा

Cancelled

Status प्रयोग हुनेछ।

Audit History सुरक्षित रहनेछ।

28. Company Isolation

प्रत्येक Delivery Record

Company ID द्वारा सुरक्षित हुनेछ।

कुनै Company ले

अर्को Company को Delivery

हेर्न

Edit गर्न

Cancel गर्न

Report निकाल्न

पाउने छैन।

29. Audit Fields

हरेक Delivery Table मा

company_id

created_by

updated_by

cancelled_by

created_at

updated_at

cancelled_at

अनिवार्य रहनेछन्।

30. Golden Database Rules
Delivery Module केवल Reference Module हो।
Delivery Module ले Sales Table Update गर्दैन।
Delivery Module ले Stock Table Update गर्दैन।
Delivery Module ले Payment Table Update गर्दैन।
Delivery Module ले Ledger Update गर्दैन।
Delivery History कहिल्यै Delete हुँदैन।
Attachment इतिहास सुरक्षित रहनेछ।
Signature सुरक्षित रहनेछ।
Delivery Number Immutable हुनेछ।
सबै Record Company Isolation अनुसार सञ्चालन हुनेछन्।
