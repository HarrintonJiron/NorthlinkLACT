<?php

namespace App\Services;

use App\Models\Device;
use App\Models\RouteRun;
use App\Models\User;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Services\RouteService;
use App\Modules\Production\Models\ProductDispatch;
use App\Modules\Production\Models\ProductionBatch;
use Carbon\Carbon;

class OfficialReportService
{
    public function build(string $module, User $user, ?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(30)->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        return match ($module) {
            'collections' => $this->collections($user, $fromDate, $toDate),
            'inventory' => $this->inventory($fromDate, $toDate),
            'finances', 'payments' => $this->finances($fromDate, $toDate),
            'production' => $this->production($fromDate, $toDate),
            'payment-sheets' => $this->paymentSheets($fromDate, $toDate),
            'producers' => $this->producers(),
            'routes' => $this->routes($user),
            'sumni' => $this->devices($user),
            'dispatches' => $this->dispatches($fromDate, $toDate),
            default => $this->summary($module),
        } + ['from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()];
    }

    private function collections(User $user, Carbon $from, Carbon $to): array
    {
        $routeIds = app(RouteService::class)->accessibleQuery($user)->select('routes.id');
        $records = MilkCollection::query()->with(['producer:id,code,full_name', 'route:id,code,name'])
            ->whereIn('route_id', $routeIds)->whereBetween('collection_date', [$from, $to])->orderBy('collection_date')->limit(5000)->get();

        return ['title' => 'Reporte oficial de acopio', 'columns' => ['Fecha', 'Productor', 'Ruta', 'Litros', 'Densidad', 'Estado', 'Corrección', 'Observaciones'],
            'rows' => $records->map(fn ($r) => [$r->collection_date->format('d/m/Y'), $r->producer?->full_name, $r->route?->code, number_format((float) $r->liters, 2), $r->density ?? $r->temperature ?? '—', $r->sync_status, $r->correction_status ?? '—', $r->notes ?? '—'])->all(),
            'totals' => ['Registros' => $records->count(), 'Litros' => number_format((float) $records->sum('liters'), 2)]];
    }

    private function inventory(Carbon $from, Carbon $to): array
    {
        $records = InventoryMovement::query()->with(['product.unit', 'user:id,name'])->whereBetween('created_at', [$from, $to])->orderBy('created_at')->limit(5000)->get();

        return ['title' => 'Kardex oficial de inventario', 'columns' => ['Fecha', 'Código', 'Producto', 'Tipo', 'Movimiento', 'Saldo', 'Motivo', 'Usuario'],
            'rows' => $records->map(fn ($r) => [$r->created_at->format('d/m/Y H:i'), $r->product?->code, $r->product?->name, $r->type, ($r->direction > 0 ? '+' : '-').number_format((float) $r->quantity, 3), number_format((float) $r->new_stock, 3), $r->reason, $r->user?->name ?? 'Sistema'])->all(),
            'totals' => ['Movimientos' => $records->count(), 'Productos activos' => InventoryProduct::query()->where('active', true)->count()]];
    }

    private function finances(Carbon $from, Carbon $to): array
    {
        $records = FinanceTransaction::query()->with('category')->whereBetween('transaction_date', [$from, $to])->orderBy('transaction_date')->limit(5000)->get();

        return ['title' => 'Reporte oficial financiero', 'columns' => ['Fecha', 'Código', 'Tipo', 'Categoría', 'Concepto', 'Beneficiario', 'Referencia', 'Estado', 'Monto'],
            'rows' => $records->map(fn ($r) => [$r->transaction_date->format('d/m/Y'), $r->code, $r->type, $r->category?->name, $r->concept, $r->payee, $r->reference, $r->status, number_format((float) $r->amount, 2)])->all(),
            'totals' => ['Ingresos' => 'C$ '.number_format((float) $records->where('type', 'ingreso')->sum('amount'), 2), 'Salidas' => 'C$ '.number_format((float) $records->whereIn('type', ['gasto', 'pago'])->sum('amount'), 2)]];
    }

    private function production(Carbon $from, Carbon $to): array
    {
        $records = ProductionBatch::query()->with(['milkInputs', 'activeWeightEntries', 'costs'])->whereBetween('created_at', [$from, $to])->orderBy('created_at')->limit(5000)->get();

        return ['title' => 'Reporte oficial de producción', 'columns' => ['Lote', 'Estado', 'Litros', 'Queso lb', 'Subproductos lb', 'Merma lb', 'Costos C$'],
            'rows' => $records->map(fn ($r) => [$r->batch_number, $r->status, number_format((float) $r->milkInputs->sum('liters'), 3), number_format((float) $r->activeWeightEntries->sum('weight_lb'), 3), number_format((float) $r->cream_weight_lb + (float) $r->trimmings_weight_lb, 3), number_format((float) $r->waste_weight_lb, 3), number_format((float) $r->costs->sum('amount'), 2)])->all(),
            'totals' => ['Lotes' => $records->count(), 'Litros procesados' => number_format((float) $records->sum(fn ($r) => $r->milkInputs->sum('liters')), 3), 'Costos' => 'C$ '.number_format((float) $records->sum(fn ($r) => $r->costs->sum('amount')), 2)]];
    }

    private function paymentSheets(Carbon $from, Carbon $to): array
    {
        $records = ProducerPaymentBatch::query()->whereBetween('week_end', [$from, $to])->orderBy('week_end')->get();

        return ['title' => 'Cierre de planillas de productores', 'columns' => ['Código', 'Semana inicial', 'Semana final', 'Estado', 'Productores', 'Litros', 'Bruto C$', 'Adelantos C$', 'Neto C$'],
            'rows' => $records->map(fn ($r) => [$r->code, $r->week_start->format('d/m/Y'), $r->week_end->format('d/m/Y'), $r->status, $r->producer_count, $r->total_liters, $r->gross_amount, $r->advance_amount, $r->net_amount])->all(),
            'totals' => ['Planillas' => $records->count(), 'Neto pagable' => 'C$ '.number_format((float) $records->sum('net_amount'), 2)]];
    }

    private function producers(): array
    {
        $records = Producer::query()->with('activeAssignment.route')->orderBy('full_name')->limit(5000)->get();

        return ['title' => 'Catálogo oficial de productores', 'columns' => ['Código', 'Nombre', 'Cédula', 'Teléfono', 'Comunidad', 'Ruta', 'Pago', 'Banco', 'Cuenta', 'Precio preferencial', 'Estado'],
            'rows' => $records->map(fn ($r) => [$r->code, $r->full_name, $r->identity_number, $r->phone, $r->community, $r->activeAssignment?->route?->code, $r->payment_method, $r->bank_name, $this->maskAccount($r->bank_account_number), $r->preferred_price_per_liter, $r->active ? 'Activo' : 'Inactivo'])->all(), 'totals' => ['Productores' => $records->count()]];
    }

    private function routes(User $user): array
    {
        $records = app(RouteService::class)->accessibleQuery($user)
            ->with(['rutero', 'activeAssignments'])
            ->orderBy('name')
            ->get();

        return ['title' => 'Catálogo y cobertura de rutas', 'columns' => ['Código', 'Ruta', 'Propietario', 'Rutero', 'Placa', 'Productores', 'Estado'],
            'rows' => $records->map(fn (Route $route) => [$route->code, $route->name, $route->rutero?->owner_name, $route->rutero?->driver_name, $route->rutero?->vehicle_plate, $route->activeAssignments->count(), $route->active ? 'Activa' : 'Inactiva'])->all(),
            'totals' => ['Rutas' => $records->count(), 'Productores asignados' => $records->sum(fn (Route $route) => $route->activeAssignments->count())]];
    }

    private function devices(User $user): array
    {
        abort_unless($user->is_admin, 403);
        $records = Device::query()->with('user:id,username')->withCount(['routeRuns', 'milkCollections'])->orderBy('name')->get();

        return ['title' => 'Control de dispositivos SUNMI', 'columns' => ['Equipo', 'UUID', 'Plataforma', 'Cuenta actual', 'Estado', 'Última conexión', 'Jornadas', 'Acopios'],
            'rows' => $records->map(fn (Device $device) => [$device->name, $device->device_uuid, $device->platform, $device->user?->username, $device->active ? 'Activo' : 'Bloqueado', $device->last_seen_at?->format('d/m/Y H:i'), $device->route_runs_count, $device->milk_collections_count])->all(),
            'totals' => ['Equipos' => $records->count(), 'Activos' => $records->where('active', true)->count(), 'Jornadas' => RouteRun::query()->count()]];
    }

    private function maskAccount(?string $account): string
    {
        if (blank($account)) {
            return '—';
        }

        return '•••• '.mb_substr($account, -4);
    }

    private function dispatches(Carbon $from, Carbon $to): array
    {
        $records = ProductDispatch::query()->with('items.product')->whereBetween('dispatch_date', [$from, $to])->orderBy('dispatch_date')->get();

        return ['title' => 'Reporte oficial de remisiones', 'columns' => ['Fecha', 'Código', 'Destino', 'Recibe', 'Estado', 'Renglones', 'Costo total C$'],
            'rows' => $records->map(fn ($r) => [$r->dispatch_date->format('d/m/Y'), $r->code, $r->destination, $r->recipient, $r->status, $r->items->count(), number_format((float) $r->items->sum(fn ($i) => (float) $i->quantity * (float) $i->unit_cost), 2)])->all(), 'totals' => ['Remisiones' => $records->count()]];
    }

    private function summary(string $module): array
    {
        return ['title' => 'Resumen oficial: '.str($module)->replace('-', ' ')->title(), 'columns' => ['Indicador', 'Valor'], 'rows' => [['Módulo', $module], ['Generado', now()->format('d/m/Y H:i')]], 'totals' => []];
    }
}
