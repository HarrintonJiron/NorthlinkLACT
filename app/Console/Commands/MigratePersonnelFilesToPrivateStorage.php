<?php

namespace App\Console\Commands;

use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeDocument;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class MigratePersonnelFilesToPrivateStorage extends Command
{
    protected $signature = 'northlink:personnel-files-private';

    protected $description = 'Move existing personnel documents from public storage to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $migrated = 0;
        $missing = 0;

        EmployeeDocument::withTrashed()
            ->whereNotNull('file_path')
            ->eachById(function (EmployeeDocument $document) use ($public, $private, &$migrated, &$missing): void {
                $this->moveFile($document->file_path, $public, $private, $migrated, $missing);
            });

        EmployeeAttendance::query()
            ->whereNotNull('justification_path')
            ->eachById(function (EmployeeAttendance $attendance) use ($public, $private, &$migrated, &$missing): void {
                $this->moveFile($attendance->justification_path, $public, $private, $migrated, $missing);
            });

        $this->info("Archivos migrados: {$migrated}. Referencias sin archivo: {$missing}.");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function moveFile(
        string $path,
        FilesystemAdapter $public,
        FilesystemAdapter $private,
        int &$migrated,
        int &$missing,
    ): void {
        if ($private->exists($path)) {
            $public->delete($path);

            return;
        }

        if (! $public->exists($path)) {
            $this->warn("No se encontró: {$path}");
            $missing++;

            return;
        }

        $stream = $public->readStream($path);
        if ($stream === false || ! $private->writeStream($path, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->warn("No se pudo migrar: {$path}");
            $missing++;

            return;
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        $public->delete($path);
        $migrated++;
    }
}
