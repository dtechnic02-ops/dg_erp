==========================================================
DG ERP SUBSCRIPTION BUSINESS RULE
Version: 1.0 (FREEZE)
Status: FINAL
==========================================================

SUBSCRIPTION PHILOSOPHY

Subscription controls ONLY:

• Company Activation
• Subscription Duration
• Available ERP Modules
• Staff Limit

Subscription NEVER changes ERP business logic.

==========================================================
COMPANY REGISTRATION WORKFLOW
==========================================================

Company Registration

↓

Pending

↓

Super Admin Approval

↓

Register Trial (7 Days)

↓

Expired

↓

Company Selects Subscription Plan

↓

Super Admin Approves Subscription

↓

Subscription Activated

==========================================================
REGISTER TRIAL
==========================================================

Purpose

To allow every newly approved company
to test the complete ERP.

Duration

7 Days

Starts Automatically

YES

Modules

All ERP Modules Enabled

Staff Limit

5 Staff

After Expiry

Company Status = Expired

==========================================================
FREE TRIAL
==========================================================

Purpose

Promotional trial provided by Super Admin.

Duration

30 Days

Starts

Only when assigned by Super Admin.

Modules

All ERP Modules Enabled

Staff Limit

100 Staff

After Expiry

Company Status = Expired

==========================================================
SUBSCRIPTION PLANS
==========================================================

Basic

Default Duration

30 Days

Hidden Modules

CRM

Loan

HR

Delivery

Staff Limit

1 Staff

==========================================================

Basic Plus

Default Duration

30 Days

Hidden Modules

CRM

Delivery

Staff Limit

5 Staff

==========================================================

Pro

Default Duration

30 Days

Hidden Modules

CRM

Staff Limit

20 Staff

==========================================================

Pro Plus

Default Duration

30 Days

Hidden Modules

None

Staff Limit

100 Staff

==========================================================
BILLING CYCLE
==========================================================

Subscription Plan

determines

• Available Modules

• Staff Limit

Billing Cycle

determines

Subscription Duration

Supported Billing Cycles

Monthly

30 Days

Yearly

365 Days

Future Ready

Quarterly

Half-Yearly

Lifetime

No new Plan is required when adding
new Billing Cycles.

==========================================================
MODULE ACCESS
==========================================================

Subscription restrictions MUST be enforced at:

✓ Sidebar

✓ Routes

✓ Controllers

✓ Services

✓ API

✓ Direct URL Access

Hidden modules must NEVER be accessible
through direct URLs.

==========================================================
STAFF LIMIT
==========================================================

Maximum staff depends on the active plan.

Basic

1 Staff

Basic Plus

5 Staff

Pro

20 Staff

Pro Plus

100 Staff

Company Admin cannot create staff
beyond the subscribed limit.

Staff creation assigns system role_id = 3 automatically.
Authorization for staff management uses Company-scope permissions only.
Reference: docs/12_DG_ERP_ROLE_PERMISSION_STANDARD.md (Version 2.0)

==========================================================
SUBSCRIPTION EXPIRY
==========================================================

When subscription expires:

Company Status = Expired

ERP operations are blocked until
a valid subscription is activated.

==========================================================
SUBSCRIPTION RENEWAL
==========================================================

When a company renews its subscription before expiry,
the remaining subscription days must be preserved.

The new subscription duration is added to the current
expiry date.

Example:

Current Expiry:
31 July 2026

Renewal:
365 Days

New Expiry:
31 July 2027

If the subscription has already expired,
the new subscription starts from the payment
approval date.

==========================================================
PLAN UPGRADE
==========================================================

When a company upgrades to a higher subscription plan:

• New module permissions become active immediately.

• New staff limit becomes active immediately.

• Remaining subscription duration is preserved.

==========================================================
PLAN DOWNGRADE
==========================================================

When a company downgrades to a lower subscription plan:

• New module restrictions become active.

• New staff limit becomes active.

If the current number of staff exceeds the new plan limit:

• Existing staff remain active.

• Company Admin cannot create additional staff
until the total staff count is within the new limit.

No staff account is deleted automatically.

==========================================================
FUTURE COMPATIBILITY
==========================================================

New Billing Cycles

New Payment Methods

New Pricing

must NOT require database redesign.

Only configuration should change.

==========================================================
FINAL ARCHITECTURE RULE
==========================================================

Subscription Plan

=

ERP Features

+

Staff Limit

Billing Cycle

=

Subscription Duration

Plan and Billing Cycle are completely independent.

This architecture is FINAL and must be
followed throughout DG ERP.
==========================================================