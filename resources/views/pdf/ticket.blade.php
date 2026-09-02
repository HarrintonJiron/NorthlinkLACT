<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Ticket {{ $data['title'] }}</title>
<style>
    @page { margin: 12px 8px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #1D1D1F; font-size: 9px; }
    .center { text-align: center; }
    .brand { font-size: 10.5px; font-weight: bold; letter-spacing: 1px; }
    .title { font-size: 12px; font-weight: bold; margin: 5px 0 2px; }
    .meta { font-size: 8px; color: #444; margin-bottom: 1px; }
    .divider { border-top: 1px dashed #999; margin: 7px 0; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data td { padding: 3.5px 0; font-size: 8.5px; vertical-align: top; }
    .right { text-align: right; }
    table.totals { width: 100%; margin-top: 4px; }
    table.totals td { font-size: 9.5px; padding: 3px 0; font-weight: bold; }
    .footer { margin-top: 10px; font-size: 7.5px; color: #777; text-align: center; }
    .empty { padding: 14px 0; color: #888; text-align: center; }
</style>
</head>
<body>
    <div class="center">
        <div class="brand">NORTHLINK LACT</div>
        <div class="title">{{ $data['title'] }}</div>
        <div class="meta">{{ $employee->full_name }}</div>
        @if ($employee->role)
            <div class="meta">{{ $employee->role->name }}</div>
        @endif
        <div class="meta">{{ $rangeLabel }}</div>
    </div>
    <div class="divider"></div>

    @if (count($data['rows']))
        <table class="data">
            @foreach ($data['rows'] as $row)
                <tr>
                    <td>{{ implode(' · ', $row) }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="empty">Sin registros en este rango.</div>
    @endif

    @if ($data['totals'])
        <div class="divider"></div>
        <table class="totals">
            @foreach ($data['totals'] as $label => $value)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="right">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="divider"></div>
    <div class="footer">Generado el {{ now()->format('d/m/Y H:i') }}<br>Documento informativo, no es comprobante fiscal.</div>
</body>
</html>
