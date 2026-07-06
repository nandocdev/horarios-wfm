<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', $title ?? 'Reporte')</title>
    <style>
        @page {
            margin: 22mm 12mm 20mm 12mm;
            margin-top: 25mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.4;
            color: #1a1a1a;
        }

        h1 { font-size: 14pt; margin: 0 0 8px 0; color: #1e293b; }
        h2 { font-size: 11pt; margin: 0 0 6px 0; color: #334155; }
        h3 { font-size: 9.5pt; margin: 0 0 4px 0; color: #475569; }

        .report-header {
            position: fixed;
            top: -18mm;
            left: 0;
            right: 0;
            height: 18mm;
            border-bottom: 2px solid #2563eb;
            padding: 4mm 12mm;
        }
        .report-header .logo { height: 12mm; float: left; }
        .report-header .header-info { float: right; text-align: right; font-size: 7pt; color: #64748b; padding-top: 2mm; }
        .report-header .header-title { text-align: center; font-size: 10pt; font-weight: bold; color: #1e293b; padding-top: 3mm; }

        .report-footer {
            position: fixed;
            bottom: -16mm;
            left: 0;
            right: 0;
            height: 14mm;
            border-top: 1px solid #cbd5e1;
            padding: 3mm 12mm;
            font-size: 7pt;
            color: #64748b;
        }
        .report-footer .footer-left { float: left; }
        .report-footer .footer-right { float: right; }

        .report-info-bar {
            background: #f1f5f9;
            border-radius: 3px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 7.5pt;
            color: #475569;
        }
        .report-info-bar table { width: 100%; }
        .report-info-bar td { padding: 1px 8px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        table.data thead th {
            background: #2563eb;
            color: white;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #1d4ed8;
        }
        table.data thead th.right { text-align: right; }
        table.data thead th.center { text-align: center; }
        table.data tbody td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            font-size: 8pt;
        }
        table.data tbody td.right { text-align: right; }
        table.data tbody td.center { text-align: center; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tbody tr.subtotal { background: #f1f5f9; font-weight: bold; }
        table.data tbody tr.subtotal td { border-top: 2px solid #94a3b8; }
        table.data tbody tr.grandtotal { background: #e2e8f0; font-weight: bold; font-size: 9pt; }
        table.data tbody tr.grandtotal td { border-top: 3px double #475569; }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 8px 0;
            font-size: 8pt;
        }
        .summary-box table { width: 100%; }
        .summary-box td { padding: 2px 10px; }
        .summary-box .label { color: #64748b; }
        .summary-box .value { font-weight: bold; text-align: right; }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 1px solid #2563eb;
            padding-bottom: 3px;
            margin: 12px 0 8px 0;
        }

        .kpi-grid { width: 100%; margin: 6px 0; }
        .kpi-grid td { width: 20%; padding: 4px 6px; }
        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 6px 8px;
            text-align: center;
        }
        .kpi-card .kpi-value { font-size: 12pt; font-weight: bold; color: #1e293b; }
        .kpi-card .kpi-label { font-size: 6.5pt; color: #64748b; text-transform: uppercase; }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 60pt;
            color: rgba(0,0,0,0.04);
            z-index: -1;
            white-space: nowrap;
        }

        .text-muted { color: #64748b; }
        .text-success { color: #166534; }
        .text-danger { color: #991b1b; }
        .text-warning { color: #92400e; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .w-full { width: 100%; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- Watermark --}}
@if(isset($watermark) && $watermark)
    <div class="watermark">{{ $watermark }}</div>
@endif

{{-- Header por página --}}
<div class="report-header">
    @if(isset($logo) && file_exists($logo))
        <img src="{{ $logo }}" class="logo" alt="Logo"/>
    @endif
    <div class="header-title">{{ $title ?? 'REPORTE' }}</div>
    <div class="header-info">
        {{ $date ?? now()->format('d/m/Y H:i') }}<br/>
        @if(isset($user)){{ $user }}<br/>@endif
    </div>
</div>

{{-- Footer por página --}}
@if(isset($footer))
    <div class="report-footer">
        <div class="footer-left">{{ $footer['left'] ?? '' }}</div>
        <div class="footer-right">
            {{ $footer['right'] ?? '' }}
            <br/><span class="page-number"></span>
        </div>
    </div>
@endif

{{-- Contenido del reporte --}}
@yield('content')

</body>
</html>
