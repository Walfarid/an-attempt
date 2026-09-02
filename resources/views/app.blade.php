<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Google Consent Mode v2 — default all storage to denied before any
             Google tags load. The CMP (our custom Vue banner) updates these
             after the user makes a choice. See:
             https://developers.google.com/tag-platform/security/guides/consent --}}
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied',
                'wait_for_update': 500
            });
        </script>

        @head

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

        <link rel="icon" href="/favicon.ico" sizes="any">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head />

        {{-- Analytics: server-side gate (middleware reads the consent cookie).
             When consent is granted, update Consent Mode v2 before GA4 loads. --}}
        @if (($consent ?? 'unset') === 'accepted')
            <script>
                gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted',
                    'analytics_storage': 'granted'
                });
            </script>

            {{-- Microsoft Clarity — heatmaps, session recordings, click tracking --}}
            @if ($clarityId = config('services.clarity.id'))
                <script>
                    (function(c,l,a,r,i,t,y){
                        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
                    })(window, document, "clarity", "script", @json($clarityId));
                </script>
            @endif

            {{-- Google Analytics 4 — pageviews, events, conversions --}}
            @if ($gaId = config('services.google.analytics_id'))
                <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
                <script>
                    gtag('js', new Date());
                    gtag('config', @json($gaId));
                </script>
            @endif

            {{-- Google AdSense — monetization, gated behind consent --}}
            @if ($adClient = config('services.adsense.client_id'))
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adClient }}"
                        crossorigin="anonymous"></script>
            @endif
        @endif
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
