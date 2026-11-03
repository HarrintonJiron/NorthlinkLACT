<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ReportCenterController extends Controller
{
    /** @var array<int, array<string, string>> */
    private array $reports = [
        ['module' => 'dashboard', 'permission' => '', 'title' => 'Resumen ejecutivo', 'description' => 'Indicadores generales, alertas y actividad reciente.', 'href' => '/', 'status' => 'functional'],
        ['module' => 'collections', 'permission' => 'collections', 'title' => 'Acopio de leche', 'description' => 'Litros, productores, rutas, calidad y estado de sincronización.', 'href' => '/collections', 'status' => 'functional'],
        ['module' => 'routes', 'permission' => 'routes', 'title' => 'Rutas de acopio', 'description' => 'Cobertura, responsables, actividad y avance operativo.', 'href' => '/routes', 'status' => 'functional'],
        ['module' => 'producers', 'permission' => 'producers', 'title' => 'Productores', 'description' => 'Registro, asignación de ruta y resumen de entregas.', 'href' => '/producers', 'status' => 'functional'],
        ['module' => 'ruteros', 'permission' => 'ruteros', 'title' => 'Ruteros', 'description' => 'Responsables, vehículos, disponibilidad y rutas asignadas.', 'href' => '/ruteros', 'status' => 'functional'],
        ['module' => 'sumni', 'permission' => 'sumni', 'title' => 'Operación offline', 'description' => 'Jornadas de campo, recibos y sincronización con dispositivos.', 'href' => '/sumni', 'status' => 'functional'],
        ['module' => 'finances', 'permission' => 'finances', 'title' => 'Finanzas', 'description' => 'Ingresos, gastos, pagos y movimientos administrativos.', 'href' => '/finanzas', 'status' => 'functional'],
        ['module' => 'inventory', 'permission' => 'inventory', 'title' => 'Inventario', 'description' => 'Existencias, mínimos y productos que requieren atención.', 'href' => '/inventory', 'status' => 'functional'],
        ['module' => 'personnel', 'permission' => 'personnel', 'title' => 'Personal', 'description' => 'Colaboradores, asistencia, documentos y novedades.', 'href' => '/employees', 'status' => 'functional'],
        ['module' => 'production', 'permission' => 'production', 'title' => 'Producción', 'description' => 'Lotes, leche procesada, pesajes y rendimientos separados.', 'href' => '/production', 'status' => 'functional'],
        ['module' => 'dispatches', 'permission' => 'production', 'title' => 'Remisiones', 'description' => 'Producto terminado, lotes, destinos y confirmación de recepción.', 'href' => '/dispatches', 'status' => 'functional'],
        ['module' => 'payment-sheets', 'permission' => 'finances', 'title' => 'Planillas de productores', 'description' => 'Cierres semanales, importes congelados y pagos trazables.', 'href' => '/payment-sheets', 'status' => 'functional'],
    ];

    public function __invoke(): Response
    {
        /** @var User $user */
        $user = request()->user();
        $reports = collect($this->reports)
            ->filter(fn (array $report): bool => $this->canAccess($user, $report['permission']))
            ->map(fn (array $report): array => [
                ...$report,
                'pdf_url' => route('module-reports.show', ['module' => $report['module']]),
                'excel_url' => route('module-reports.excel', ['module' => $report['module']]),
            ])
            ->values();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'summary' => [
                'available' => $reports->count(),
                'functional' => $reports->where('status', 'functional')->count(),
                'prototype' => $reports->where('status', 'prototype')->count(),
            ],
        ]);
    }

    private function canAccess(User $user, string $module): bool
    {
        return $module === '' || $user->is_admin || $user->hasPermission("access_{$module}");
    }
}
