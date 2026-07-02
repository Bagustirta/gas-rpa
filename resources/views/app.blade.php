<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#4f46e5">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        
        <!-- PWA / Favicon -->
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" type="image/png" href="/logo.png">
        <link rel="apple-touch-icon" href="/logo.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900 w-full overflow-x-hidden">
        @inertia
    </body>
</html>
