<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('title') — {{ $student->student_number }}</title>
<style>
    /* dompdf supports CSS 2.1 only; brand colours from docs/branding (navy #0F172A, primary #2563EB, muted #64748B). */
    @page { margin: 28mm 18mm 26mm 18mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1E293B; line-height: 1.45; }
    header { position: fixed; top: -20mm; left: 0; right: 0; border-bottom: 2px solid #2563EB; padding-bottom: 6px; }
    header .name { font-size: 14pt; font-weight: bold; color: #0F172A; }
    header .meta { font-size: 8.5pt; color: #64748B; }
    footer { position: fixed; bottom: -18mm; left: 0; right: 0; border-top: 1px solid #CBD5E1; padding-top: 6px; font-size: 8pt; color: #64748B; }
    footer .code { font-family: DejaVu Sans Mono, monospace; color: #0F172A; }
    h1 { font-size: 16pt; color: #0F172A; margin: 0 0 4px 0; text-align: center; letter-spacing: 1px; }
    .subtitle { text-align: center; color: #64748B; margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; }
    .facts td { padding: 3px 0; vertical-align: top; }
    .facts td.label { width: 32%; color: #64748B; }
    .grid { margin-top: 8px; }
    .grid th { background: #F1F5F9; color: #0F172A; text-align: left; font-size: 9pt; padding: 5px 6px; border-bottom: 1px solid #CBD5E1; }
    .grid td { padding: 4px 6px; border-bottom: 1px solid #E2E8F0; font-size: 9.5pt; }
    .grid .num { text-align: right; }
    .section-title { margin: 16px 0 2px 0; font-size: 11pt; font-weight: bold; color: #0F172A; }
    .summary { margin-top: 4px; font-size: 9pt; color: #334155; }
    .signature { margin-top: 40px; width: 45%; border-top: 1px solid #94A3B8; padding-top: 4px; font-size: 9pt; color: #64748B; }
</style>
</head>
<body>
<header>
    <div class="name">{{ $university?->name ?? 'EduCore University' }}</div>
    <div class="meta">
        {{ collect([$university?->address, $university?->phone, $university?->email, $university?->website])->filter()->join(' · ') }}
    </div>
</header>

<footer>
    Issued {{ $issuedOn }} by EduCore. Verify this document at {{ $verifyUrl }}<br>
    Verification code: <span class="code">{{ $token }}</span>
</footer>

<main>
    @yield('content')

    <div class="signature">Registrar's Office<br>{{ $university?->name ?? 'EduCore University' }}</div>
</main>
</body>
</html>
