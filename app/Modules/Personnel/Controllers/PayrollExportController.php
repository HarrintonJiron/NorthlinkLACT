<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\AguinaldoPeriod;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeSettlement;
use App\Modules\Personnel\Models\PayrollPeriod;
use App\Modules\Personnel\Services\PayrollExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PayrollExportController extends Controller
{
    public function __construct(private readonly PayrollExportService $exportService) {}

    public function export(Request $request, string $section): Response
    {
        abort_unless(in_array($section, PayrollExportService::SECTIONS, true), 404);

        $validated = $request->validate([
            'range' => ['required', Rule::in(PayrollExportService::RANGES)],
            'date' => ['nullable', 'date'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        [$start, $end] = $this->exportService->resolveRange(
            $validated['range'],
            $validated['date'] ?? null,
            $validated['start'] ?? null,
            $validated['end'] ?? null,
        );

        $employee = isset($validated['employee_id']) ? Employee::find($validated['employee_id']) : null;
        $data = $this->exportService->build($section, $start, $end, $employee);
        $rangeLabel = $start->format('d/m/Y').' – '.$end->format('d/m/Y');

        if ($employee) {
            // 80mm receipt width; height is sized to the content so dompdf doesn't
            // pad extra blank pages onto a fixed oversized custom page box.
            $rowCount = max(1, count($data['rows']));
            $heightPt = 130 + ($rowCount * 22) + ($data['totals'] ? 40 : 0);

            $pdf = Pdf::loadView('pdf.ticket', [
                'data' => $data,
                'employee' => $employee,
                'rangeLabel' => $rangeLabel,
            ])->setPaper([0, 0, 226.77, $heightPt], 'portrait');

            $filename = 'ticket-'.$section.'-'.str($employee->full_name)->slug().'-'.now()->format('Ymd').'.pdf';
        } else {
            $pdf = Pdf::loadView('pdf.report', [
                'data' => $data,
                'rangeLabel' => $rangeLabel,
            ])->setPaper('letter', 'portrait');

            $filename = $section.'-'.now()->format('Ymd_His').'.pdf';
        }

        // stream() (not download()) so the browser opens the PDF inline and the
        // user chooses to print or save it from there, instead of forcing a download.
        return $pdf->stream($filename);
    }

    public function exportPlanilla(PayrollPeriod $payrollPeriod): Response
    {
        $data = $this->exportService->planillaDetail($payrollPeriod);
        $rangeLabel = $payrollPeriod->period_start->format('d/m/Y').' – '.$payrollPeriod->period_end->format('d/m/Y');

        $pdf = Pdf::loadView('pdf.report', ['data' => $data, 'rangeLabel' => $rangeLabel])
            ->setPaper('letter', 'landscape');

        return $pdf->stream('planilla-'.$payrollPeriod->code.'.pdf');
    }

    public function exportAguinaldo(AguinaldoPeriod $aguinaldoPeriod): Response
    {
        $data = $this->exportService->aguinaldoDetail($aguinaldoPeriod);
        $rangeLabel = $aguinaldoPeriod->period_start->format('d/m/Y').' – '.$aguinaldoPeriod->period_end->format('d/m/Y');

        $pdf = Pdf::loadView('pdf.report', ['data' => $data, 'rangeLabel' => $rangeLabel])
            ->setPaper('letter', 'portrait');

        return $pdf->stream('aguinaldo-'.$aguinaldoPeriod->code.'.pdf');
    }

    public function exportSettlement(EmployeeSettlement $settlement): Response
    {
        $data = $this->exportService->settlementDetail($settlement);
        $rangeLabel = 'Salida: '.$settlement->termination_date->format('d/m/Y');

        $pdf = Pdf::loadView('pdf.report', ['data' => $data, 'rangeLabel' => $rangeLabel])
            ->setPaper('letter', 'portrait');

        return $pdf->stream('liquidacion-'.$settlement->code.'.pdf');
    }
}
