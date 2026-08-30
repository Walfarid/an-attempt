<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>404 — Page Not Found</title>

        {{-- Establish early connection to Bunny Fonts CDN for web fonts --}}
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

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

        {{-- Inline style to set the HTML background color --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }

            body {
                font-family: ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
                margin: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }

            .container {
                text-align: center;
                max-width: 28rem;
            }

            .code {
                font-size: 8rem;
                font-weight: 800;
                line-height: 1;
                color: oklch(0.145 0 0);
                letter-spacing: -0.05em;
            }

            html.dark .code {
                color: oklch(0.95 0 0);
            }

            .title {
                font-size: 1.25rem;
                font-weight: 600;
                margin-top: 0.5rem;
                color: oklch(0.35 0 0);
            }

            html.dark .title {
                color: oklch(0.75 0 0);
            }

            .message {
                font-size: 0.875rem;
                margin-top: 0.75rem;
                color: oklch(0.55 0 0);
            }

            html.dark .message {
                color: oklch(0.55 0 0);
            }

            .link {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                margin-top: 1.5rem;
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: white;
                background-color: oklch(0.55 0.13 172);
                border-radius: 0.375rem;
                text-decoration: none;
                transition: background-color 150ms ease;
            }

            .link:hover {
                background-color: oklch(0.5 0.13 172);
            }

            .link:focus {
                outline: 2px solid oklch(0.55 0.13 172);
                outline-offset: 2px;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
    </head>
    <body>
        <div class="container">
            <p class="code">404</p>
            <h1 class="title">Page not found</h1>
            <p class="message">The page you're looking for doesn't exist or has been moved.</p>
            <a href="/" class="link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Back to homepage
            </a>
        </div>
    </body>
</html>