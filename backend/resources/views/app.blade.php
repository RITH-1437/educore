<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F172A">
    {{-- Pages render without SSR, so crawlers and link previews only see these tags; data-inertia keys let a page's <Head> replace them instead of duplicating them. --}}
    <meta name="description" content="EduCore is a university digital administration platform connecting academic management, student services, administration, communication, and institutional workflows." data-inertia="description">
    <meta property="og:type" content="website" data-inertia="og:type">
    <meta property="og:site_name" content="EduCore" data-inertia="og:site_name">
    <meta property="og:title" content="EduCore — One Platform. Smarter Education." data-inertia="og:title">
    <meta property="og:description" content="EduCore is a university digital administration platform connecting academic management, student services, administration, communication, and institutional workflows." data-inertia="og:description">
    <meta property="og:image" content="{{ url('/assets/logo/educore-primary.png') }}" data-inertia="og:image">
    <meta name="twitter:card" content="summary_large_image" data-inertia="twitter:card">
    <link rel="icon" type="image/svg+xml" href="/assets/logo/educore-favicon.svg">
    <link rel="alternate icon" type="image/png" href="/assets/logo/educore-app-icon.png">
    <link rel="apple-touch-icon" href="/assets/logo/educore-app-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=inter:400,500,600,700&family=plus-jakarta-sans:500,600,700,800&display=swap" rel="stylesheet">
    <title inertia>{{ config('app.name', 'EduCore') }}</title>
    @vite(['src/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>