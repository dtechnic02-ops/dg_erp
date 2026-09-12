DG ERP --- Account Management User Guide

Document: 02_ACCOUNT_MANAGEMENT_USER_GUIDE.md
Module: Account Management
Document Type: End User / Operation Guide
Status: FINAL USER GUIDE
System: DG ERP --- Multi Company SaaS ERP

1. Purpose

Account Management is used to create and maintain the company's
operational accounts used by DG ERP.

Typical operational accounts include:

Cash

Bank

ATM

Wallet

These accounts represent the actual places where company money is held
or moved.

Account Management is not the same as the Chart of Accounts.

Chart Account = accounting classification used by Accounting
Core.

Operational Account = actual Cash/Bank/ATM/Wallet account used
by the company.

For example:

Chart Account: Cash in Hand

Operational Account: Main Cash

or:

Chart Account: Bank Accounts

Operational Account: ABC Bank Current Account

The system connects the operational account to the correct accounting
control account when a supported transaction is posted.

2. Where to Open Account Management

Log in to the company portal with a user who has Account Management
permission.

Open the Account Management / Accounts section from the company
interface.

The exact menu visibility depends on the logged-in user's role and
permissions.

3. Required Permissions

DG ERP protects Account Management with permissions.

The module currently enforces permissions for:

View Accounts

Create Accounts

Edit Accounts

Delete Accounts

Print Accounts

A Company Admin with the appropriate module access can manage company
accounts.

Company Staff can access only the actions granted through their assigned
permissions.

A user from one company cannot access another company's accounts.

4. Account Types

4.1 Cash

Use Cash for physical cash held by the business.

Examples:

Main Cash

Office Cash

Counter Cash

Petty Cash

Bank-specific information is not required for a Cash account.

The following fields may remain empty:

Bank Name

Branch

Account Number

IBAN

Swift Code

Do not enter a fake bank name such as Cash merely to fill a bank
field.

4.2 Bank

Use Bank for a normal company bank account.

Examples:

Current Account

Savings Account

Business Bank Account

Enter the genuine bank information available for the account.

4.3 ATM

Use ATM where the operational account is classified as an ATM-type
account under the existing company workflow.

4.4 Wallet

Use Wallet for supported operational wallet accounts.

Use the real account details applicable to that wallet.

5. Creating a New Account

Step 1 --- Open Account Management

Go to the Accounts page and choose Add Account.

Step 2 --- Select Account Group

Choose the appropriate account group shown by the system.

For normal Cash/Bank operational accounts, follow the company's approved
accounting setup.

Do not change accounting classifications merely to make a transaction
work.

Step 3 --- Select Account Type

Choose the correct operational type:

Cash

Bank

ATM

Wallet

Step 4 --- Enter Account Name

Enter a clear and unique account name.

Examples:

Main Cash

Office Cash

Emirates NBD Current Account

Nepal Bank Main Account

Use names that staff can identify easily during transactions.

Step 5 --- Enter Bank Details Where Applicable

For Bank/ATM/Wallet accounts, complete the relevant available details
such as:

Bank Name

Branch

Account Number

IBAN

Swift Code

For Cash, these bank-specific fields are optional and may remain blank.

Step 6 --- Select Currency

Select the currency applicable to the operational account.

Do not use the currency field to alter historical transaction values.

Step 7 --- Set Status

Use the available status option according to whether the account is
currently operational.

Normally a new usable account should be Active.

Step 8 --- Add Note if Required

Use the Note field for useful operational information only.

Do not store passwords, PINs, API keys, or other secrets in the Note
field.

Step 9 --- Save

Save the account.

A successfully created account becomes available to the permitted
workflows that use operational accounts.

6. Opening Balance Rule

A newly created Account Management account does not accept an
arbitrary opening balance from the Add Account form.

For a new account:

opening_balance = 0

current_balance = 0

This is intentional.

If the company already has money in the account when starting DG ERP,
that amount must be entered through the approved Opening Balance /
Accounting Core workflow.

Do not edit the account record directly to insert an opening balance.

7. Current Balance Rule

Current Balance is system-controlled.

Users must not manually type or overwrite Current Balance from Account
Management.

The balance changes through approved financial transactions and
Accounting Core integrations.

Examples can include supported:

Journal transactions

Sales-related receipts/payments

Purchase-related payments/refunds

Income/Expense transactions

Opening Balance postings

Other approved accounting workflows

The exact financial effect is determined by the relevant transaction
module.

8. Editing an Account

Open the required account and choose Edit if you have permission.

You may update allowed account master information.

Editing an account must not be used to manually change:

Opening Balance

Current Balance

Historical transactions

Accounting entries

Existing balances are preserved by the Account update workflow.

Important

Always verify that you are editing the intended account.

The Add Account and Edit Account forms are separate flows. Creating a
new account must not reuse an existing account's identity or balance.

9. Viewing an Account

Use View to inspect the selected account and its available
information.

Viewing an account does not itself create a financial transaction.

Use the appropriate ledger/transaction/report area when you need to
investigate the financial movements affecting the account.

10. Account Transactions

Operational account balances are supported by account transaction
records created through approved posting workflows.

Users should create the business transaction in its correct module
rather than attempting to alter Account Management balances directly.

For example, if money is transferred through a Journal, create the
Journal transaction and select the appropriate operational account where
required.

11. Journal Compatibility

Journal supports operational account selection for Cash and Bank control
lines.

Cash Control

When the selected Chart Account is the Cash control account
(CASH_IN_HAND), the journal requires an appropriate active Cash
operational account.

Bank Control

When the selected Chart Account is the Bank control account
(BANK_ACCOUNTS), the journal requires an appropriate active
operational account of an accepted bank-related type:

Bank

ATM

Wallet

Example

Suppose the company transfers 500 from Bank to Cash.

Conceptually:

Debit: Cash in Hand --- 500

Credit: Bank Accounts --- 500

The journal line must also identify the actual operational Cash and Bank
accounts.

This allows Accounting Core and operational balances to remain
connected.

12. Chart Account vs Operational Account

This distinction is important.

Chart Account

A Chart Account answers:

What accounting category does this amount belong to?

Examples:

Cash in Hand

Bank Accounts

Sales

Expense

VAT

Operational Account

An Operational Account answers:

Which actual company account did the money enter or leave?

Examples:

Main Cash

Counter Cash

Emirates NBD Account

Company Wallet

Therefore, one accounting control account may represent multiple real
operational accounts.

Example:

Chart Account: Bank Accounts

may contain operational accounts such as:

Bank Account A

Bank Account B

Bank Account C

This structure allows accounting reports to remain correctly classified
while the company can still track each actual account separately.

13. Cash Account Example

Assume the company wants to create its main physical cash account.

Enter:

Account Group: appropriate approved group

Account Type: Cash

Account Name: Main Cash

Currency: company/account currency

Status: Active

Leave bank-specific fields blank if they are not applicable.

After saving:

Opening Balance = 0

Current Balance = 0

If the business already has 10,000 in Main Cash, do not type 10,000
into Account Management.

Post the approved 10,000 opening balance through the Opening Balance
workflow.

14. Bank Account Example

Assume the company has a real bank account.

Create an account with:

Account Type: Bank

Account Name: a clear company-defined name

Bank Name: actual bank name

Branch: actual branch if applicable

Account Number: actual account number if applicable

IBAN: actual IBAN if applicable

Swift Code: actual Swift code if applicable

Currency: applicable currency

Status: Active

After creation, the balance still starts at zero until an approved
financial/opening-balance transaction changes it.

15. Duplicate Protection

Do not create multiple master accounts representing the same operational
account merely to correct a mistake.

DG ERP includes duplicate protection in Account Management.

Use Edit for allowed corrections to an existing account.

If an account already has financial history, preserve that history and
follow the system's protection rules instead of attempting to recreate
or replace the account improperly.

16. Delete Protection

Account deletion is protected.

An account with financial usage/history must not be deleted in a way
that breaks:

Account Transactions

Journal references

Accounting history

Financial reports

Audit trail

If the system blocks deletion, do not bypass the protection by directly
deleting database records.

Where appropriate, use the account's supported status/operational
controls instead.

17. Active and Inactive Accounts

Use account status to control whether an operational account should
remain available for normal use.

Before making an account inactive, confirm that doing so will not
disrupt an unfinished transaction or required workflow.

Historical transactions must remain traceable even when an account is no
longer used for new transactions.

18. Company Isolation

Every operational account belongs to its own company.

Company users must only work with accounts belonging to their company.

DG ERP protects company isolation in Account Management and related
transaction workflows.

Never attempt to share an operational account ID between companies.

19. What Account Management Must NOT Be Used For

Do not use Account Management to:

manually correct Current Balance

manually insert transaction history

bypass Opening Balance

create fake bank information

alter Accounting Core entries

repair Sales/Purchase/Journal postings manually

access another company's accounts

bypass permission controls

If a balance is wrong, investigate the transaction that caused the
balance rather than editing the account balance directly.

20. Common Problems

Cash Account Cannot Be Saved Because Bank Name Is Empty

Cash bank details are optional in the business workflow.

The application normalizes legacy database compatibility so an omitted
Cash bank name does not require a fake bank name.

Do not enter misleading bank information.

Current Balance Shows Zero

For a newly created account, this is expected.

New accounts start with:

Opening Balance = 0

Current Balance = 0

Use the approved Opening Balance workflow if an existing real-world
balance must be brought into DG ERP.

Account Does Not Appear in Journal

Check:

Account is Active.

Correct Account Type was selected.

Cash journal control lines use a Cash operational account.

Bank control lines use Bank/ATM/Wallet as supported.

The account belongs to the current company.

User Cannot Open or Manage Accounts

Check the user's Account Management permissions.

Access may depend on:

View permission

Create permission

Edit permission

Delete permission

Print permission

Do not bypass the permission system.

Balance Looks Incorrect

Do not edit Current Balance.

Instead inspect the related:

Account Transactions

Journal

Opening Balance

Sales/Payment

Purchase/Payment

Income/Expense

other relevant posted transaction

Correct the source transaction only through its approved workflow.

21. Recommended Setup Order for a New Company

For a new DG ERP company, a practical sequence is:

Complete Company Profile.

Confirm Country and Financial Year setup.

Configure required Chart of Accounts / accounting setup.

Create operational Cash accounts.

Create operational Bank/ATM/Wallet accounts.

Create Customer/Supplier/Product/VAT masters as required.

Enter approved Opening Balances.

Begin normal business transactions.

Do not create fake opening transactions merely to force balances into
the system.

22. Daily User Checklist

Before using an operational account in a transaction, confirm:

correct company

correct account

correct account type

account is active

correct currency

correct Business Date in the transaction module

correct Cash/Bank selection

transaction amount is correct

After posting an important transaction, verify the relevant
transaction/ledger/report where operationally necessary.

23. Audit and Safety Rules

Account Management is master-data management, not a balance-editing
screen.

The following principles must always remain true:

Financial balances come from approved postings.

Opening Balance follows the Opening Balance / Accounting Core
workflow.

Current Balance is not manually editable.

Company isolation is mandatory.

Permissions are mandatory.

Financial history must remain traceable.

Cash and Bank operational accounts must map to the correct
accounting context.

Existing financial records must not be destroyed to correct
master-data mistakes.

24. Related DG ERP Guides

This guide should be read together with the applicable user guides for:

Opening Balance

Journal

Sales Invoice

Sales Payment

Purchase

Purchase Payment

Income

Expense

Accounting Reports

Role & Permission

Financial Year / AD-BS Date

Those guides explain the transaction-specific financial effects that
Account Management itself does not perform.

25. Quick Reference

Task                                      Account Management Rule

Create Cash account                       Allowed with permission
Create Bank account                       Allowed with permission
Cash bank details                         Optional
Opening Balance during account creation   0
Current Balance during account creation   0
Manually edit Current Balance             Not allowed
Enter existing balance                    Use Opening Balance workflow
Edit master information                   Allowed subject to permission/protection
Delete financially used account           Protected
Cash Journal line                         Select Cash operational account
Bank Journal line                         Select Bank/ATM/Wallet operational account
Cross-company account access              Not allowed
Fake bank details for Cash                Not allowed

END OF DOCUMENT