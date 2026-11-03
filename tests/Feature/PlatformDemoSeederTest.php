<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use Database\Seeders\PlatformDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_data_is_repeatable_and_preserves_existing_admin_credentials(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'name' => 'Administrador de Pruebas',
            'email' => 'admin@northlink.test',
            'password' => 'ClaveExistente2026!',
            'active' => true,
            'is_admin' => true,
        ]);

        $this->seed(PlatformDemoSeeder::class);
        $this->seed(PlatformDemoSeeder::class);

        $admin = User::query()->where('username', 'admin')->sole();

        $this->assertSame('Administrador de Pruebas', $admin->name);
        $this->assertSame('admin@northlink.test', $admin->email);
        $this->assertTrue(Hash::check('ClaveExistente2026!', $admin->password));
        $this->assertGreaterThanOrEqual(30, Producer::query()->count());
        $this->assertGreaterThanOrEqual(100, MilkCollection::query()->count());
        $this->assertGreaterThanOrEqual(20, InventoryProduct::query()->count());
        $this->assertGreaterThanOrEqual(40, FinanceTransaction::query()->count());
        $this->assertGreaterThanOrEqual(10, Employee::query()->count());
    }
}
