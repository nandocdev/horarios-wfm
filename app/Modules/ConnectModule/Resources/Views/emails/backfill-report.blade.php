<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1e293b; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { border: 1px solid #e2e8f0; border-top: none; padding: 20px; border-radius: 0 0 8px 8px; }
        .stat-card { background: #f8fafc; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border-bottom: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        .table th { background: #f1f5f9; }
        .error-list { color: #dc2626; background: #fef2f2; padding: 15px; border-radius: 4px; border: 1px solid #fecaca; }
        .footer { font-size: 0.8em; color: #64748b; text-align: center; margin-top: 20px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.85em; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Resumen de Sincronización</h1>
        <p>Backfill Histórico - {{ $date }}</p>
    </div>
    <div class="content">
        <div class="stat-card">
            <h3 style="margin-top:0">Total de Registros</h3>
            <span style="font-size: 2em; font-weight: bold; color: #2563eb;">{{ number_format($stats['total_records']) }}</span>
        </div>

        <h3>Desglose por Módulo</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Reporte</th>
                    <th>Registros</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['by_type'] as $type => $count)
                <tr>
                    <td>{{ ucfirst($type) }}</td>
                    <td>{{ number_format($count) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if(!empty($stats['errors']))
        <h3 style="color: #dc2626;">Errores detectados ({{ count($stats['errors']) }})</h3>
        <div class="error-list">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($stats['errors'] as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @else
        <p><span class="badge badge-success">✓</span> No se reportaron errores durante el procesamiento de este día.</p>
        @endif

        <div class="footer">
            Este es un reporte automático generado por el Sistema WFM.
        </div>
    </div>
</body>
</html>
