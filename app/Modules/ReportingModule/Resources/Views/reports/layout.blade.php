<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 20mm 15mm 25mm 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1f2937; margin: 0; padding: 0; }
        .membrete { border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-start; }
        .membrete-logo img { max-height: 40px; }
        .membrete-info { text-align: right; font-size: 8pt; color: #6b7280; line-height: 1.4; }
        .membrete-info strong { color: #1f2937; font-size: 10pt; }
        .encabezado { margin-bottom: 14px; }
        .encabezado h1 { font-size: 13pt; color: #1e3a5f; margin: 0 0 6px 0; }
        .encabezado-filtros { font-size: 8pt; color: #6b7280; }
        .encabezado-filtros span { background: #f3f4f6; padding: 1px 6px; border-radius: 3px; margin-right: 4px; }
        .cards { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
        .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 14px; text-align: center; min-width: 100px; flex: 1; }
        .card-label { font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-value { font-size: 14pt; font-weight: bold; color: #1e3a5f; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        th { background: #1e3a5f; color: white; padding: 6px 8px; text-align: left; font-weight: 600; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.3px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .pie { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #d1d5db; padding-top: 6px; font-size: 7pt; color: #9ca3af; display: flex; justify-content: space-between; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 48pt; color: rgba(0,0,0,0.04); z-index: -1; }
    </style>
</head>
<body>
    @if(isset($watermark) && $watermark)
        <div class="watermark">{{ $watermark }}</div>
    @endif

    <div class="membrete">
        <div class="membrete-logo">
            <img src="{{ $logo }}" alt="Logo">
        </div>
        <div class="membrete-info">
            <strong>Sistema WFM — Caja de Seguro Social de Panamá</strong><br>
            Módulo de Workforce Management<br>
            Generado: {{ $date }} | Por: {{ $user }}
        </div>
    </div>

    <div class="encabezado">
        <h1>{{ $title }}</h1>
        <div class="encabezado-filtros">
            @if(isset($dateFrom) && isset($dateTo))
                <span>Período: {{ $dateFrom }} → {{ $dateTo }}</span>
            @endif
            @if(isset($filters) && is_array($filters))
                @foreach($filters as $key => $value)
                    @if($value)
                        <span>{{ ucfirst($key) }}: {{ $value }}</span>
                    @endif
                @endforeach
            @endif
        </div>
    </div>

    <div class="cuerpo">
        @yield('cards')
        @yield('content')
    </div>

    <div class="pie">
        <span>Confidencial · uso interno</span>
        <span>Página {PAGE_NUM} de {PAGE_COUNT}</span>
        <span>ID: RPT-{{ date('Ymd') }}-{{ str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) }}</span>
    </div>
</body>
</html>
