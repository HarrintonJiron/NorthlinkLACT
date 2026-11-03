<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(EmployeeRoleSeeder::class);
        $this->call(ModulePermissionSeeder::class);
        $this->call(PersonnelDemoSeeder::class);
        $this->call(AcopioDemoSeeder::class);
        $this->call(PlatformDemoSeeder::class);

        if (! app()->isLocal()) {
            return;
        }

        User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador Local',
                'email' => 'admin@northlink.test',
                'password' => 'password',
                'active' => true,
                'is_admin' => true,
            ],
        );

        // Ejecutar MassiveDataSeeder solo si se especifica la variable de entorno
        if (env('SEED_MASSIVE_DATA', false)) {
            $this->call(MassiveDataSeeder::class);
        }
    }
}
