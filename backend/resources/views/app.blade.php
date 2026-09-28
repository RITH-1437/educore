<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0F172A">
    <meta name="description" content="EduCore is a modern university digital administration platform connecting academic management, student services, administration, communication, documents, and institutional workflows.">
    <link rel="icon" type="image/png" href="/assets/logo/educore-app-icon.png">
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