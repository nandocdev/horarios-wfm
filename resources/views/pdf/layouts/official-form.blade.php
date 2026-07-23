<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Formulario Oficial')</title>
    <style>
        @page {
            margin: 12mm;
            size: A4 portrait;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            vertical-align: top;
            padding: 3px;
        }

        .page { width: 100%; }
        .header { margin-bottom: 6mm; }
        .content { width: 100%; }
        .footer { margin-top: 6mm; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        .borders { border: 1px solid #000; }
        .border { border: 1px solid #000; }
        .border-top { border-top: 1px solid #000; }
        .border-bottom { border-bottom: 1px solid #000; }
        .border-left { border-left: 1px solid #000; }
        .border-right { border-right: 1px solid #000; }
        .no-border { border: none; }

        .field {
            border-bottom: 1px solid #000;
            height: 18px;
        }

        .checkbox {
            font-size: 14pt;
            font-family: DejaVu Sans, sans-serif;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 45px;
            margin-bottom: 6px;
        }

        @stack('styles')
    </style>
    @stack('styles')
</head>
<body>
<div class="page">
    @hasSection('header')
        <div class="header">@yield('header')</div>
    @endif

    <div class="content">@yield('content')</div>

    @hasSection('footer')
        <div class="footer">@yield('footer')</div>
    @endif
</div>
</body>
</html>
