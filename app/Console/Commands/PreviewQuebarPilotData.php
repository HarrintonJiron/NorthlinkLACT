<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JsonException;

#[Signature('acopio:preview-pilot {--source= : Ruta absoluta o relativa del JSON a revisar}')]
#[Description('Valida la fuente de rutas y productores sin modificar la base de datos')]
class PreviewQuebarPilotData extends Command
{
    public function handle(): int
    {
        $source = $this->option('source') ?: database_path('data/quebar_pilot_routes.json');
        if (! str_starts_with($source, DIRECTORY_SEPARATOR)) {
            $source = base_path($source);
        }

        if (! is_file($source)) {
            $this->error("No se encontró la fuente: {$source}");

            return self::FAILURE;
        }

        try {
            $routes = json_decode((string) file_get_contents($source), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('El JSON no es válido: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! is_array($routes)) {
            $this->error('La fuente debe contener una lista de rutas.');

            return self::FAILURE;
        }

        $routeRows = [];
        $names = [];
        $identities = [];
        $errors = [];
        $producerCount = 0;
        $missingPhones = 0;
        $transfersWithoutAccount = 0;

        foreach ($routes as $routeIndex => $route) {
            $code = trim((string) ($route['code'] ?? ''));
            $name = trim((string) ($route['name'] ?? ''));
            $producers = is_array($route['producers'] ?? null) ? $route['producers'] : [];
            if ($code === '' || $name === '') {
                $errors[] = 'Ruta #'.($routeIndex + 1).' sin código o nombre.';
            }

            $routeRows[] = [$code ?: '—', $name ?: '—', count($producers)];
            $producerCount += count($producers);

            foreach ($producers as $producerIndex => $producer) {
                $producerName = trim((string) ($producer['name'] ?? ''));
                $identity = trim((string) ($producer['identity'] ?? ''));
                if ($producerName === '') {
                    $errors[] = "{$code}: productor #".($producerIndex + 1).' sin nombre.';

                    continue;
                }

                $normalizedName = $code.'|'.Str::of($producerName)->ascii()->lower()->squish()->toString();
                $names[$normalizedName][] = "{$producerName} ({$name})";
                if (blank($producer['phone'] ?? null)) {
                    $missingPhones++;
                }
                if (($producer['payment_method'] ?? 'cash') === 'transfer'
                    && blank($producer['bank_account_number'] ?? null)) {
                    $transfersWithoutAccount++;
                }
                if ($identity !== '') {
                    $identities[$identity][] = "{$producerName} ({$name})";
                }
            }
        }

        $duplicateNames = array_filter($names, fn (array $items): bool => count($items) > 1);
        $identityConflicts = array_filter($identities, function (array $items): bool {
            $people = array_unique(array_map(fn (string $item): string => Str::before($item, ' ('), $items));

            return count($people) > 1;
        });

        $this->info('Vista previa; no se modificó la base de datos.');
        $this->line("Fuente: {$source}");
        $this->table(['Código', 'Ruta', 'Productores'], $routeRows);
        $this->table(['Indicador', 'Resultado'], [
            ['Rutas', count($routes)],
            ['Productores', $producerCount],
            ['Nombres repetidos dentro de una ruta', count($duplicateNames)],
            ['Cédulas asignadas a personas distintas', count($identityConflicts)],
            ['Teléfonos pendientes', $missingPhones],
            ['Transferencias sin cuenta bancaria', $transfersWithoutAccount],
            ['Errores estructurales', count($errors)],
        ]);

        foreach ($identityConflicts as $identity => $people) {
            $this->warn("Cédula en conflicto {$identity}: ".implode(' / ', $people));
        }
        foreach ($errors as $error) {
            $this->error($error);
        }

        if (count($routes) < 17 || $errors !== [] || $identityConflicts !== [] || $duplicateNames !== []) {
            $this->warn('La fuente no está aprobada para carga automática. Corrija o autorice explícitamente las excepciones.');

            return self::FAILURE;
        }

        $this->info('La fuente cumple la estructura mínima para una carga controlada.');

        return self::SUCCESS;
    }
}
