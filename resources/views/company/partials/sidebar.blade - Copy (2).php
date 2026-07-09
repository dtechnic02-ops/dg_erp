
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

</details>

</div>

</div>
{{-- EXPENSES --}}

<li class="nav-item">

<a
class="nav-link collapsed"
href="#expenseMenu"
data-bs-toggle="collapse">

<i class="fa-solid fa-money-bill-wave"></i>

<span>

Expenses

</span>

</a>

<div
id="expenseMenu"
class="collapse">

<ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">

<li>

<a
href="{{ route('company.expense.index') }}"
class="nav-link">

Expense List

</a>

</li>

<li>

<a
href="{{ route('company.expense.create') }}"
class="nav-link">

Add Expense

</a>

</li>

<li>

<a
href="{{ route('company.expense-category.index') }}"
class="nav-link">

Expense Categories

</a>

</li>

<li>

<a
href="{{ route('company.expense-category.create') }}"
class="nav-link">

Add Category

</a>

</li>

</ul>

</div>

</li>



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
