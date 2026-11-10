<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title') &middot; {{ config('app.name', 'Cardiovascular Care') }}</title>
        <style>
            :root {
                color-scheme: light;
                --ink: #0f2a35;
                --muted: #5b7180;
                --line: #dbe6ea;
                --accent: #146b8a;
            }

            * {
                box-sizing: border-box;
            }

            body {
                align-items: center;
                background: #f5f9fa;
                color: var(--ink);
                display: flex;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                justify-content: center;
                margin: 0;
                min-height: 100vh;
                padding: 2rem;
            }

            main {
                background: #fff;
                border: 1px solid var(--line);
                border-radius: 1.25rem;
                box-shadow: 0 20px 50px rgba(15, 42, 53, 0.08);
                max-width: 34rem;
                padding: 2.5rem;
                width: 100%;
            }

            .mark {
                color: var(--accent);
                font-size: 0.75rem;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            .code {
                color: var(--accent);
                font-size: 3.5rem;
                font-weight: 800;
                letter-spacing: -0.04em;
                line-height: 1;
                margin: 1.25rem 0 0.5rem;
            }

            h1 {
                font-size: 1.5rem;
                line-height: 1.25;
                margin: 0 0 0.75rem;
            }

            p {
                color: var(--muted);
                line-height: 1.6;
                margin: 0 0 1.75rem;
            }

            a {
                background: var(--accent);
                border-radius: 0.75rem;
                color: #fff;
                display: inline-block;
                font-weight: 700;
                padding: 0.75rem 1.25rem;
                text-decoration: none;
            }

            a:hover {
                background: #0f536c;
            }
        </style>
    </head>
    <body>
        <main>
            <div class="mark">{{ config('app.name', 'Cardiovascular Care') }}</div>
            <div class="code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <a href="{{ url('/') }}">Return to the homepage</a>
        </main>
    </body>
</html>
