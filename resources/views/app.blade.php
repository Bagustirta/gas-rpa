<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#4f46e5">

        <title inertia>{{ config('app.name', 'GAS-RPA') }}</title>
        
        <!-- SEO Metadata -->
        <meta name="description" content="GAS-RPA adalah Sistem Otomatisasi Distribusi dan Pencatatan Gas LPG 3Kg. Lapor MyPertamina dan cek sisa kuota pelanggan lebih cepat dalam satu klik.">
        <meta name="keywords" content="GAS-RPA, pangkalan lpg 3kg, mypertamina, bot lpg, otomatisasi gas, rpa">
        <meta name="author" content="Ocean Tech">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="GAS-RPA - Sistem Pangkalan LPG 3Kg">
        <meta property="og:description" content="Sistem Otomatisasi Distribusi dan Pencatatan Gas LPG 3Kg.">
        <meta property="og:image" content="{{ asset('logo.png') }}">

        <!-- PWA / Favicon (Google Standar) -->
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" type="image/png" sizes="192x192" href="/logo.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/logo.png">
        <link rel="shortcut icon" href="/logo.png">
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
