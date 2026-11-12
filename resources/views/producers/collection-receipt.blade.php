<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de acopio #{{ $collection->id }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: 80mm auto; margin: 2mm; }
        body { width: 76mm; margin: 0 auto; padding: 2mm; color: #111; background: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.4; }
        h1, p { margin: 0; }
        .center { text-align: center; }
        .title { font-size: 17px; font-weight: 800; }
        .subtitle { margin-top: 2px; font-size: 10px; }
        .rule { border-top: 1px dashed #111; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin: 4px 0; }
        .row span:first-child { color: #333; }
        .row strong { max-width: 48mm; text-align: right; }
        .liters { font-size: 20px; font-weight: 900; }
        .note { margin-top: 7px; font-size: 9px; }
        .actions { display: flex; justify-content: center; gap: 8px; margin: 16px 0; }
        .actions button, .actions a { border: 0; border-radius: 7px; padding: 9px 12px; background: #111; color: #fff; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .actions a { background: #666; }
        @media print { body { width: 76mm; padding: 0; } .actions { display: none; } }
    </style>
</head>
<body>
    <header class="center">
        <h1 class="title">LÁCTEOS QUEBAR</h1>
        <p class="subtitle">Recibo de entrega de leche</p>
        <p class="subtitle">Consultas: 8916-9856</p>
    </header>

    <div class="rule"></div>
    <div class="row"><span>Recibo</span><strong>#{{ $collection->id }}</strong></div>
    <div class="row"><span>Fecha</span><strong>{{ $collection->collection_date->format('d/m/Y') }}</strong></div>
    <div class="row"><span>Productor</span><strong>{{ $collection->producer?->full_name }}</strong></div>
    <div class="row"><span>Código</span><strong>{{ $collection->producer?->code }}</strong></div>
    <div class="row"><span>Ruta</span><strong>{{ $collection->route?->code }} · {{ $collection->route?->name }}</strong></div>
    <div class="rule"></div>
    <div class="row liters"><span>LECHE</span><strong>{{ number_format((float) $collection->liters, 2) }} L</strong></div>
    @if ($collection->density)
        <div class="row"><span>Densidad</span><strong>{{ number_format((float) $collection->density, 3) }}</strong></div>
    @endif
    @if ($collection->notes)
        <p class="note"><strong>Observación:</strong> {{ $collection->notes }}</p>
    @endif
    <div class="rule"></div>
    <p class="note center">Registrado por {{ $collection->collectedBy?->name ?: 'Administración' }}</p>
    <p class="note center">Emitido {{ now()->format('d/m/Y h:i A') }}</p>
    <p class="note center">Gracias por su entrega. Dios lo bendiga.</p>
    <p class="note center">Desarrollado por Northlink Microsystem</p>

    <div class="actions">
        <button type="button" onclick="window.print()">Imprimir 80 mm</button>
        <a href="{{ route('collections.show', $collection) }}">Volver</a>
    </div>
</body>
</html>
