@php
$user = auth()->user();
$pageTitle = trim($__env->yieldContent('title')) ?: 'DG ERP';
@endphp

<header class="dg-header" role="banner">
    <div class="dg-header-inner">
        <div class="dg-header-start">
            <label
                for="dg-mobile-nav"
                class="dg-header-toggle"
                aria-label="Open navigation menu">
                <span class="dg-header-toggle-icon" aria-hidden="true">☰</span>
            </label>

            @hasSection('breadcrumb')
                <nav class="dg-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('company.dashboard') }}" class="dg-breadcrumb-link">Home</a>
                    <span class="dg-breadcrumb-sep" aria-hidden="true">/</span>
                    @yield('breadcrumb')
                </nav>
            @else
                <h1 class="dg-header-title">{{ $pageTitle }}</h1>
            @endif
        </div>

        <div class="dg-header-end">
            <div class="dg-header-actions" aria-label="Quick actions">
                @hasSection('header-actions')
                    @yield('header-actions')
                @endif
            </div>

            <div class="dg-header-notify" aria-label="Notifications">
                <span class="dg-header-notify-icon" aria-hidden="true">🔔</span>
            </div>

            <details class="dg-header-user">
                <summary class="dg-header-user-btn" aria-haspopup="true">
                    <span class="dg-header-user-avatar" aria-hidden="true">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                    <span class="dg-header-user-name">{{ $user->name ?? 'User' }}</span>
                    <span class="dg-header-user-chevron" aria-hidden="true"></span>
                </summary>
                <div class="dg-header-user-menu" role="menu">
                    <a href="{{ route('company.profile') }}" class="dg-header-user-link" role="menuitem">Profile</a>
                    <a href="{{ route('company.dashboard') }}" class="dg-header-user-link" role="menuitem">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="dg-header-user-form">
                        @csrf
                        <button type="submit" class="dg-header-user-link dg-header-user-logout" role="menuitem">Logout</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
