<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1">

<meta
name="csrf-token"
content="{{ csrf_token() }}">

<title>

@yield('title','DG ERP')

</title>

<link
rel="icon"
href="{{ asset('favicon.ico') }}">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="{{ asset('assets/company/css/app.css') }}">

@stack('styles')

@yield('css')

<style>

:root{

--sidebar:#ffffff;

--content:#f5f7fb;

--border:#dee2e6;

--primary:#0d6efd;

--danger:#dc3545;

--success:#198754;

--warning:#ffc107;

--dark:#212529;

--muted:#6c757d;

}

*{

margin:0;

padding:0;

box-sizing:border-box;

}

html{

scroll-behavior:smooth;

}

body{

background:var(--content);

font-family:Arial,Helvetica,sans-serif;

font-size:13px;

color:var(--dark);

overflow-x:hidden;

}


/* ===========================
   SIDEBAR
=========================== */

.sidebar{

position:fixed;

top:0;

left:0;

width:240px;

height:100vh;

background:var(--sidebar);

border-right:1px solid var(--border);

display:flex;

flex-direction:column;

justify-content:space-between;

padding:16px;

overflow-y:auto;

z-index:1050;

}


/* ===========================
   CONTENT
=========================== */

.content{

margin-left:240px;

padding:20px;

min-height:100vh;

}


/* ===========================
   COMPANY
=========================== */

.logo{

width:72px;

height:72px;

display:block;

margin:auto;

margin-bottom:12px;

object-fit:contain;

}

.company-name{

text-align:center;

font-weight:700;

font-size:15px;

margin-bottom:4px;

}

.company-email{

text-align:center;

font-size:12px;

color:var(--muted);

word-break:break-word;

margin-bottom:18px;

}


/* ===========================
   MENU
=========================== */

.menu{

display:flex;

flex-direction:column;

gap:6px;

}

.menu a{

display:block;

padding:10px 14px;

text-decoration:none;

border-radius:8px;

color:#495057;

transition:.20s;

font-size:14px;

}

.menu a:hover{

background:#eef5ff;

color:var(--primary);

}

.menu a.active{

background:var(--primary);

color:#fff;

}


/* ===========================
   LOGOUT
=========================== */

.logout{

width:100%;

height:40px;

border:none;

border-radius:8px;

background:var(--danger);

color:#fff;

font-weight:600;

}


/* ===========================
   MOBILE TOPBAR
=========================== */

.mobile-top{

display:none;

}


/* ===========================
   MOBILE NAV
=========================== */

.mobile-nav{

display:none;

}


/* ===========================
   OFFCANVAS
=========================== */

.offcanvas{

width:280px !important;

}

.offcanvas-body{

padding-bottom:100px;

overflow-y:auto;

}


/* ===========================
   RESPONSIVE
=========================== */

@media(max-width:992px){

.sidebar{

display:none;

}

.content{

margin-left:0;

padding:15px;

padding-bottom:90px;

}

.mobile-top{

display:flex;

align-items:center;

background:#fff;

border-bottom:1px solid var(--border);

padding:10px 15px;

position:sticky;

top:0;

z-index:1060;

}

.menu-btn{

width:46px;

height:46px;

display:flex;

align-items:center;

justify-content:center;

font-size:20px;

border-radius:10px;

}

.mobile-nav{

display:flex;

justify-content:space-around;

align-items:center;

position:fixed;

left:0;

bottom:0;

width:100%;

background:#fff;

border-top:1px solid var(--border);

padding:10px;

z-index:1100;

}

.mobile-nav a{

text-decoration:none;

font-size:22px;

color:#495057;

}

}

</style>

</head>
<body>

@php

$company = auth()->user()->company;

@endphp


<!-- ===========================
     MOBILE TOP BAR
=========================== -->

<div class="mobile-top">

    <button

        class="btn btn-primary menu-btn"

        data-bs-toggle="offcanvas"

        data-bs-target="#mobileSidebar">

        ☰

    </button>

    <div class="ms-3">

        <div class="fw-bold">

            {{ $company->company_name ?? 'DG ERP' }}

        </div>

        <small class="text-muted">

            Company Panel

        </small>

    </div>

</div>



<!-- ===========================
     DESKTOP SIDEBAR
=========================== -->

<div class="sidebar">

    @include('company.partials.sidebar')

</div>



<!-- ===========================
     MOBILE SIDEBAR
=========================== -->

<div

class="offcanvas offcanvas-start"

tabindex="-1"

id="mobileSidebar"

aria-labelledby="mobileSidebarLabel">

<div class="offcanvas-header">

<h5
class="offcanvas-title"
id="mobileSidebarLabel">

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



<!-- ===========================
     PAGE CONTENT
=========================== -->

<div class="content">

@include('company.partials.alert')

@yield('content')

</div>




<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- ===========================
     PAGE SCRIPTS
=========================== -->

@stack('scripts')


<!-- ===========================
     AUTO CLOSE ALERT
=========================== -->

<script>

document.addEventListener(

'DOMContentLoaded',

function(){

const alerts =

document.querySelectorAll(

'.alert'

);

alerts.forEach(

function(alert){

setTimeout(

function(){

if(alert){

const bsAlert =

bootstrap.Alert.getOrCreateInstance(

alert

);

bsAlert.close();

}

},

4000

);

}

);

}

);

</script>


<!-- ===========================
     ACTIVE SIDEBAR
=========================== -->

<script>

document.addEventListener(

'DOMContentLoaded',

function(){

const current =

window.location.href;

document.querySelectorAll(

'.menu a'

).forEach(

function(link){

if(

link.href===current

){

link.classList.add(

'active'

);

}

}

);

}

);

</script>


<!-- ===========================
     PRINT SUPPORT
=========================== -->

<script>

window.addEventListener(

'beforeprint',

function(){

document.body.classList.add(

'printing'

);

}

);

window.addEventListener(

'afterprint',

function(){

document.body.classList.remove(

'printing'

);

}

);

</script>


</body>

</html>