
@php

$company = auth()->user()?->company;

@endphp


<div class="d-flex flex-column justify-content-between h-100">

<div>

<img
class="logo"
src="{{
$company && $company->logo_path
? asset('companies/'.$company->id.'/'.$company->logo_path)
: asset('logo.png')
}}">


<div class="company-name">

{{ $company->company_name ?? 'Company' }}

</div>


<div class="company-email">

{{ auth()->user()->email }}

</div>



<div class="menu">

<a href="{{ route('company.dashboard') }}">

🏠 Dashboard

</a>



<details open>

<summary>

⚙ Settings

</summary>

<a href="{{ route('company.profile') }}">

Profile

</a>

<a href="{{ route('company.users.index') }}">

Staff

</a>

<a href="{{ route('company.permissions.index') }}">

Permissions

</a>
<a href="{{ route('company.financial-years.index') }}">

 Financial Years
</a>
</details>



<details>

<summary>

📦 Products

</summary>

<a href="{{ route('company.categories.index') }}">

Categories

</a>

<a href="{{ route('company.products.index') }}">

Products

</a>

<a href="{{ route('company.units.index') }}">

Units

</a>
</a>

<a href="{{ route('company.stock-ledger.index') }}">

Stock

</a>

</a>
     <a href="{{ route('company.service-categories.index') }}">
        🛠️ Service Categories
    </a>
            <a href="{{ route('company.services.index') }}">
                🔧 Services
            </a>
                
</details>



<details>

<summary>

👥 CRM

</summary>

<a href="{{ route('company.customers.index') }}">

Customers

</a>

<a href="{{ route('company.suppliers.index') }}">

Suppliers

</a>

</details>



<details>

<summary>

🏦 Accounts

</summary>

<a href="{{ route('company.accounts.index') }}">

Accounts

</a>

<a href="{{ route('company.cash.accounts.index') }}">

Cash Accounts

</a>

<a href="{{ route('company.vats.index') }}">

VAT

</a>

</details>



<details>

<summary>

🛒 Purchases

</summary>

<a href="{{ route('company.purchases.index') }}">

Purchases

</a>
<a href="{{ route('company.purchase-payments.index') }}">
    Purchase Payments
</a>
</a>
<a href="{{ route('company.purchase-return.index') }}">

Purchase Returns

</a>

<a href="{{ route('company.purchase-return-refunds.index') }}">

Refunds

</a>

</details>



<details>

<summary>

🧾 Sales

</summary>

<a href="{{ route('company.sales.index') }}">

Sales

</a>
    <a href="{{ route('company.sales-payment.index') }}">
        Sales Payment
    </a>

<a href="{{ route('company.sales-return.index') }}">

Returns

</a>

<a href="{{ route('company.sales-return-refund.index') }}">

Refunds

</a>

</details>


<details>

<summary>

💰 Accounting

</summary>

<a href="{{ route('company.sales-payment.index') }}">

Payments

</a>

<a href="{{ route('company.stock-ledger.index') }}">

Reports

</a>
</a>

<a href="{{ route('company.contra.index') }}">

Contra

</a>

</details>



<details>

<summary>

💸 Expenses

</summary>

<a href="{{ route('company.expense.index') }}">

Expenses

</a>

<a href="{{ route('company.expense-category.index') }}">

Expense Categories

</a>

</details>
<details>

<summary>

👨‍💼 Employees

</summary>

<a
href="{{ route(
'company.employee-account.index'
) }}">

Employees

</a>

<a
href="{{ route(
'company.employee-account.create'
) }}">

Add Employee

</a>

<details>

<summary>

💰 Accounting

</summary>

<a href="{{ route('company.income.index') }}">

Income

</a>

<a href="{{ route('company.income.create') }}">

Add Income

</a>

<a href="{{ route('company.income-category.index') }}">

Income Categories

</a>

<a href="{{ route('company.journal.index') }}">

Journal

</a>

<a href="{{ route('company.journal.create') }}">

Add Journal



<a href="{{ route('company.contra.create') }}">

Add Contra

</a>

</details>

<details>

<summary>

🏦 Loans

</summary>

<a href="{{ route('company.party-account.index') }}">

Party Accounts

</a>

<a href="{{ route('company.loan-account.index') }}">

Loan Accounts

</a>

<a href="{{ route('company.loan-payment.index') }}">

Loan Payments

</a>

</details>


</div>

</div>


</div>




<div class="bottom">

<p>

📅 Expiry:

{{ $company->expiry_date ?? 'N/A' }}

</p>


<p>

👥 Limit:

{{ $company->selected_user_limit ?? 0 }}

</p>


<form method="POST" action="{{ route('logout') }}">

@csrf

<button class="logout">

🚪 Logout

</button>

</form>

</div>

</div>
