<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
    @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    @php
        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';
        $appName = config('app.name');

        // SEO Content based on language
        $seoData = [
            'ar' => [
                'title' => "$appName - نظام إرسال رسائل واتساب جماعية احترافي",
                'description' => "نظام إرسال رسائل واتساب جماعية احترافي للشركات والمسوقين. قم بتوصيل واتساب، واستيراد جهات الاتصال، وإرسال رسائل مخصصة بشكل جماعي. يدعم حتى 1000 جهاز في وقت واحد، مع ميزات تخصيص الرسائل، والتحكم في معدل الإرسال لحماية حسابك من الحظر.",
                'keywords' => "واتساب جماعي, رسائل واتساب, رسائل جماعية, إدارة جهات الاتصال, حملات تسويقية, واتساب للأعمال, استيراد جهات اتصال, رسائل مخصصة, حملات واتساب, WhatsApp Bulk, واتساب API",
                'og_title' => "$appName - أفضل نظام لإرسال رسائل واتساب الجماعية",
                'og_description' => "أرسل رسائل واتساب جماعية مخصصة بأمان. يدعم 1000 جهاز، استيراد CSV/Excel، وحماية من الحظر. ابدأ مجاناً الآن!",
            ],
            'en' => [
                'title' => "$appName - Professional WhatsApp Bulk Messaging System",
                'description' => "Professional WhatsApp bulk messaging system for businesses and marketers. Connect WhatsApp, import contacts, send personalized bulk messages. Supports up to 1000 devices simultaneously, with message personalization, rate limiting to protect your account from bans.",
                'keywords' => "WhatsApp bulk, bulk messaging, mass messaging, WhatsApp Business, contact management, marketing campaigns, CSV import, personalized messages, WhatsApp API, WhatsApp sender, WhatsApp automation",
                'og_title' => "$appName - Best WhatsApp Bulk Messaging Platform",
                'og_description' => "Send personalized bulk WhatsApp messages safely. Supports 1000 devices, CSV/Excel import, and ban protection. Start free now!",
            ]
        ];

        $seo = $seoData[$locale];
    @endphp

    {{-- Prevent indexing of auth pages --}}
    @if(request()->is('login') || request()->is('register') || request()->is('password/*'))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    @endif

    {{-- SEO Meta Tags --}}
    <title inertia>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    <meta name="author" content="{{ $appName }}">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    <meta name="language" content="{{ $isArabic ? 'Arabic' : 'English' }}">
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
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta name="application-name" content="{{ $appName }}">
    <meta name="theme-color" content="#0ea5e9">
    <meta name="msapplication-TileColor" content="#0ea5e9">

    {{-- Open Graph Meta Tags --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seo['og_title'] }}">
    <meta property="og:description" content="{{ $seo['og_description'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:locale" content="{{ $isArabic ? 'ar_EG' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ $isArabic ? 'en_US' : 'ar_EG' }}">
    <meta property="og:image" content="{{ asset('android-icon-192x192.png') }}">
    <meta property="og:image:secure_url" content="{{ asset('android-icon-192x192.png') }}">
    <meta property="og:image:width" content="192">
    <meta property="og:image:height" content="192">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:alt" content="{{ $appName }} Logo">

    {{-- Twitter Card Meta Tags --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['og_title'] }}">
    <meta name="twitter:description" content="{{ $seo['og_description'] }}">
    <meta name="twitter:image" content="{{ asset('android-icon-192x192.png') }}">
    <meta name="twitter:image:alt" content="{{ $appName }} Logo">
    <meta name="twitter:site" content="@{{ $appName }}">
    <meta name="twitter:creator" content="@{{ $appName }}">

    {{-- Alternate Languages --}}
    <link rel="alternate" hreflang="en" href="{{ url('/?lang=en') }}">
    <link rel="alternate" hreflang="ar" href="{{ url('/?lang=ar') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/?lang=ar') }}">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- DNS Prefetch & Preconnect for Performance --}}
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

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

    {{-- Android Chrome Icons --}}
    <link rel="icon" type="image/png" sizes="192x192" href="{{asset('/android-icon-192x192.png')}}">

    {{-- Microsoft Tiles --}}
    <meta name="msapplication-TileImage" content="{{asset('/ms-icon-144x144.png')}}">

    {{-- Web App Manifest --}}
    <link rel="manifest" href="{{asset('/manifest.json')}}">

    {{-- Inline script to detect system dark mode preference --}}
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

    {{-- Inline style to set HTML background --}}
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
    @if(config('services.google_analytics.measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.google_analytics.measurement_id') }}');
        </script>
    @endif

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
