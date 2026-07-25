<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DG ERP Admin')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/company/css/common.css') }}">
    @stack('styles')
    @yield('css')
</head>
<body class="dg-admin-body">

<input type="checkbox" id="dg-mobile-nav" class="dg-mobile-nav-toggle" aria-hidden="true">
<label for="dg-mobile-nav" class="dg-mobile-nav-backdrop" aria-hidden="true"></label>

<input type="checkbox" id="dg-admin-sidebar-collapse" class="dg-admin-sidebar-collapse-toggle" aria-hidden="true">

<div class="dg-layout dg-admin-layout">

    <aside class="dg-sidebar dg-admin-sidebar" aria-label="Admin navigation">
        <div class="dg-sidebar-inner">
            <div class="dg-sidebar-header">
                <div class="dg-sidebar-brand-card dg-admin-brand-card">
                    <img src="{{ asset('logo.png') }}" class="dg-sidebar-logo dg-admin-brand-logo" alt="DG ERP">
                    <div class="dg-sidebar-company">
                        <div class="dg-sidebar-company-name">DG ERP</div>
                        <div class="dg-sidebar-company-meta">Master Admin</div>
                    </div>
                </div>
            </div>

            <div class="dg-sidebar-mobile-bar">
                <span class="dg-sidebar-mobile-title">Navigation</span>
                <label for="dg-mobile-nav" class="dg-sidebar-mobile-close" aria-label="Close navigation menu">
                    <span class="dg-sidebar-mobile-close-icon" aria-hidden="true">&times;</span>
                </label>
            </div>

            <div class="dg-sidebar-scroll">
                <nav class="dg-sidebar-nav">
                    <div class="dg-sidebar-section">
                        <div class="dg-sidebar-item">
                            <a href="{{ route('admin.dashboard') }}" class="dg-sidebar-link @if(request()->routeIs('admin.dashboard')) dg-sidebar-active @endif">
                                <span class="dg-sidebar-icon"><i class="bi bi-speedometer2"></i></span>
                                <span class="dg-sidebar-label">Dashboard</span>
                            </a>
                        </div>
                        @if(auth()->user()->hasPermission('view_company'))
                        <div class="dg-sidebar-item">
                            <a href="{{ route('admin.companies') }}" class="dg-sidebar-link @if(request()->routeIs('admin.companies')) dg-sidebar-active @endif">
                                <span class="dg-sidebar-icon"><i class="bi bi-building"></i></span>
                                <span class="dg-sidebar-label">Companies</span>
                            </a>
                        </div>
                        @endif
                        @if(auth()->user()->hasPermission('view_company'))
                        <div class="dg-sidebar-item">
                            <a href="{{ route('admin.registrations') }}" class="dg-sidebar-link @if(request()->routeIs('admin.registrations')) dg-sidebar-active @endif">
                                <span class="dg-sidebar-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <span class="dg-sidebar-label">Registrations</span>
                            </a>
                        </div>
                        @endif
                        @if(Route::has('admin.users'))
                            <div class="dg-sidebar-item">
                                <a href="{{ route('admin.users') }}" class="dg-sidebar-link @if(request()->routeIs('admin.users')) dg-sidebar-active @endif">
                                    <span class="dg-sidebar-icon"><i class="bi bi-people"></i></span>
                                    <span class="dg-sidebar-label">Users</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    @auth
                        @php
                            $canManageSubscription = (int) auth()->user()->role_id === \App\Models\Role::SUPER_ADMIN_ID || auth()->user()->hasPermission('manage_subscription_module');
                            $canViewSubscription = in_array((int) auth()->user()->role_id, [\App\Models\Role::SUPER_ADMIN_ID, \App\Models\Role::SUPER_STAFF_ID], true) || auth()->user()->hasPermission('view_subscription_module');
                            $subscriptionOpen = request()->routeIs(
                                'admin.subscription-plans.*',
                                'admin.subscription-payments.*',
                                'admin.subscriptions.*',
                                'admin.subscription-reports.*',
                                'admin.plans',
                                'admin.payments',
                                'admin.manual.payment*'
                            );
                        @endphp

                        @if($canManageSubscription || $canViewSubscription)
                            <div class="dg-sidebar-divider" aria-hidden="true"></div>
                            <div class="dg-sidebar-section">
                                <details class="dg-sidebar-group dg-admin-nav-group" @if($subscriptionOpen) open @endif>
                                    <summary class="dg-sidebar-parent">
                                        <span class="dg-sidebar-icon"><i class="bi bi-box-seam"></i></span>
                                        <span class="dg-sidebar-label">Subscription</span>
                                        <span class="dg-sidebar-chevron" aria-hidden="true"></span>
                                    </summary>
                                    <div class="dg-sidebar-submenu">
                                        @if($canManageSubscription)
                                            @if(Route::has('admin.subscription-plans.index'))
                                                <div class="dg-sidebar-child">
                                                    <a href="{{ route('admin.subscription-plans.index') }}" class="dg-sidebar-child-link @if(request()->routeIs('admin.subscription-plans.*', 'admin.plans')) dg-sidebar-active @endif">Plans</a>
                                                </div>
                                            @endif
                                            @if(Route::has('admin.subscription-payments.index'))
                                                <div class="dg-sidebar-child">
                                                    <a href="{{ route('admin.subscription-payments.index') }}" class="dg-sidebar-child-link @if(request()->routeIs('admin.subscription-payments.index', 'admin.payments')) dg-sidebar-active @endif">Payments</a>
                                                </div>
                                            @endif
                                            @if(Route::has('admin.subscription-payments.manual'))
                                                <div class="dg-sidebar-child">
                                                    <a href="{{ route('admin.subscription-payments.manual') }}" class="dg-sidebar-child-link @if(request()->routeIs('admin.subscription-payments.manual*', 'admin.manual.payment*')) dg-sidebar-active @endif">Manual Payment</a>
                                                </div>
                                            @endif
                                        @endif
                                        @if($canViewSubscription)
                                            @if(Route::has('admin.subscriptions.index'))
                                                <div class="dg-sidebar-child">
                                                    <a href="{{ route('admin.subscriptions.index') }}" class="dg-sidebar-child-link @if(request()->routeIs('admin.subscriptions.*')) dg-sidebar-active @endif">Subscriptions</a>
                                                </div>
                                            @endif
                                            @if(Route::has('admin.subscription-reports.index'))
                                                <div class="dg-sidebar-child">
                                                    <a href="{{ route('admin.subscription-reports.index') }}" class="dg-sidebar-child-link @if(request()->routeIs('admin.subscription-reports.*')) dg-sidebar-active @endif">Reports</a>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </details>
                            </div>
                        @endif
                    @endauth
                </nav>
            </div>
        </div>
    </aside>

    <div class="dg-main dg-admin-main">
        @php
            $user = auth()->user();
            $pageTitle = trim($__env->yieldContent('title')) ?: 'Admin';
        @endphp

        <header class="dg-header dg-admin-header" role="banner">
            <div class="dg-header-inner">
                <div class="dg-header-start">
                    <label for="dg-mobile-nav" class="dg-header-toggle dg-admin-mobile-toggle" aria-label="Open navigation menu">
                        <span class="dg-header-toggle-icon" aria-hidden="true"><i class="bi bi-list"></i></span>
                    </label>

                    <label for="dg-admin-sidebar-collapse" class="dg-admin-collapse-toggle" aria-label="Collapse sidebar">
                        <span class="dg-header-toggle-icon" aria-hidden="true"><i class="bi bi-layout-sidebar"></i></span>
                    </label>

                    <div class="dg-admin-page-meta">
                        <nav class="dg-breadcrumb" aria-label="Breadcrumb">
                            <a href="{{ route('admin.dashboard') }}" class="dg-breadcrumb-link">Home</a>
                            <span class="dg-breadcrumb-sep" aria-hidden="true">/</span>
                            @hasSection('breadcrumb')
                                @yield('breadcrumb')
                            @else
                                <span class="dg-breadcrumb-current">{{ $pageTitle }}</span>
                            @endif
                        </nav>
                        <h1 class="dg-header-title dg-admin-page-title">{{ $pageTitle }}</h1>
                    </div>
                </div>

                <div class="dg-header-end">
                    <div class="dg-header-actions" aria-label="Page actions">
                        @hasSection('header-actions')
                            @yield('header-actions')
                        @endif
                    </div>

                    @auth
                        <details class="dg-header-user dg-admin-user-menu">
                            <summary class="dg-header-user-btn" aria-haspopup="true">
                                <span class="dg-header-user-avatar" aria-hidden="true">{{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}</span>
                                <span class="dg-header-user-name">{{ $user->name ?? 'Admin' }}</span>
                                <span class="dg-header-user-chevron" aria-hidden="true"></span>
                            </summary>
                            <div class="dg-header-user-menu" role="menu">
                                <div class="dg-admin-user-menu-meta" role="none">
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ $user->email }}</span>
                                </div>
                                <a href="{{ route('admin.dashboard') }}" class="dg-header-user-link" role="menuitem">Dashboard</a>
                                <form method="POST" action="{{ route('logout') }}" class="dg-header-user-form">
                                    @csrf
                                    <button type="submit" class="dg-header-user-link dg-header-user-logout" role="menuitem">Logout</button>
                                </form>
                            </div>
                        </details>
                    @endauth
                </div>
            </div>
        </header>

        <main class="dg-main-content dg-admin-content" id="dgAdminPage">
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
