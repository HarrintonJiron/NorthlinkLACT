<?php

namespace App\Console\Commands;

use App\Models\RouteOperatorAssignment;
use App\Models\User;
use App\Modules\Producers\Models\Route;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('acopio:share-route {route=QBR-05 : Código de la ruta compartida} {--date=* : Fechas YYYY-MM-DD; por defecto hoy y mañana}')]
#[Description('Agrega una ruta de prueba compartida a todos los ruteros sin reemplazar sus rutas principales')]
class SharePilotRoute extends Command
{
    public function handle(): int
    {
        $route = Route::query()
            ->where('code', $this->argument('route'))
            ->where('active', true)
            ->first();

        if (! $route) {
            $this->error('No se encontró una ruta activa con ese código.');

            return self::FAILURE;
        }

        $dates = collect($this->option('date'))
            ->whenEmpty(fn ($dates) => $dates->push(today()->toDateString(), today()->addDay()->toDateString()))
            ->map(fn (string $date): string => Carbon::createFromFormat('Y-m-d', $date)->toDateString())
            ->unique()
            ->values();
        $users = User::query()
            ->where('active', true)
            ->where('username', 'like', 'rutero%')
            ->orderBy('username')
            ->get();

        if ($users->count() !== 17) {
            $this->error("Se esperaban 17 ruteros activos y se encontraron {$users->count()}.");

            return self::FAILURE;
        }

        $assignedBy = User::query()->where('is_admin', true)->value('id') ?? $users->first()->id;

        DB::transaction(function () use ($route, $dates, $users, $assignedBy): void {
            foreach ($dates as $date) {
                foreach ($users as $user) {
                    $primary = RouteOperatorAssignment::query()
                        ->with('route.rutero:id,route_id,vehicle_plate')
                        ->where('user_id', $user->id)
                        ->whereDate('assigned_date', $date)
                        ->where('is_shared_test', false)
                        ->first();

                    $assignment = RouteOperatorAssignment::query()
                        ->where('route_id', $route->id)
                        ->where('user_id', $user->id)
                        ->whereDate('assigned_date', $date)
                        ->first() ?? new RouteOperatorAssignment;

                    $assignment->fill([
                        'route_id' => $route->id,
                        'user_id' => $user->id,
                        'assigned_date' => $date,
                        'assigned_by' => $assignedBy,
                        'vehicle_plate' => $primary?->vehicle_plate ?? $primary?->route?->rutero?->vehicle_plate,
                        'is_shared_test' => $primary?->route_id !== $route->id,
                    ])->save();
                }
            }
        });

        $this->info("Ruta {$route->name} agregada a 17 ruteros para {$dates->implode(', ')}.");

        return self::SUCCESS;
    }
}
