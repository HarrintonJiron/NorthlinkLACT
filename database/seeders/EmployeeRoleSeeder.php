<?php

namespace Database\Seeders;

use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Database\Seeder;

class EmployeeRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmployeeRole::query()->updateOrCreate(
            ['name' => 'Administrativo'],
            [
                'description' => 'Gestión administrativa y soporte interno.',
                'active' => true,
            ],
        );

        EmployeeRole::query()->updateOrCreate(
            ['name' => 'Ruta'],
            [
                'description' => 'Operaciones de acopio y trabajo en ruta.',
                'active' => true,
            ],
        );
    }
}
