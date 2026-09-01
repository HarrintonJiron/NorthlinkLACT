<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeDocument;
use App\Modules\Personnel\Requests\StoreEmployeeDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function store(StoreEmployeeDocumentRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');
        $path = $file->store("employee-documents/{$employee->id}", 'public');

        EmployeeDocument::query()->create([
            'employee_id' => $employee->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Documento cargado correctamente.');
    }

    public function download(Employee $employee, EmployeeDocument $document): StreamedResponse
    {
        abort_unless($document->employee_id === $employee->id, 404);

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        abort_unless($document->employee_id === $employee->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Documento eliminado correctamente.');
    }
}
