DG ERP CRM MODULE STANDARD
Version 1.0 (Production Constitution)

Document: 09_DG_ERP_CRM_MODULE_STANDARD.md

1. Purpose

CRM (Customer Relationship Management) Module is designed to manage customer relationships before and after sales.

CRM helps manage:

Leads
Prospects
Opportunities
Customer Communication
Follow-up
Meetings
Tasks
Sales Pipeline

CRM is NOT responsible for financial transactions.

2. Module Type
CRM is
Business Module
Relationship Module
Communication Module
Sales Support Module
CRM is NOT
Financial Module
Accounting Module
Inventory Module
Delivery Module
HR Module
3. Business Philosophy

CRM stores business relationships.

CRM does not create accounting entries.

CRM does not modify stock.

CRM does not receive payments.

CRM prepares customers for Sales Module.

4. Responsibilities

CRM SHALL manage

Lead Management
Prospect Management
Opportunity Management
Contact Management
Follow-up
Meeting Schedule
Call History
Email History
Customer Notes
Sales Pipeline
Attachments
5. Non Responsibilities

CRM MUST NEVER

Create Sales Invoice
Receive Payment
Update Stock
Create Journal Entry
Update Ledger
Update VAT
Create Account Transaction
Modify Delivery
6. Workflow
Lead
    ↓
Contacted
    ↓
Qualified
    ↓
Opportunity
    ↓
Quotation
    ↓
Negotiation
    ↓
Won
    ↓
Customer

OR

Lost
7. CRM Status
New

Contacted

Qualified

Proposal Sent

Negotiation

Won

Lost

Closed

Status must never be hardcoded.

Use Configuration or Enum.

8. Company Isolation

Every CRM record MUST contain

company_id

Users may only access their own company's CRM data.

Cross-company access is strictly prohibited.

9. Business Date Rules

CRM is not a Financial Module.

However every activity must contain business dates.

Examples

lead_date

follow_up_date

meeting_date

next_follow_up_date

closed_date
10. Financial Year

CRM does NOT require Financial Posting.

However activity dates should always belong to the company's active Business Period for reporting consistency when applicable.

CRM never creates financial transactions.

11. Database Tables
crm_leads

crm_contacts

crm_opportunities

crm_follow_ups

crm_meetings

crm_tasks

crm_notes

crm_attachments

crm_status_histories
12. CRM Numbering Standard

Every CRM document must have a unique company-scoped number.

Lead Number

LEAD-2026-000001

Opportunity Number

OPP-2026-000001

Activity Number

ACT-2026-000001

Rules

• Auto Generated
• Company Wise
• Never Editable
• Never Reused
• Unique Per Company

13. Customer Conversion Rules

A Lead may be converted into a Customer.

Conversion must never delete the Lead.

Original Lead remains for history.

Customer stores Lead Reference.

Audit Trail is mandatory.

Conversion SHALL record

• Converted By
• Converted At
• Lead Reference
• Customer Reference
• Conversion Remarks

Lead conversion is a historical event and must remain permanently traceable.

14. Lead Information

Lead may contain

Lead No

Company

Customer Name

Contact Person

Mobile

Email

Address

Country

Industry

Lead Source

Assigned Employee

Status

Priority

Expected Value

Remarks
15. Opportunity

Opportunity stores

Opportunity No

Potential Value

Expected Closing Date

Probability

Current Stage

Assigned Employee

Remarks
16. Activities

CRM Activities include

Phone Call

Meeting

Email

Visit

WhatsApp

SMS

Task

Reminder

Every activity must have

Activity No

Activity Date

Created By

Created Time

Remarks
17. Follow-up Rules

Every Follow-up must include

• Next Follow-up Date
• Assigned Employee
• Priority
• Remarks
• Status

Follow-up history can never be deleted.

Follow-up records SHALL remain available for audit, reporting, and employee performance review.

Follow-up Status changes must be recorded in status history.

18. Task Management Rules

Task Types

• Call
• Meeting
• Email
• Visit
• Reminder
• Deadline

Task Status

• Pending
• In Progress
• Completed
• Cancelled
• Overdue

Every Task must contain

• Task Type
• Task Status
• Assigned Employee
• Due Date
• Priority
• Remarks

Task Status must never be hardcoded.

Use Configuration or Enum.

19. Priority Configuration

Priority

• Low
• Normal
• High
• Urgent

Priority must be configurable.

Priority must never be hardcoded in application logic.

Use Configuration or Enum.

20. Lead Source Configuration

Lead Source

• Website
• Facebook
• Google
• Referral
• Walk-In
• Phone
• Email
• Advertisement
• Other

Lead Source must be configurable.

Lead Source must never be hardcoded in application logic.

Use Configuration or Enum.

21. Opportunity Stage

Discovery

Requirement

Proposal

Negotiation

Won

Lost

Stages must be configurable.

Opportunity Stage must never be hardcoded in application logic.

Use Configuration or Enum.

22. Attachments

CRM supports

PDF

DOC

Image

Quotation

Agreement

Business Card
23. Security Rules

CRM records shall never be permanently deleted.

Use

• Close
• Archive
• Cancel

Maintain complete audit history.

Security Rules

• CRM records cannot be deleted permanently
• Closed records remain searchable for audit
• Archived records remain available for reporting
• Cancelled records remain available for history
• Attachment history must remain secure and traceable
• Signature, document, and communication evidence must remain protected
• Cross-company CRM access is strictly prohibited
24. Permissions

Typical permissions

CRM View

CRM Create

CRM Edit

CRM Close

CRM Archive

CRM Export

CRM Report

Role-based access is mandatory.

25. Reports

CRM reports may include

Lead Summary
Opportunity Pipeline
Conversion Rate
Lost Reason Analysis
Sales Funnel
Employee Performance
Follow-up Due
Meeting Report
Activity Report
26. CRM Dashboard

CRM Dashboard shall include

• Today's Follow-up
• Pending Tasks
• Upcoming Meetings
• Won Opportunities
• Lost Opportunities
• Conversion Rate
• Monthly Leads
• Employee Performance

Dashboard data must respect Company Isolation.

Dashboard must use Business Dates, not system timestamps, for operational reporting.

27. Notification Rules

CRM shall support notifications for

• Follow-up Reminder
• Meeting Reminder
• Task Reminder
• Lead Assignment
• Opportunity Assignment

Notification logic belongs to Services.

Blade and UI must not contain notification business logic.

Notifications must respect Company Isolation and assigned employee ownership.

28. Search & Filter Standard

CRM Search shall support

• Lead Number
• Customer
• Mobile
• Email
• Assigned Employee
• Status
• Priority
• Lead Source
• Date Range

Search and filter must always be scoped by company_id.

Default list views should prioritize active records while still allowing access to closed, archived, and cancelled history when permitted.

29. Integration Rules

CRM may integrate with

• Customer
• Employee
• Quotation
• Sales
• Calendar
• Notification
• Document Management

CRM may reference

Customer Module
Employee Module
Quotation Module
Sales Module

CRM must NEVER

• Update Stock
• Update Ledger
• Update Payment
• Update VAT
• Update Account Transactions

CRM MUST NEVER update financial or inventory data directly.

Integration must preserve referential integrity without creating financial side effects.

30. Audit Trail

Audit every important event

• Lead Created
• Lead Updated
• Lead Assigned
• Follow-up Added
• Follow-up Updated
• Meeting Scheduled
• Meeting Completed
• Task Created
• Task Completed
• Opportunity Created
• Opportunity Updated
• Opportunity Won
• Opportunity Lost
• Lead Converted
• Customer Created From Lead
• Attachment Uploaded

Every important action must be logged.

Examples

Lead Created

Lead Updated

Status Changed

Meeting Scheduled

Opportunity Created

Lead Converted

Opportunity Closed

Audit records must never be deleted.

Audit Trail must include

• Previous Value
• Current Value
• Changed By
• Changed At
• Remarks
31. Golden Rules
CRM is a Business Relationship Module.
CRM never performs Financial Posting.
Company Isolation is mandatory.
All activities must be auditable.
Status must be configurable, not hardcoded.
CRM supports the Sales process but does not replace it.
Records should be Closed or Archived, not permanently deleted.
Business dates must be recorded for every significant activity.
9. Every Lead shall have one responsible employee.
10. CRM shall never duplicate customer records.
11. Every Follow-up shall remain historically traceable.
12. Every Customer conversion shall preserve referential integrity.
13. CRM is the single source of truth for pre-sales communication.
14. Status, Priority, Lead Source and Opportunity Stage must never be hardcoded.
15. Company Isolation is mandatory in every CRM table.

DG ERP CRM Module Objective

"Manage relationships professionally, track every interaction, improve customer conversion, and provide complete business visibility without affecting financial or inventory data."
