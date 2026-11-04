<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\EmployeeRoleSeeder;
use Database\Seeders\ModulePermissionSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('northlink:clean-start
            {--confirm= : Escriba CLEAN_NORTHLINK para autorizar el reinicio}
            {--backup-confirmed : Confirma que existe un respaldo verificado}')]
#[Description('Recrea la base sin datos demo y crea el administrador inicial')]
class CleanStartCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('confirm') !== 'CLEAN_NORTHLINK' || ! $this->option('backup-confirmed')) {
            $this->error('Operación cancelada. Se requiere respaldo verificado y --confirm=CLEAN_NORTHLINK.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Nombre del administrador', 'Administrador Northlink'));
        $email = mb_strtolower(trim((string) $this->ask('Correo del administrador', 'admin@northlinkni.com')));
        $password = (string) $this->secret('Nueva contraseña del administrador');
        $passwordConfirmation = (string) $this->secret('Confirme la contraseña');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->confirm('Se eliminarán definitivamente todos los datos actuales de Northlink LACT. ¿Continuar?')) {
            $this->warn('Operación cancelada sin modificar la base de datos.');

            return self::FAILURE;
        }

        $this->warn('Recreando la base de datos...');
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        if ($exitCode !== self::SUCCESS) {
            $this->error('Las migraciones no finalizaron correctamente. Restaure el respaldo.');

            return self::FAILURE;
        }

        $this->callSilent('db:seed', ['--class' => EmployeeRoleSeeder::class, '--force' => true]);
        $this->callSilent('db:seed', ['--class' => ModulePermissionSeeder::class, '--force' => true]);

        User::query()->create([
            'username' => 'admin',
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'pin' => '1234',
            'active' => true,
            'is_admin' => true,
        ]);

        $this->newLine();
        $this->info('Base limpia creada correctamente.');
        $this->line('Usuario: admin');
        $this->line('No se cargaron productores, rutas, acopios, empleados ni movimientos demo.');

        return self::SUCCESS;
    }
}
