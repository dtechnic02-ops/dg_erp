
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>

  @if(session('success'))
<div class="alert alert-success alert-dismissible fade show">

{{ session('success') }}
  
<button
type="button"
class="btn-close"
data-bs-dismiss="alert">

</button>

</div>

@endif


@if(session('error'))

<div class="alert alert-danger alert-dismissible fade show">

{{ session('error') }}

<button
type="button"
class="btn-close"
data-bs-dismiss="alert">

</button>

</div>

@endif


@if($errors->any())

<div class="alert alert-danger">

<ul class="mb-0">

@foreach($errors->all() as $error)

<li>

{{ $error }}

</li>

@endforeach

</ul>

</div>

@endif

@yield('title','Company Panel')

</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link rel="stylesheet"
href="{{ asset('assets/company/css/app.css') }}">

@stack('styles')

@yield('css')
@section('css')

<link rel="stylesheet"
href="{{ asset('assets/company/css/profile-show.css') }}">

@endsection

<style>

:root{

--sidebar:#ffffff;
--content:#f8fafc;
--border:#e5e7eb;
--primary:#2563eb;
--danger:#ef4444;
--text:#111827;
--muted:#6b7280;

}

body{

margin:0;
background:var(--content);
font-family:Arial,sans-serif;
font-size:12px;
overflow-x:hidden;

}

/* SIDEBAR */

.sidebar{

position:fixed;

left:0;

top:0;

width:230px;

height:100vh;

background:white;

border-right:1px solid var(--border);

padding:14px;

overflow-y:auto;

display:flex;

flex-direction:column;

justify-content:space-between;

z-index:100;

}

/* CONTENT */

.content{

margin-left:230px;

padding:18px;

min-height:100vh;

}

/* COMPANY */

.logo{

width:70px;

display:block;

margin:auto;

margin-bottom:12px;

}

.company-name{

font-weight:700;

text-align:center;

font-size:14px;

}

.company-email{

font-size:12px;

text-align:center;

word-break:break-word;

margin-bottom:15px;

color:var(--muted);

}

/* MENU */

.menu{

display:flex;

flex-direction:column;

gap:6px;

}

.menu a{

display:block;

width:100%;

padding:10px 12px;

border-radius:8px;

text-decoration:none;

color:#374151;

font-size:14px;

transition:.2s;

}

.menu a:hover{

background:#eff6ff;

color:var(--primary);

}

/* BOTTOM */

.bottom{

font-size:12px;

margin-top:20px;

}

.logout{

width:100%;

height:38px;

border:none;

border-radius:8px;

background:var(--danger);

color:white;

}

/* OFFCANVAS */

.offcanvas{

width:280px !important;

}

.offcanvas-body{

overflow-y:auto;

padding-bottom:100px;

}

/* MOBILE TOP */

.mobile-top{

display:none;

}

/* BOTTOM MOBILE NAV */

.mobile-nav{

display:none;

}
.menu-btn{

width:42px;

height:42px;

font-size:18px;

padding:0;

border-radius:10px;

display:flex;

align-items:center;

justify-content:center;

}

/* MOBILE */
@media(max-width:992px){

.sidebar{

display:none;

}

/* CONTENT */

.content{

margin-left:0;

padding:12px;

padding-bottom:80px;

min-height:calc(100vh - 70px);

}

/* TOP BAR */

.mobile-top{

display:flex;

align-items:center;

padding:10px;

background:white;

border-bottom:1px solid var(--border);

position:sticky;

top:0;

z-index:1000;

}

/* MENU BUTTON */

.menu-btn{

width:48px;

height:48px;

font-size:20px;

}

/* BOTTOM NAV */

.mobile-nav{

position:fixed;

bottom:0;

left:0;

width:100%;

background:white;

display:flex;

justify-content:space-around;

align-items:center;

padding:10px;

border-top:1px solid #ddd;

z-index:999;

}

.mobile-nav a{

text-decoration:none;

font-size:20px;

color:#374151;

}

}





</style>

</head>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
   @stack('scripts') 
</script>
<body>

@php

$company=auth()->user()->company;

@endphp


<!-- MOBILE TOPBAR -->

<div class="mobile-top">

<button

class="btn btn-primary menu-btn"

data-bs-toggle="offcanvas"

data-bs-target="#mobileSidebar">

☰

</button>

<div class="ms-3 fw-bold">

{{ $company->company_name ?? 'Company' }}

</div>

</div>


<!-- DESKTOP SIDEBAR -->

<div class="sidebar">

@include('company.partials.sidebar')

</div>


<!-- MOBILE SIDEBAR -->

<div

class="offcanvas offcanvas-start"

tabindex="-1"

id="mobileSidebar">

<div class="offcanvas-header">

<h5>

Menu

</h5>

<button

type="button"

class="btn-close"

data-bs-dismiss="offcanvas">

</button>

</div>

<div class="offcanvas-body">

@include('company.partials.sidebar')

</div>

</div>


<!-- CONTENT -->

<div class="content">

@include('company.partials.alert')

@yield('content')

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
