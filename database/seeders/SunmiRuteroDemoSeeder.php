<?php

namespace Database\Seeders;

use App\Models\RouteOperatorAssignment;
use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Producers\Models\Route;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SunmiRuteroDemoSeeder extends Seeder
{
    private const PASSWORD = 'RuteroNorthlink2026!';

    public function run(): void
    {
        $this->call(EmployeeRoleSeeder::class);
        $this->call(ModulePermissionSeeder::class);

        $routes = Route::query()
            ->where('active', true)
            ->orderBy('code')
            ->limit(17)
            ->get();

        if ($routes->count() < 17) {
            $this->command?->warn('Se necesitan 17 rutas activas para crear los 17 ruteros SUNMI sin conflictos.');

            return;
        }

        $routeRole = EmployeeRole::query()->where('name', 'Ruta')->firstOrFail();
        $sumniPermission = Permission::query()->where('name', 'access_sumni')->firstOrFail();
        $dates = [today()->toDateString(), today()->addDay()->toDateString()];

        DB::transaction(function () use ($routes, $routeRole, $sumniPermission, $dates): void {
            foreach ($routes as $index => $route) {
                $number = $index + 1;
                $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
                $employee = Employee::withTrashed()->updateOrCreate(
                    ['code' => 'SUNMI-RUT-'.$suffix],
                    [
                        'employee_role_id' => $routeRole->id,
                        'plant_id' => $route->plant_id,
                        'first_name' => 'Rutero',
                        'last_name' => 'SUNMI '.$suffix,
                        'area' => 'Acopio',
                        'identity_number' => '441-'.str_pad((string) (91000 + $number), 5, '0', STR_PAD_LEFT).'-'.str_pad((string) (3000 + $number), 4, '0', STR_PAD_LEFT).'R',
                        'email' => 'rutero'.$suffix.'@northlinkni.com',
                        'phone' => '8600-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                        'hired_at' => today()->subYear()->toDateString(),
                        'status' => 'activo',
                        'contract_type' => 'indefinido',
                        'contract_start_date' => today()->subYear()->toDateString(),
                        'salary' => 12000,
                        'inss_insured' => false,
                        'payment_method' => 'efectivo',
                        'deleted_at' => null,
                    ],
                );

                $user = User::query()->updateOrCreate(
                    ['username' => 'rutero'.$suffix],
                    [
                        'employee_id' => $employee->id,
                        'name' => $employee->full_name,
                        'email' => $employee->email,
                        'phone' => $employee->phone,
                        'password' => self::PASSWORD,
                        'pin' => str_pad((string) (1000 + $number), 4, '0', STR_PAD_LEFT),
                        'active' => true,
                        'is_admin' => false,
                    ],
                );

                $user->permissions()->sync([$sumniPermission->id]);
                $user->companies()->syncWithoutDetaching([
                    $route->company_id => ['plant_id' => $route->plant_id],
                ]);

                foreach ($dates as $date) {
                    RouteOperatorAssignment::query()->updateOrCreate(
                        [
                            'route_id' => $route->id,
                            'user_id' => $user->id,
                            'assigned_date' => $date,
                        ],
                        [
                            'assigned_by' => User::query()->where('is_admin', true)->value('id') ?? $user->id,
                            'vehicle_plate' => $route->rutero?->vehicle_plate,
                            'is_shared_test' => false,
                        ],
                    );
                }
            }
        });

        $this->command?->info('17 ruteros SUNMI creados: rutero01 a rutero17.');
    }
}
