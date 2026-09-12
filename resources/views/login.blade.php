<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ $platformSetting->platform_name ?: 'DG ERP' }}</title>
    @if($platformSetting->favicon_path)<link rel="icon" href="{{ asset('storage/'.$platformSetting->favicon_path) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dg-login-page">
<main class="dg-login-shell">
    <section class="dg-login-public" aria-labelledby="dg-login-public-title">
        @php
            $slides = collect();
            if ($loginSetting?->hero_image_path) {
                $slides->push(['path' => $loginSetting->hero_image_path, 'alt_text' => $loginSetting->heading ?: 'Platform information']);
            }
            $slides = $slides->concat($loginGallery)->unique('path')->values();
            $phone = $platformSetting->support_mobile ?: $platformSetting->primary_mobile;
            $whatsapp = preg_replace('/[^0-9]/', '', (string) $platformSetting->whatsapp_number);
        @endphp

        @if($slides->isNotEmpty())
            <div class="dg-login-carousel" data-dg-carousel aria-roledescription="carousel" aria-label="Platform images">
                <div class="dg-login-carousel-track">
                    @foreach($slides as $index => $slide)
                        <figure class="dg-login-slide {{ $index === 0 ? 'is-active' : '' }}" data-dg-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                            <img src="{{ asset('storage/'.$slide['path']) }}" alt="{{ $slide['alt_text'] ?: ($loginSetting?->heading ?: 'Platform image') }}">
                        </figure>
                    @endforeach
                </div>
                @if($slides->count() > 1)
                    <div class="dg-login-carousel-controls" aria-label="Choose platform image">
                        @foreach($slides as $index => $slide)
                            <button type="button" class="dg-login-carousel-dot {{ $index === 0 ? 'is-active' : '' }}" data-dg-slide-button="{{ $index }}" aria-label="Show image {{ $index + 1 }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="dg-login-public-content">
            @if($platformSetting->logo_path)
                <img class="dg-login-logo" src="{{ asset('storage/'.$platformSetting->logo_path) }}" alt="{{ $platformSetting->platform_name ?: 'DG ERP' }} logo">
            @else
                <img class="dg-login-logo" src="{{ asset('logo.png') }}" alt="DG ERP logo">
            @endif
            <div>
                <h1 id="dg-login-public-title">{{ $loginSetting?->heading ?: ($platformSetting->platform_name ?: 'DG ERP') }}</h1>
                <p class="dg-login-description">{{ $loginSetting?->description ?: 'Manage Your Business Smartly' }}</p>
            </div>
        </div>

        @if($loginSetting)
            <address class="dg-login-contact">
                @if($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"><span>Phone</span>{{ $phone }}</a>@endif
                @if($platformSetting->whatsapp_number && $whatsapp)<a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"><span>WhatsApp</span>{{ $platformSetting->whatsapp_number }}</a>@endif
                @if($platformSetting->website_url)<a href="{{ $platformSetting->website_url }}" target="_blank" rel="noopener noreferrer"><span>Website</span>{{ $platformSetting->website_url }}</a>@endif
                @foreach(array_filter([$platformSetting->full_address, $loginSetting->address_line_2, $loginSetting->address_line_3]) as $addressLine)<p><span>Address</span>{{ $addressLine }}</p>@endforeach
            </address>

            @if($loginLinks->isNotEmpty())
                <nav class="dg-login-links" aria-label="Platform links">
                    @foreach($loginLinks as $link)<a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">{{ ucwords(str_replace(['-', '_'], ' ', $link->provider)) }}</a>@endforeach
                </nav>
            @endif
        @endif
    </section>

    <section class="dg-login-auth" aria-labelledby="dg-login-title">
        <div class="dg-login-box">
            <h2 id="dg-login-title">Login</h2>

            @if(session('error'))<p class="dg-login-message dg-login-error" role="alert">{{ session('error') }}</p>@endif
            @if ($errors->any())
                <div class="dg-login-message dg-login-error" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="dg-login-form">
                @csrf
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
                <button type="submit">Login</button>
            </form>

        </div>
    </section>
</main>
</body>
</html>
