<?php

namespace Tests\Feature;

use App\Models\RouteOperatorAssignment;
use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\Route;
use Database\Seeders\SunmiRuteroDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SunmiRuteroDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_seventeen_non_conflicting_rotating_operator_accounts(): void
    {
        $company = Company::factory()->create();
        $plant = Plant::factory()->create(['company_id' => $company->id]);

        foreach (range(1, 17) as $number) {
            Route::factory()->create([
                'company_id' => $company->id,
                'plant_id' => $plant->id,
                'code' => 'RUT-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'active' => true,
            ]);
        }

        $this->seed(SunmiRuteroDemoSeeder::class);

        $users = User::query()
            ->where('username', 'like', 'rutero%')
            ->orderBy('username')
            ->get();

        $this->assertCount(17, $users);
        $this->assertTrue(Hash::check('RuteroNorthlink2026!', $users->first()->password));
        $this->assertTrue($users->every(fn (User $user): bool => $user->active
            && $user->hasPermission('access_sumni')));
        $this->assertSame(17, RouteOperatorAssignment::query()
            ->whereDate('assigned_date', today())
            ->distinct('route_id')
            ->count('route_id'));
        $this->assertSame(17, RouteOperatorAssignment::query()
            ->whereDate('assigned_date', today()->addDay())
            ->distinct('route_id')
            ->count('route_id'));
    }
}
