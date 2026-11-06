<?php

namespace App\Http\Controllers;

use App\Models\RouteRun;
use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Services\RouteService;
use App\Modules\Production\Models\ProductionBatch;
use App\Modules\Ruteros\Models\Rutero;
use App\Services\ExcelReportService;
use App\Services\OfficialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleReportController extends Controller
{
    /**
     * @var array<string, array{title: string, subtitle: string, status: string}>
     */
    private array $modules = [
        'dashboard' => [
            'title' => 'Dashboard Ejecutivo',
            'subtitle' => 'Resumen gerencial de operaciones, alertas y rendimiento diario.',
            'status' => 'Prototipo funcional con datos operativos.',
        ],
        'sumni' => [
            'title' => 'Operación Sumni / Offline',
            'subtitle' => 'Control de acopio en campo, sincronización y recibos.',
            'status' => 'Interfaz y flujo operativo en avance.',
        ],
        'routes' => [
            'title' => 'Rutas de Acopio',
            'subtitle' => 'Seguimiento de rutas, ruteros asignados y cobertura de productores.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'ruteros' => [
            'title' => 'Ruteros',
            'subtitle' => 'Gestión de responsables de ruta, vehículos y disponibilidad.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'collections' => [
            'title' => 'Recolecciones de Leche',
            'subtitle' => 'Registro diario de litros, calidad básica y trazabilidad de entregas.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'producers' => [
            'title' => 'Productores',
            'subtitle' => 'Catálogo de productores, rutas y condiciones operativas.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'finances' => [
            'title' => 'Finanzas',
            'subtitle' => 'Movimientos, gastos, pagos e ingresos para control administrativo.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'payment-sheets' => [
            'title' => 'Planillas de Pago',
            'subtitle' => 'Preparación visual de liquidaciones a productores.',
            'status' => 'Cierre formal, fotografía inmutable y pago trazable.',
        ],
        'payments' => [
            'title' => 'Pagos',
            'subtitle' => 'Seguimiento visual de programación, aprobación y conciliación.',
            'status' => 'Flujo financiero auditable y conciliable.',
        ],
        'production' => [
            'title' => 'Producción',
            'subtitle' => 'Lotes, leche procesada, pesajes y rendimiento administrativo.',
            'status' => 'Módulo funcional de producción y rendimiento.',
        ],
        'dispatches' => [
            'title' => 'Remisiones',
            'subtitle' => 'Salidas de producto terminado por lote y destino.',
            'status' => 'Módulo funcional conectado al kardex.',
        ],
        'inventory' => [
            'title' => 'Inventario',
            'subtitle' => 'Control de insumos, stock mínimo y alertas operativas.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'personnel' => [
            'title' => 'Personal',
            'subtitle' => 'Gestión de empleados, expedientes y asistencia.',
            'status' => 'Módulo funcional con reporte de avance.',
        ],
        'reports' => [
            'title' => 'Centro de Reportes',
            'subtitle' => 'Catálogo de reportes ejecutivos y operativos por módulo.',
            'status' => 'Interfaz demostrativa; reportes PDF base disponibles.',
        ],
        'settings' => [
            'title' => 'Configuración',
            'subtitle' => 'Administración de usuarios, permisos y parámetros del sistema.',
            'status' => 'Módulo funcional parcial con reporte de avance.',
        ],
    ];

    public function __invoke(string $module, OfficialReportService $reports): Response
    {
        $report = $this->modules[$module] ?? null;

        if (! $report || $module === 'payroll') {
            throw new NotFoundHttpException;
        }

        /** @var User $user */
        $user = request()->user();
        abort_unless($this->canAccess($user, $module), 403);

        $filters = request()->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $data = $reports->build($module, $user, $filters['from'] ?? null, $filters['to'] ?? null);
        $company = Company::query()->first();
        $filename = str($module)->replace('-', '_')->append('_northlink_quebar.pdf')->toString();

        return Pdf::loadView('pdf.report', compact('data', 'company', 'user'))
            ->setPaper('a4', 'landscape')
            ->stream($filename);
    }

    public function excel(string $module, OfficialReportService $reports, ExcelReportService $excel): Response
    {
        $report = $this->modules[$module] ?? null;
        if (! $report || $module === 'payroll') {
            throw new NotFoundHttpException;
        }
        /** @var User $user */
        $user = request()->user();
        abort_unless($this->canAccess($user, $module), 403);
        $filters = request()->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $data = $reports->build($module, $user, $filters['from'] ?? null, $filters['to'] ?? null);
        $workbook = $excel->build($data);

        return response($workbook, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.str($module)->replace('-', '_').'_northlink_quebar.xlsx"',
        ]);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function pdf(array $lines): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $content = "BT\n/F2 18 Tf\n52 740 Td\n";
        foreach ($lines as $index => $line) {
            $font = $index < 2 ? '/F2 18 Tf' : '/F1 10 Tf';
            $leading = $index < 2 ? 24 : 16;
            $safeLine = $this->escapePdfText($line);
            $content .= "{$font}\n({$safeLine}) Tj\n0 -{$leading} Td\n";
        }
        $content .= "ET\n";

        $objects[] = '<< /Length '.strlen($content)." >>\nstream\n{$content}endstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $number = $index + 1;
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        $normalized = str($text)
            ->ascii()
            ->replaceMatches('/[^\x20-\x7E]/', ' ')
            ->limit(105, '')
            ->toString();

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $normalized);
    }

    private function canAccess(User $user, string $module): bool
    {
        if ($user->is_admin || $module === 'dashboard') {
            return true;
        }

        $permissionModule = match ($module) {
            'payment-sheets', 'payments' => 'finances',
            'dispatches' => 'production',
            'settings' => 'administrator',
            default => $module,
        };

        return $user->hasPermission("access_{$permissionModule}");
    }

    /** @return array<int, string> */
    private function metricLines(string $module, User $user): array
    {
        $routeIds = app(RouteService::class)->accessibleQuery($user)->select('routes.id');
        $collections = MilkCollection::query()->whereIn('route_id', (clone $routeIds));

        return match ($module) {
            'collections' => [
                'Acopios registrados: '.(clone $collections)->count(),
                'Litros acumulados: '.number_format((float) (clone $collections)->sum('liters'), 2, '.', ','),
                'Conflictos de sincronización: '.(clone $collections)->where('sync_status', RouteRun::SYNC_CONFLICT)->count(),
            ],
            'routes' => [
                'Rutas visibles: '.app(RouteService::class)->accessibleQuery($user)->count(),
                'Rutas activas: '.app(RouteService::class)->accessibleQuery($user)->where('active', true)->count(),
            ],
            'producers' => [
                'Productores vinculados: '.Producer::query()
                    ->whereHas('assignments', fn ($query) => $query->whereIn('route_id', (clone $routeIds)))
                    ->distinct()
                    ->count(),
            ],
            'ruteros' => [
                'Ruteros vinculados: '.Rutero::query()->whereIn('route_id', (clone $routeIds))->count(),
                'Ruteros activos: '.Rutero::query()->whereIn('route_id', (clone $routeIds))->where('active', true)->count(),
            ],
            'sumni' => [
                'Jornadas sincronizadas: '.RouteRun::query()
                    ->whereIn('route_id', (clone $routeIds))
                    ->where('sync_status', RouteRun::SYNC_SYNCED)
                    ->count(),
                'Acopios recibidos desde campo: '.(clone $collections)->whereNotNull('route_run_id')->count(),
            ],
            'finances', 'payment-sheets', 'payments' => [
                'Movimientos financieros: '.FinanceTransaction::query()->count(),
                'Monto acumulado: C$ '.number_format((float) FinanceTransaction::query()->sum('amount'), 2, '.', ','),
            ],
            'inventory' => [
                'Productos de inventario: '.InventoryProduct::query()->count(),
                'Productos bajo mínimo: '.InventoryProduct::query()->whereColumn('stock', '<=', 'min_stock')->count(),
            ],
            'personnel' => [
                'Empleados registrados: '.$this->employeeCount($user),
            ],
            'production' => [
                'Lotes registrados: '.ProductionBatch::query()->count(),
                'Lotes abiertos: '.ProductionBatch::query()->whereIn('status', [
                    ProductionBatch::STATUS_PLANNED,
                    ProductionBatch::STATUS_IN_PROCESS,
                    ProductionBatch::STATUS_WEIGHING,
                ])->count(),
                'Lotes liberados: '.ProductionBatch::query()->where('status', ProductionBatch::STATUS_RELEASED)->count(),
            ],
            'dashboard' => [
                'Rutas activas: '.app(RouteService::class)->accessibleQuery($user)->where('active', true)->count(),
                'Acopios registrados: '.(clone $collections)->count(),
                'Litros acumulados: '.number_format((float) (clone $collections)->sum('liters'), 2, '.', ','),
            ],
            default => ['Sin indicadores oficiales disponibles para este módulo.'],
        };
    }

    private function employeeCount(User $user): int
    {
        if ($user->is_admin) {
            return Employee::query()->count();
        }

        $companyIds = $user->companies()->pluck('companies.id');
        $plantIds = $user->plants()->pluck('plants.id')
            ->merge(Plant::query()->whereIn('company_id', $companyIds)->pluck('id'))
            ->unique();

        return Employee::query()->whereIn('plant_id', $plantIds)->count();
    }
}
