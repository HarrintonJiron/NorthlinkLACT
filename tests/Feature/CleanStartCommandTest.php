<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\Producer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanStartCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_reset_without_explicit_safety_flags(): void
    {
        Producer::factory()->create();

        $this->artisan('northlink:clean-start')
            ->assertFailed();

        $this->assertDatabaseCount('producers', 1);
    }

    public function test_it_validates_the_new_admin_before_deleting_any_data(): void
    {
        Producer::factory()->create();

        $this->artisan('northlink:clean-start', [
            '--confirm' => 'CLEAN_NORTHLINK',
            '--backup-confirmed' => true,
        ])
            ->expectsQuestion('Nombre del administrador', 'Administrador Quebar')
            ->expectsQuestion('Correo del administrador', 'correo-invalido')
            ->expectsQuestion('Nueva contraseña del administrador', 'corta')
            ->expectsQuestion('Confirme la contraseña', 'diferente')
            ->assertFailed();

        $this->assertDatabaseCount('producers', 1);
    }
}
