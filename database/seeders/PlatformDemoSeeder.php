<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Finanzas\Models\FinanceCategory;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Finanzas\Services\FinanzasService;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\PersonnelService;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Services\MilkCollectionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ModulePermissionSeeder::class);
        $this->call(AcopioDemoSeeder::class);

        DB::transaction(function (): void {
            $admin = $this->adminUser();

            $this->extraEmployees();
            $this->extraAcopio($admin);
            $this->inventory();
            $this->finances();
        });

        $this->command?->info('Demo ampliado: usuarios, rutas, productores, acopios, inventario, finanzas y personal listos.');
        $this->command?->info('Las credenciales administrativas existentes se conservaron.');
    }

    private function adminUser(): User
    {
        $user = User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Northlink',
                'email' => 'admin@northlink.local',
                'password' => 'Northlink2026!',
                'pin' => '1234',
                'phone' => '8888-0000',
                'active' => true,
            ],
        );

        if (! $user->is_admin) {
            $user->forceFill(['is_admin' => true])->save();
        }

        return $user;
    }

    private function extraEmployees(): void
    {
        $this->call(EmployeeRoleSeeder::class);

        $roles = EmployeeRole::query()->pluck('id', 'name');
        $service = app(PersonnelService::class);
        $people = [
            ['EMP-0010', 'Camila', 'Reyes', 'Calidad', 'Administrativo', 16800, true],
            ['EMP-0011', 'Nelson', 'Duarte', 'Producción', 'Administrativo', 15200, true],
            ['EMP-0012', 'Fátima', 'Cruz', 'Caja', 'Administrativo', 13900, true],
            ['EMP-0013', 'Edwin', 'Blandón', 'Acopio', 'Ruta', 12800, true],
            ['EMP-0014', 'Ruth', 'Morales', 'Inventario', 'Administrativo', 14500, true],
            ['EMP-0015', 'Kevin', 'Sequeira', 'Mantenimiento', 'Ruta', 11800, false],
            ['EMP-0016', 'Diana', 'Orozco', 'Ventas', 'Administrativo', 17100, true],
            ['EMP-0017', 'Álvaro', 'Medina', 'Logística', 'Ruta', 13200, true],
            ['EMP-0018', 'Noelia', 'Pineda', 'Administración', 'Administrativo', 15900, true],
            ['EMP-0019', 'Iván', 'Gaitán', 'Acopio', 'Ruta', 12600, true],
            ['EMP-0020', 'Roxana', 'Téllez', 'Recursos Humanos', 'Administrativo', 14950, true],
            ['EMP-0021', 'Santos', 'Rivas', 'Bodega', 'Ruta', 11200, false],
        ];

        foreach ($people as $index => [$code, $firstName, $lastName, $area, $role, $salary, $insured]) {
            $employee = Employee::query()->updateOrCreate(
                ['code' => $code],
                [
                    'identity_number' => '001-'.str_pad((string) (70000 + $index), 5, '0', STR_PAD_LEFT).'-'.str_pad((string) (1000 + $index), 4, '0', STR_PAD_LEFT).'X',
                    'employee_role_id' => $roles[$role],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'area' => $area,
                    'email' => strtolower($firstName).'.'.strtolower($lastName).'@northlink.test',
                    'phone' => '8888-'.str_pad((string) (3000 + $index), 4, '0', STR_PAD_LEFT),
                    'hired_at' => now()->subMonths(18 - ($index % 9))->toDateString(),
                    'status' => PersonnelService::STATUS_ACTIVO,
                    'contract_type' => $index % 4 === 0 ? PersonnelService::CONTRACT_TEMPORAL : PersonnelService::CONTRACT_INDEFINIDO,
                    'contract_start_date' => now()->subMonths(18 - ($index % 9))->toDateString(),
                    'salary' => $salary,
                    'inss_insured' => $insured,
                    'inss_number' => $insured ? 'INSS-DEMO-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT) : null,
                    'payment_method' => $index % 3 === 0 ? PersonnelService::PAYMENT_EFECTIVO : PersonnelService::PAYMENT_TRANSFERENCIA,
                    'bank_account' => $index % 3 === 0 ? null : '100200300400'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'emergency_contact_name' => 'Contacto '.$firstName,
                    'emergency_contact_phone' => '8777-'.str_pad((string) (4000 + $index), 4, '0', STR_PAD_LEFT),
                ],
            );

            $attendanceDate = now()->subDays($index % 5)->toDateString();
            $attendance = EmployeeAttendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $attendanceDate)
                ->first() ?? new EmployeeAttendance([
                    'employee_id' => $employee->id,
                    'attendance_date' => $attendanceDate,
                ]);

            $attendance->fill([
                'type' => $index % 6 === 0 ? EmployeeAttendance::TYPE_PERMISO : EmployeeAttendance::TYPE_PRESENTE,
                'check_in' => $index % 6 === 0 ? null : '07:0'.($index % 6),
                'check_out' => $index % 6 === 0 ? null : '16:1'.($index % 6),
                'notes' => $index % 6 === 0 ? 'Permiso demo autorizado' : null,
            ])->save();

            if ($index % 4 === 0) {
                EmployeeDeduction::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'type' => PersonnelService::DEDUCTION_ADELANTO_SALARIO,
                        'deduction_date' => now()->startOfMonth()->toDateString(),
                    ],
                    [
                        'amount' => 750 + ($index * 50),
                        'status' => PersonnelService::DEDUCTION_STATUS_ACTIVA,
                        'reason' => 'Adelanto demo para pruebas',
                    ],
                );
            }

            $service->inssDeductionForEmployee($employee);
        }
    }

    private function extraAcopio(User $user): void
    {
        $routes = Route::query()->where('active', true)->orderBy('code')->get();
        $names = [
            'Ernesto Suárez', 'Julia Pérez', 'Ramón Corea', 'Marta Tinoco', 'Felipe Rocha',
            'Isabel Gómez', 'Francisco Talavera', 'Norma Aguilar', 'Eddy Valle', 'Teresa Mena',
            'Hugo Lanzas', 'Cristina López', 'Walter Meza', 'Silvia Obando', 'Danilo Centeno',
            'Patricia Rugama', 'Benito Zamora', 'Adriana Solís', 'Jairo Molina', 'Esperanza Cano',
            'Omar Blandino', 'Vilma Salgado', 'René Chavarría', 'Claudia Mayorga',
        ];

        $service = app(MilkCollectionService::class);
        $friday = now()->startOfDay()->isFriday()
            ? now()->startOfDay()->copy()
            : now()->startOfDay()->copy()->previous(Carbon::FRIDAY);

        foreach ($names as $index => $name) {
            $route = $routes[$index % max(1, $routes->count())];
            $code = 'PRO-'.str_pad((string) (100 + $index), 4, '0', STR_PAD_LEFT);
            $producer = Producer::withTrashed()->updateOrCreate(
                ['code' => $code],
                [
                    'full_name' => $name,
                    'identity_number' => '441-'.str_pad((string) (81000 + $index), 5, '0', STR_PAD_LEFT).'-'.str_pad((string) (2000 + $index), 4, '0', STR_PAD_LEFT).'D',
                    'phone' => '8755-'.str_pad((string) (1000 + $index), 4, '0', STR_PAD_LEFT),
                    'address' => 'Comarca demo '.$route->name,
                    'community' => ['Yasica Sur', 'San Ramón', 'El Tuma', 'La Concordia'][$index % 4],
                    'municipality' => ['Matagalpa', 'San Ramón', 'Jinotega', 'La Dalia'][$index % 4],
                    'department' => ['Matagalpa', 'Jinotega'][$index % 2],
                    'active' => $index % 13 !== 0,
                    'deleted_at' => null,
                ],
            );

            ProducerRouteAssignment::query()->updateOrCreate(
                ['producer_id' => $producer->id, 'ended_at' => null],
                [
                    'route_id' => $route->id,
                    'assigned_at' => now()->subWeeks(3)->toDateString(),
                    'assigned_by' => $user->id,
                ],
            );

            if (! $producer->active) {
                continue;
            }

            for ($day = 0; $day < 7; $day++) {
                if (($index + $day) % 5 === 0) {
                    continue;
                }

                $liters = 12 + (($index * 3 + $day * 2) % 38) + ($day / 10);
                $temperature = 23 + (($index + $day) % 5);
                $service->record($route, $producer->id, $friday->copy()->addDays($day)->toDateString(), $liters, $user, temperature: $temperature);
            }
        }
    }

    private function inventory(): void
    {
        $service = app(InventoryService::class);
        $service->ensureDefaultUnits();
        $units = InventoryUnit::query()->pluck('id', 'code');
        $items = [
            ['Cuajo líquido premium', 'ML', 3250, 700],
            ['Cultivo láctico yogurt', 'G', 820, 250],
            ['Bolsas para leche 1L', 'UND', 12500, 3000],
            ['Tapas plásticas blancas', 'UND', 8700, 2000],
            ['Detergente alcalino', 'GAL', 38, 12],
            ['Sanitizante grado alimenticio', 'GAL', 24, 10],
            ['Botellas PET 500 ml', 'UND', 6400, 1800],
            ['Cajas plásticas de reparto', 'CJ', 94, 30],
            ['Etiquetas Lácteos Quebar', 'UND', 18000, 5000],
            ['Sal industrial', 'KG', 420, 120],
            ['Azúcar refinada', 'QQ', 18, 5],
            ['Saborizante vainilla', 'ML', 2100, 500],
            ['Saborizante fresa', 'ML', 1900, 500],
            ['Repuesto manguera sanitaria', 'UND', 14, 6],
            ['Guantes nitrilo', 'CJ', 22, 8],
            ['Mascarillas desechables', 'CJ', 16, 6],
            ['Alcohol gel', 'GAL', 9, 10],
            ['Termómetro digital', 'UND', 5, 3],
        ];

        foreach ($items as $index => [$name, $unitCode, $stock, $minStock]) {
            InventoryProduct::query()->updateOrCreate(
                ['code' => 'PRD-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'description' => 'Producto demo para pruebas de inventario.',
                    'unit_id' => $units[$unitCode],
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'expiration_date' => $index % 4 === 0 ? now()->addMonths(2 + $index)->toDateString() : null,
                    'active' => $index % 11 !== 0,
                ],
            );
        }

        $supplies = ['Filtro sanitario', 'Empaque al vacío', 'Cepillo industrial', 'Canasta reparto', 'Balde acero', 'Medidor lactómetro', 'Cinta pH', 'Bolsa basura industrial', 'Lámpara bodega', 'Escoba sanitaria', 'Papel térmico Z92', 'Sello seguridad'];
        foreach ($supplies as $index => $name) {
            InventoryProduct::query()->updateOrCreate(
                ['code' => 'PRD-DEMO-X'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'description' => 'Insumo adicional demo para pruebas de búsqueda, stock mínimo y estado.',
                    'unit_id' => $units[['UND', 'CJ', 'KG', 'ML'][$index % 4]],
                    'stock' => 8 + ($index * 17),
                    'min_stock' => 10 + ($index % 5),
                    'expiration_date' => $index % 5 === 0 ? now()->addDays(30 + $index)->toDateString() : null,
                    'active' => $index % 7 !== 0,
                ],
            );
        }
    }

    private function finances(): void
    {
        $service = app(FinanzasService::class);
        $service->ensureDefaultCategories();
        $categories = FinanceCategory::query()->pluck('id', 'code');
        $movements = [
            [FinanceTransaction::TYPE_INGRESO, 'VENT', 'Venta leche pasteurizada', 48500],
            [FinanceTransaction::TYPE_INGRESO, 'VENT', 'Venta queso fresco', 31200],
            [FinanceTransaction::TYPE_PAGO, 'PROD', 'Pago semanal productores', 27600],
            [FinanceTransaction::TYPE_GASTO, 'SERV', 'Energía eléctrica planta', 8900],
            [FinanceTransaction::TYPE_GASTO, 'MANT', 'Mantenimiento camión ruta norte', 7200],
            [FinanceTransaction::TYPE_GASTO, 'INS', 'Compra insumos producción', 11600],
            [FinanceTransaction::TYPE_PAGO, 'NOM', 'Nómina quincenal', 42300],
            [FinanceTransaction::TYPE_GASTO, 'OPER', 'Combustible rutas', 15750],
            [FinanceTransaction::TYPE_INGRESO, 'VENT', 'Venta yogurt natural', 22400],
            [FinanceTransaction::TYPE_GASTO, 'OTRO', 'Papelería administrativa', 1350],
        ];

        foreach ($movements as $index => [$type, $categoryCode, $concept, $amount]) {
            FinanceTransaction::query()->updateOrCreate(
                ['code' => 'FIN-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'type' => $type,
                    'category_id' => $categories[$categoryCode],
                    'concept' => $concept,
                    'description' => 'Movimiento financiero demo para validar reportes y filtros.',
                    'amount' => $amount + ($index * 137.25),
                    'transaction_date' => now()->subDays($index * 3)->toDateString(),
                    'payment_method' => ['efectivo', 'transferencia', 'cheque'][$index % 3],
                    'reference' => 'REF-DEMO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'payee' => $type === FinanceTransaction::TYPE_INGRESO ? 'Cliente mayorista demo' : 'Proveedor demo',
                    'notes' => 'Dato cargado automáticamente para pruebas.',
                    'active' => $index % 9 !== 0,
                ],
            );
        }

        $concepts = [
            [FinanceTransaction::TYPE_INGRESO, 'VENT', 'Venta canal pulperías'],
            [FinanceTransaction::TYPE_INGRESO, 'VENT', 'Venta cliente institucional'],
            [FinanceTransaction::TYPE_PAGO, 'PROD', 'Liquidación productor'],
            [FinanceTransaction::TYPE_GASTO, 'OPER', 'Combustible acopio'],
            [FinanceTransaction::TYPE_GASTO, 'INS', 'Compra material empaque'],
            [FinanceTransaction::TYPE_GASTO, 'SERV', 'Servicio de agua'],
            [FinanceTransaction::TYPE_GASTO, 'MANT', 'Reparación equipo frío'],
            [FinanceTransaction::TYPE_PAGO, 'NOM', 'Pago personal eventual'],
        ];

        for ($index = 0; $index < 36; $index++) {
            [$type, $categoryCode, $concept] = $concepts[$index % count($concepts)];

            FinanceTransaction::query()->updateOrCreate(
                ['code' => 'FIN-DEMO-X'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'type' => $type,
                    'category_id' => $categories[$categoryCode],
                    'concept' => $concept.' #'.($index + 1),
                    'description' => 'Movimiento financiero recurrente demo.',
                    'amount' => 950 + (($index * 421) % 18500),
                    'transaction_date' => now()->subDays($index * 2)->toDateString(),
                    'payment_method' => ['efectivo', 'transferencia', 'cheque'][$index % 3],
                    'reference' => 'REF-MASIVO-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'payee' => $type === FinanceTransaction::TYPE_INGRESO ? 'Cliente demo '.($index + 1) : 'Proveedor demo '.($index + 1),
                    'notes' => 'Dato masivo para probar filtros, totales y gráficas.',
                    'active' => $index % 10 !== 0,
                ],
            );
        }
    }
}
