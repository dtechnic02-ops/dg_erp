<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DG ERP')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/company/css/common.css') }}">
    @stack('styles')
    @yield('css')
</head>
<body>
    <input type="checkbox" id="dg-mobile-nav" class="dg-mobile-nav-toggle">
    <label for="dg-mobile-nav" class="dg-mobile-nav-backdrop" aria-hidden="true"></label>

    <div class="dg-layout">
        @include('company.partials.sidebar')

        <div class="dg-main">
            @include('company.partials.header')

            <main class="dg-main-content" id="dgPage">
                @include('company.partials.alert')
                @yield('content')
            </main>

            @include('company.partials.footer')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/company/js/dg.js') }}"></script>
    @stack('scripts')
</body>
</html>
