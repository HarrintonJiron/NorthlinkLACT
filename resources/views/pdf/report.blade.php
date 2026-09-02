<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $data['title'] }}</title>
<style>
    @page { margin: 26px 30px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1D1D1F; font-size: 11px; }
    .header { border-bottom: 2px solid #007AFF; padding-bottom: 10px; margin-bottom: 18px; }
    .header .brand { font-size: 10px; font-weight: bold; color: #007AFF; letter-spacing: 1px; }
    .header h1 { font-size: 21px; margin: 5px 0 3px; color: #1D1D1F; }
    .header .meta { font-size: 10px; color: #6E6E73; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.data thead th { background: #F5F5F7; color: #6E6E73; font-size: 8.5px; font-weight: bold; letter-spacing: 0.4px; text-align: left; padding: 7px 8px; border-bottom: 1px solid #E5E5E5; }
    table.data tbody td { padding: 6px 8px; border-bottom: 1px solid #EFEFF1; font-size: 10px; }
    table.data tbody tr:nth-child(even) { background: #FAFAFB; }
    table.data tfoot .totals-row td { padding: 8px; font-size: 11px; border-top: 2px solid #E5E5E5; background: #FAFAFB; }
    table.data tfoot .totals-label { text-align: right; color: #6E6E73; }
    table.data tfoot .totals-value { text-align: right; font-weight: bold; color: #187A31; white-space: nowrap; }
    .empty { padding: 30px; text-align: center; color: #8E8E93; font-size: 11px; }
    .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #EFEFF1; font-size: 8px; color: #B0B0B5; text-align: center; }
</style>
</head>
<body>
    <div class="header">
        <div class="brand">NORTHLINK LACT &middot; NÓMINA</div>
        <h1>{{ $data['title'] }}</h1>
        <div class="meta">Periodo: {{ $rangeLabel }} &middot; Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    @if (count($data['rows']))
        <table class="data">
            <thead>
                <tr>
                    @foreach ($data['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if ($data['totals'])
                <tfoot>
                    @foreach ($data['totals'] as $label => $value)
                        <tr class="totals-row">
                            <td class="totals-label" colspan="{{ max(1, count($data['columns']) - 1) }}">{{ $label }}</td>
                            <td class="totals-value">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tfoot>
            @endif
        </table>
    @else
        <div class="empty">No hay registros para el rango seleccionado.</div>
    @endif

    <div class="footer">Northlink LACT &middot; Documento generado automáticamente</div>
</body>
</html>
