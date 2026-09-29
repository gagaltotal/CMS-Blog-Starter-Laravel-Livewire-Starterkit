<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') | {{ config('app.name') }}</title>
    {{--
        Deliberately self-contained: no @vite, no database access (site
        name comes from config, not the settings table), no session. An
        error page must still render when the database is down or the
        frontend assets haven't been built yet.
    --}}
    <style>
        :root { --paper:#faf9f6; --ink:#1c1b1a; --muted:#6b6660; --line:#e4e0d6; --accent:#1f4b4a; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem;
               background:var(--paper); color:var(--ink);
               font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 30rem; }
        .code { margin:0; font-family: "Fraunces", ui-serif, Georgia, serif; font-size:5rem; line-height:1; font-weight:600; color:var(--accent); }
        h1 { margin:1rem 0 0; font-family: "Fraunces", ui-serif, Georgia, serif; font-size:1.75rem; font-weight:600; }
        p { margin:.75rem 0 0; color:var(--muted); line-height:1.6; }
        a { display:inline-block; margin-top:1.75rem; padding:.65rem 1.1rem; border-radius:.375rem; background:var(--accent); color:var(--paper);
            text-decoration:none; font-size:.9rem; font-weight:500; }
        a:hover { background:#163736; }
        a:focus-visible { outline:2px solid var(--accent); outline-offset:2px; }
    </style>
</head>
<body>
    <main>
        <p class="code" aria-hidden="true">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Back to {{ config('app.name') }}</a>
    </main>
</body>
</html>
