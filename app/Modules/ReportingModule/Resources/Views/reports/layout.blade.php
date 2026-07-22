<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 22mm 15mm 22mm 15mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1f2937; margin: 0; padding: 0; }

        /* ─── 1. MEMBRETE ─── */
        .membrete {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .membrete-logo img { max-height: 40px; }
        .membrete-info { text-align: right; font-size: 8pt; color: #6b7280; line-height: 1.5; }
        .membrete-info strong { color: #1f2937; font-size: 10pt; display: block; }

        /* ─── 2. ENCABEZADO ─── */
        .encabezado { margin-bottom: 14px; }
        .encabezado h1 { font-size: 13pt; color: #1e3a5f; margin: 0 0 8px 0; }
        .encabezado-filtros { font-size: 7.5pt; color: #6b7280; line-height: 1.8; }
        .encabezado-filtros .tag {
            display: inline-block;
            background: #f3f4f6;
            padding: 1px 7px;
            border-radius: 3px;
            margin: 1px 3px 1px 0;
            white-space: nowrap;
        }
        .encabezado-filtros .tag strong { color: #374151; }

        /* ─── 3. CUERPO ─── */
        .cuerpo { }

        /* Cards de KPIs */
        .cards { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px 14px;
            text-align: center;
            min-width: 100px;
            flex: 1;
        }
        .card-label { font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .card-value { font-size: 14pt; font-weight: bold; color: #1e3a5f; margin-top: 2px; }

        /* Tabla con encabezado repetido por página */
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        thead { display: table-header-group; }
        th {
            background: #1e3a5f;
            color: white;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }

        /* ─── 4. PIE DE PÁGINA ─── */
        .pie {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #d1d5db;
            padding-top: 5px;
            font-size: 7pt;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
        }

        /* Marca de agua opcional */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 48pt;
            color: rgba(0,0,0,0.04);
            z-index: -1;
        }
    </style>
</head>
<body>
    @if(isset($watermark) && $watermark)
        <div class="watermark">{{ $watermark }}</div>
    @endif

    {{-- ═══════ 1. MEMBRETE ═══════ --}}
    <div class="membrete">
        <div class="membrete-logo">
            <img src="{{ $logo }}" alt="Logo">
        </div>
        <div class="membrete-info">
            <strong>Sistema WFM — Caja de Seguro Social de Panamá</strong>
            Módulo de Workforce Management<br>
            Generado: {{ $date }} | Por: {{ $user }} ({{ $userRole }})
        </div>
    </div>

    {{-- ═══════ 2. ENCABEZADO ═══════ --}}
    <div class="encabezado">
        <h1>{{ $title }}</h1>
        @if(isset($dateFrom) && isset($dateTo))
            <div class="encabezado-filtros">
                <span class="tag"><strong>Período:</strong> {{ $dateFrom }} → {{ $dateTo }}</span>
                @if(isset($filters) && is_array($filters))
                    @foreach($filters as $key => $value)
                        @if($value && !in_array($key, ['dateFrom', 'dateTo']))
                            <span class="tag"><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</span>
                        @endif
                    @endforeach
                @endif
            </div>
        @endif
    </div>

    {{-- ═══════ 3. CUERPO ═══════ --}}
    <div class="cuerpo">
        @hasSection('cards')
            <div class="cards">
                @yield('cards')
            </div>
        @endif
        @yield('content')
    </div>

    {{-- ═══════ 4. PIE DE PÁGINA ═══════ --}}
    <div class="pie">
        <span>Confidencial · uso interno</span>
        <span>ID: RPT-{{ date('Ymd') }}-{{ str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) }}</span>
    </div>

    {{-- Numeración de páginas via DomPDF inline script --}}
    <script type="text/php">
        if (isset($pdf)) {
            $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
            $pdf->page_text(308, $pdf->get_height() - 17, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7, [156, 163, 175]);
        }
    </script>
</body>
</html>
