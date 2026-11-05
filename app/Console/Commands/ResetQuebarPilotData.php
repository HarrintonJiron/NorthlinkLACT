<?php

namespace App\Console\Commands;

use App\Models\RouteRun;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\ProducerPaymentItem;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\ProducerWeekAdjustment;
use App\Modules\Producers\Models\Route;
use App\Modules\Ruteros\Models\Rutero;
use Database\Seeders\QuebarPilotSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('acopio:reset-pilot {--confirm= : Escriba REINICIAR-ACOPIO para autorizar la limpieza}')]
#[Description('Reinicia únicamente los datos operativos de acopio y carga el piloto oficial de 17 rutas QUEBAR')]
class ResetQuebarPilotData extends Command
{
    public function handle(): int
    {
        if ($this->option('confirm') !== 'REINICIAR-ACOPIO') {
            $this->error('Operación cancelada. Use --confirm=REINICIAR-ACOPIO después de crear y verificar un respaldo.');

            return self::FAILURE;
        }

        $this->table(['Dato operativo', 'Registros a retirar'], [
            ['Rutas', Route::withTrashed()->count()],
            ['Productores', Producer::withTrashed()->count()],
            ['Recolecciones', MilkCollection::query()->count()],
            ['Recorridos sincronizados', RouteRun::query()->count()],
            ['Planillas cerradas', ProducerPaymentBatch::query()->count()],
        ]);

        DB::transaction(function (): void {
            AuditEvent::query()->whereIn('entity_type', [
                Route::class,
                Producer::class,
                MilkCollection::class,
                RouteRun::class,
                ProducerPaymentBatch::class,
            ])->delete();
            ProducerPaymentItem::query()->delete();
            ProducerPaymentBatch::query()->delete();
            ProducerWeekAdjustment::query()->delete();
            MilkCollection::query()->delete();
            RouteRun::query()->delete();
            DB::table('route_operator_assignments')->delete();
            ProducerRouteAssignment::query()->delete();
            Rutero::withTrashed()->forceDelete();
            Producer::withTrashed()->forceDelete();
            Route::withTrashed()->forceDelete();

            $seeder = app(QuebarPilotSeeder::class);
            $seeder->setCommand($this);
            $seeder->run();
        });

        $this->newLine();
        $this->info('Reinicio completado: 17 rutas, 617 productores y asignaciones para hoy y mañana.');

        return self::SUCCESS;
    }
}
