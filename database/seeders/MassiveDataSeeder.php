<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
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
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Services\MilkCollectionService;
use App\Modules\Ruteros\Models\Rutero;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MassiveDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Iniciando generación masiva de datos de prueba...');

        DB::transaction(function (): void {
            $this->seedCompaniesAndPlants();
            $this->seedUsers();
            $this->seedPersonnel();
            $this->seedProducersAndRoutes();
            $this->seedMilkCollections();
            $this->seedInventory();
            $this->seedFinances();
            $this->seedRuteros();
        });

        $this->command?->info('Datos masivos generados exitosamente.');
        $this->command?->info('Resumen:');
        $this->command?->info('- Usuarios: '.User::count());
        $this->command?->info('- Empleados: '.Employee::count());
        $this->command?->info('- Productores: '.Producer::count());
        $this->command?->info('- Rutas: '.Route::count());
        $this->command?->info('- Acopios: '.MilkCollection::count());
        $this->command?->info('- Productos inventario: '.InventoryProduct::count());
        $this->command?->info('- Transacciones financieras: '.FinanceTransaction::count());
        $this->command?->info('- Ruteros: '.Rutero::count());
    }

    private function seedCompaniesAndPlants(): void
    {
        $this->command?->info('Generando empresas y plantas...');

        $company = Company::query()->first() ?? Company::factory()->create([
            'name' => 'Northlink LACT',
            'legal_name' => 'Northlink LACT S.A.',
            'tax_id' => 'J0310000000001',
            'active' => true,
        ]);

        Plant::query()->first() ?? Plant::factory()->create([
            'company_id' => $company->id,
            'name' => 'Planta Matagalpa',
            'code' => 'PLT-001',
            'active' => true,
        ]);

        // Plantas adicionales
        for ($i = 0; $i < 3; $i++) {
            try {
                Plant::factory()->create([
                    'company_id' => $company->id,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        MilkPrice::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'plant_id' => Plant::query()->first()->id,
                'effective_from' => '2026-01-01',
            ],
            [
                'price_per_liter' => 18.50,
                'effective_until' => null,
                'active' => true,
            ]
        );
    }

    private function seedUsers(): void
    {
        $this->command?->info('Generando usuarios...');

        // Admin principal
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Northlink',
                'email' => 'admin@northlink.local',
                'password' => Hash::make('Northlink2026!'),
                'pin' => Hash::make('1234'),
                'phone' => '8888-0000',
                'active' => true,
                'is_admin' => true,
            ]
        );

        // Usuarios masivos
        for ($i = 0; $i < 50; $i++) {
            User::factory()->create();
        }
    }

    private function seedPersonnel(): void
    {
        $this->command?->info('Generando personal...');

        $this->call(EmployeeRoleSeeder::class);

        $roles = EmployeeRole::query()->pluck('id', 'name');
        $service = app(PersonnelService::class);

        // Empleados masivos
        for ($i = 0; $i < 50; $i++) {
            Employee::factory()->create([
                'employee_role_id' => fake()->randomElement($roles->values()->toArray()),
            ]);
        }

        // Asistencias masivas
        $employees = Employee::query()->take(30)->get();
        foreach ($employees as $employee) {
            for ($day = 0; $day < 30; $day++) {
                $date = now()->subDays($day)->toDateString();
                if (fake()->boolean(80)) {
                    try {
                        EmployeeAttendance::query()->updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'attendance_date' => $date,
                            ],
                            [
                                'type' => EmployeeAttendance::TYPE_PRESENTE,
                                'check_in' => fake()->time('H:i', '07:00'),
                                'check_out' => fake()->time('H:i', '17:00'),
                            ]
                        );
                    } catch (UniqueConstraintViolationException $e) {
                        // Ignorar si ya existe
                    }
                } elseif (fake()->boolean(50)) {
                    try {
                        EmployeeAttendance::query()->updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'attendance_date' => $date,
                            ],
                            [
                                'type' => EmployeeAttendance::TYPE_PERMISO,
                                'notes' => fake()->sentence(),
                            ]
                        );
                    } catch (UniqueConstraintViolationException $e) {
                        // Ignorar si ya existe
                    }
                }
            }
        }

        // Deducciones masivas
        foreach (Employee::query()->take(20)->get() as $employee) {
            if (fake()->boolean(40)) {
                EmployeeDeduction::query()->create([
                    'employee_id' => $employee->id,
                    'type' => fake()->randomElement([
                        PersonnelService::DEDUCTION_ADELANTO_SALARIO,
                        PersonnelService::DEDUCTION_PRESTAMO,
                        PersonnelService::DEDUCTION_AUSENCIA,
                    ]),
                    'deduction_date' => Carbon::parse(fake()->dateTimeBetween('-3 months', 'now'))->toDateString(),
                    'amount' => fake()->randomFloat(2, 500, 5000),
                    'total_amount' => fake()->randomFloat(2, 2000, 20000),
                    'installment_amount' => fake()->randomFloat(2, 500, 2000),
                    'installments_total' => fake()->numberBetween(2, 12),
                    'installments_paid' => fake()->numberBetween(0, 6),
                    'status' => PersonnelService::DEDUCTION_STATUS_ACTIVA,
                    'reason' => fake()->sentence(),
                ]);
            }
        }
    }

    private function seedProducersAndRoutes(): void
    {
        $this->command?->info('Generando productores y rutas...');

        $company = Company::query()->first();
        $plant = Plant::query()->first();
        $user = User::query()->first();

        // Rutas masivas
        for ($i = 0; $i < 20; $i++) {
            try {
                Route::factory()->create([
                    'company_id' => $company->id,
                    'plant_id' => $plant->id,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        $routes = Route::query()->get();

        // Productores masivos
        for ($i = 0; $i < 100; $i++) {
            try {
                Producer::factory()->create();
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        // Asignar productores a rutas
        $producers = Producer::query()->get();
        foreach ($producers as $index => $producer) {
            ProducerRouteAssignment::query()->updateOrCreate(
                ['producer_id' => $producer->id, 'ended_at' => null],
                [
                    'route_id' => $routes[$index % $routes->count()]->id,
                    'assigned_at' => Carbon::parse(fake()->dateTimeBetween('-6 months', 'now'))->toDateString(),
                    'assigned_by' => $user->id,
                ]
            );
        }
    }

    private function seedMilkCollections(): void
    {
        $this->command?->info('Generando acopios históricos...');

        $routes = Route::query()->get();
        $producers = Producer::query()->where('active', true)->get();
        $user = User::query()->first();
        $service = app(MilkCollectionService::class);

        // Generar acopios históricos para los últimos 6 meses
        $startDate = now()->subMonths(6);
        $endDate = now();

        foreach ($producers as $producer) {
            $assignment = $producer->activeAssignment;
            if (! $assignment) {
                continue;
            }

            $route = $assignment->route;

            // Generar acopios cada 2-3 días en promedio
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                if (fake()->boolean(65)) {
                    $liters = fake()->randomFloat(1, 10, 80);
                    $temperature = fake()->randomFloat(1, 15, 35);
                    $service->record($route, $producer->id, $currentDate->toDateString(), $liters, $user, temperature: $temperature);
                }
                $currentDate->addDays(fake()->numberBetween(1, 4));
            }
        }

        // Acopios adicionales usando factory
        for ($i = 0; $i < 200; $i++) {
            MilkCollection::factory()->create();
        }
    }

    private function seedInventory(): void
    {
        $this->command?->info('Generando inventario...');

        $service = app(InventoryService::class);
        $service->ensureDefaultUnits();

        // Unidades adicionales
        for ($i = 0; $i < 5; $i++) {
            try {
                InventoryUnit::factory()->create();
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        $units = InventoryUnit::query()->pluck('id', 'code');

        // Productos masivos
        for ($i = 0; $i < 100; $i++) {
            try {
                InventoryProduct::factory()->create([
                    'unit_id' => fake()->randomElement($units->values()->toArray()),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        // Algunos productos con stock bajo
        for ($i = 0; $i < 15; $i++) {
            try {
                InventoryProduct::factory()->lowStock()->create([
                    'unit_id' => fake()->randomElement($units->values()->toArray()),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        // Algunos productos inactivos
        for ($i = 0; $i < 10; $i++) {
            try {
                InventoryProduct::factory()->inactive()->create([
                    'unit_id' => fake()->randomElement($units->values()->toArray()),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }
    }

    private function seedFinances(): void
    {
        $this->command?->info('Generando finanzas...');

        $service = app(FinanzasService::class);
        $service->ensureDefaultCategories();

        // Categorías adicionales
        for ($i = 0; $i < 10; $i++) {
            try {
                FinanceCategory::factory()->create();
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        $categories = FinanceCategory::query()->pluck('id', 'code');

        // Transacciones masivas
        for ($i = 0; $i < 300; $i++) {
            FinanceTransaction::factory()->create([
                'category_id' => fake()->randomElement($categories->values()->toArray()),
            ]);
        }

        // Transacciones de ingresos específicas
        for ($i = 0; $i < 100; $i++) {
            FinanceTransaction::factory()->income()->create([
                'category_id' => $categories->firstWhere('type', 'ingreso') ?? $categories->first(),
            ]);
        }

        // Transacciones de gastos específicas
        for ($i = 0; $i < 100; $i++) {
            FinanceTransaction::factory()->expense()->create([
                'category_id' => $categories->firstWhere('type', 'gasto') ?? $categories->first(),
            ]);
        }

        // Transacciones de pagos específicas
        for ($i = 0; $i < 50; $i++) {
            FinanceTransaction::factory()->payment()->create([
                'category_id' => $categories->firstWhere('type', 'pago') ?? $categories->first(),
            ]);
        }

        // Algunas transacciones inactivas
        for ($i = 0; $i < 20; $i++) {
            FinanceTransaction::factory()->inactive()->create([
                'category_id' => fake()->randomElement($categories->values()->toArray()),
            ]);
        }
    }

    private function seedRuteros(): void
    {
        $this->command?->info('Generando ruteros...');

        $routes = Route::query()->get();

        // Ruteros asignados a rutas
        for ($i = 0; $i < 25; $i++) {
            try {
                Rutero::factory()->create([
                    'route_id' => fake()->randomElement($routes->pluck('id')->values()->toArray()),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        // Ruteros sin asignar
        for ($i = 0; $i < 5; $i++) {
            try {
                Rutero::factory()->create([
                    'route_id' => null,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }

        // Algunos ruteros inactivos
        for ($i = 0; $i < 5; $i++) {
            try {
                Rutero::factory()->inactive()->create([
                    'route_id' => fake()->randomElement($routes->pluck('id')->values()->toArray()),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Ignorar si ya existe
            }
        }
    }
}
