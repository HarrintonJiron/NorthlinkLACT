<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\Permission;
use Illuminate\Database\Seeder;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'sumni' => 'Acopio Sunmi',
            'routes' => 'Rutas',
            'ruteros' => 'Ruteros',
            'collections' => 'Recolecciones',
            'producers' => 'Productores',
            'finances' => 'Finanzas',
            'production' => 'Producción',
            'inventory' => 'Inventario',
            'personnel' => 'Personal',
            'payroll' => 'Nómina',
            'reports' => 'Reportes',
        ];

        foreach ($modules as $module => $displayName) {
            Permission::query()->updateOrCreate(
                ['name' => "access_{$module}"],
                [
                    'display_name' => $displayName,
                    'description' => "Permite acceder y gestionar el módulo {$displayName}.",
                    'module' => $module,
                ],
            );
        }
    }
}
