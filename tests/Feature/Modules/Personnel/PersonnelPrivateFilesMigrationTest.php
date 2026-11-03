<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonnelPrivateFilesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_public_personnel_files_are_moved_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $documentPath = "employee-documents/{$employee->id}/document.pdf";
        $attendancePath = "employee-attendances/{$employee->id}/justification.pdf";
        Storage::disk('public')->put($documentPath, 'documento');
        Storage::disk('public')->put($attendancePath, 'justificación');

        EmployeeDocument::query()->create([
            'employee_id' => $employee->id,
            'name' => 'Contrato',
            'type' => 'contrato',
            'file_path' => $documentPath,
            'file_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 9,
        ]);
        EmployeeAttendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-11-03',
            'type' => EmployeeAttendance::TYPE_AUSENTE,
            'justification_path' => $attendancePath,
            'justification_name' => 'justification.pdf',
        ]);

        $this->artisan('northlink:personnel-files-private')->assertSuccessful();

        Storage::disk('local')->assertExists($documentPath);
        Storage::disk('local')->assertExists($attendancePath);
        Storage::disk('public')->assertMissing($documentPath);
        Storage::disk('public')->assertMissing($attendancePath);
    }
}
