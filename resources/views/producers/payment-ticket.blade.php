<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $batch->code }} · {{ $item->producer_name }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: 80mm auto; margin: 2mm; }
        body { width: 76mm; margin: 0 auto; padding: 2mm; color: #111; background: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; }
        h1, p { margin: 0; }
        .center { text-align: center; }
        .title { font-size: 17px; font-weight: 800; letter-spacing: .3px; }
        .subtitle { margin-top: 2px; font-size: 10px; }
        .rule { border-top: 1px dashed #111; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
        .row span:first-child { color: #333; }
        .row strong { text-align: right; }
        .days { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .days th, .days td { padding: 2px 0; border-bottom: 1px dotted #aaa; text-align: right; }
        .days th:first-child, .days td:first-child { text-align: left; }
        .total { font-size: 15px; font-weight: 800; }
        .note { margin-top: 6px; font-size: 9px; }
        .actions { display: flex; justify-content: center; gap: 8px; margin: 16px 0; }
        .actions button, .actions a { border: 0; border-radius: 7px; padding: 9px 12px; background: #111; color: #fff; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .actions a { background: #666; }
        @media print {
            body { width: 76mm; padding: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <header class="center">
        <h1 class="title">LÁCTEOS QUEBAR</h1>
        <p class="subtitle">Comprobante de pago de leche</p>
        <p class="subtitle">{{ $company?->legal_name ?: $company?->name }}</p>
    </header>

    <div class="rule"></div>
    <div class="row"><span>Planilla</span><strong>{{ $batch->code }}</strong></div>
    <div class="row"><span>Estado</span><strong>{{ $batch->status === 'paid' ? 'PAGADA' : 'CERRADA' }}</strong></div>
    <div class="row"><span>Semana</span><strong>{{ $batch->week_start->format('d/m/Y') }} - {{ $batch->week_end->format('d/m/Y') }}</strong></div>
    <div class="row"><span>Productor</span><strong>{{ $item->producer_name }}</strong></div>
    <div class="row"><span>Código</span><strong>{{ $item->producer_code }}</strong></div>
    <div class="row"><span>Ruta</span><strong>{{ $item->route_code ?: 'Sin ruta' }}</strong></div>

    <div class="rule"></div>
    <table class="days">
        <thead><tr><th>Día</th><th>Litros</th></tr></thead>
        <tbody>
        @foreach ($item->daily_liters as $date => $liters)
            <tr>
                <td>{{ \Carbon\Carbon::parse($date)->locale('es')->translatedFormat('D d/m') }}</td>
                <td>{{ number_format((float) $liters, 2) }} L</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="rule"></div>
    <div class="row"><span>Total de litros</span><strong>{{ number_format((float) $item->liters, 2) }} L</strong></div>
    <div class="row"><span>Precio por litro</span><strong>C$ {{ number_format((float) $item->price_per_liter, 2) }}</strong></div>
    <div class="row"><span>Monto bruto</span><strong>C$ {{ number_format((float) $item->gross_amount, 2) }}</strong></div>
    <div class="row"><span>Adelantos/deducciones</span><strong>- C$ {{ number_format((float) $item->advance_amount, 2) }}</strong></div>
    <div class="rule"></div>
    <div class="row total"><span>NETO PAGADO</span><strong>C$ {{ number_format((float) $item->net_amount, 2) }}</strong></div>

    @if ($item->notes)
        <p class="note"><strong>Nota:</strong> {{ $item->notes }}</p>
    @endif
    <p class="note center">Emitido {{ now()->format('d/m/Y h:i A') }}</p>
    <p class="note center">Gracias por su entrega. Dios lo bendiga.</p>
    <p class="note center">Desarrollado por Northlink Microsystem</p>

    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir 80 mm</button>
        <a href="{{ route('payment-sheets.index', ['week' => $batch->week_end->toDateString()]) }}">Volver</a>
    </div>
</body>
</html>
