<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- SEO Meta Tags --}}
    <meta name="description"
          content="{{ config('app.name') }} - Professional WhatsApp Bulk Messaging System. Send bulk messages, manage contacts, create campaigns with advanced features. {{ app()->getLocale() == 'ar' ? 'نظام إرسال رسائل واتساب جماعية احترافي. أرسل رسائل جماعية، وإدارة جهات الاتصال، وإنشاء حملات بميزات متقدمة.' : '' }}">
    <meta name="keywords"
          content="WhatsApp, bulk messaging, mass messaging, contact management, campaign management, WhatsApp sender, bulk WhatsApp, واتساب, رسائل جماعية, إدارة جهات الاتصال, حملات واتساب">
    <meta name="author" content="{{ config('app.name') }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="language" content="{{ app()->getLocale() == 'ar' ? 'Arabic' : 'English' }}">
    <meta name="revisit-after" content="7 days">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">
    <meta name="geo.region" content="EG">
    <meta name="geo.placename" content="Egypt">
    <meta name="referrer" content="origin-when-cross-origin">

    {{-- Mobile Meta Tags --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="theme-color" content="#0ea5e9">
    <meta name="msapplication-TileColor" content="#0ea5e9">
    <meta name="msapplication-config" content="/browserconfig.xml">

    {{-- Open Graph Meta Tags --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name') }} - Professional WhatsApp Bulk Messaging">
    <meta property="og:description"
          content="Send bulk WhatsApp messages, manage contacts, and create powerful marketing campaigns with our professional platform.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ app()->getLocale() == 'ar' ? 'ar_EG' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ app()->getLocale() == 'ar' ? 'en_US' : 'ar_EG' }}">
    <meta property="og:image" content="{{ asset('android-icon-192x192.png') }}">
    <meta property="og:image:secure_url" content="{{ asset('android-icon-192x192.png') }}">
    <meta property="og:image:width" content="192">
    <meta property="og:image:height" content="192">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="{{ config('app.name') }} Logo">

    {{-- Twitter Card Meta Tags --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ config('app.name') }} - WhatsApp Bulk Messaging">
    <meta name="twitter:description"
          content="Professional WhatsApp bulk messaging platform for businesses and marketers.">
    <meta name="twitter:image" content="{{ asset('android-icon-192x192.png') }}">
    <meta name="twitter:image:alt" content="{{ config('app.name') }} Logo">
    <meta name="twitter:site" content="@{{ config('app.name') }}">
    <meta name="twitter:creator" content="@{{ config('app.name') }}">

    {{-- Alternate Languages --}}
    <link rel="alternate" hreflang="en" href="{{ url('/?lang=en') }}">
    <link rel="alternate" hreflang="ar" href="{{ url('/?lang=ar') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- DNS Prefetch & Preconnect for Performance --}}
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Title --}}
    <title inertia>{{ config('app.name', 'WA Sender') }}</title>

    {{-- Favicon - Standard --}}
    <link rel="icon" type="image/x-icon" href="{{asset('/favicon.ico')}}" sizes="16x16 32x32 48x48">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('/favicon-16x16.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{asset('/favicon-96x96.png')}}">

    {{-- Apple Touch Icons --}}
    <link rel="apple-touch-icon" href="{{asset('/apple-touch-icon.png')}}">
    <link rel="apple-touch-icon" sizes="57x57" href="{{asset('/apple-icon-57x57.png')}}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{asset('/apple-icon-60x60.png')}}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{asset('/apple-icon-72x72.png')}}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{asset('/apple-icon-76x76.png')}}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{asset('/apple-icon-114x114.png')}}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{asset('/apple-icon-120x120.png')}}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{asset('/apple-icon-144x144.png')}}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{asset('/apple-icon-152x152.png')}}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('/apple-icon-180x180.png')}}">
    <link rel="apple-touch-icon-precomposed" href="{{asset('/apple-icon-precomposed.png')}}">

    {{-- Android Chrome Icons --}}
    <link rel="icon" type="image/png" sizes="36x36" href="{{asset('/android-icon-36x36.png')}}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{asset('/android-icon-48x48.png')}}">
    <link rel="icon" type="image/png" sizes="72x72" href="{{asset('/android-icon-72x72.png')}}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{asset('/android-icon-96x96.png')}}">
    <link rel="icon" type="image/png" sizes="144x144" href="{{asset('/android-icon-144x144.png')}}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{asset('/android-icon-192x192.png')}}">

    {{-- Microsoft Tiles --}}
    <meta name="msapplication-TileImage" content="{{asset('/ms-icon-144x144.png')}}">
    <meta name="msapplication-square70x70logo" content="{{asset('/ms-icon-70x70.png')}}">
    <meta name="msapplication-square150x150logo" content="{{asset('/ms-icon-150x150.png')}}">
    <meta name="msapplication-square310x310logo" content="{{asset('/ms-icon-310x310.png')}}">

    {{-- Web App Manifest --}}
    <link rel="manifest" href="{{asset('/manifest.json')}}">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    {{-- Fonts --}}
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    {{-- Google Analytics (GA4) --}}
    <script async
            src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());
        gtag('config', 'G-VSMTG5C8VC');
    </script>

    {{-- Meta Pixel (Facebook Pixel) --}}
    <script>
        !function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments);
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s);
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1146830717394931');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id={{ config('services.meta_pixel.pixel_id') }}&ev=PageView&noscript=1" />
    </noscript>
    {{-- Structured Data - JSON-LD Schema --}}
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('android-icon-192x192.png'),
            'description' => 'Professional WhatsApp Bulk Messaging System for businesses',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'EG'
            ],
            'sameAs' => [url('/')],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'Customer Service',
                'availableLanguage' => ['English', 'Arabic']
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/') . '/search?q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ],
            'inLanguage' => ['en', 'ar']
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => config('app.name'),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web Browser',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD'
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'ratingCount' => '150'
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
@inertia
</body>
</html>
