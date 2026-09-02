<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\TaxPolicy;
use App\Modules\Personnel\Requests\StoreTaxPolicyRequest;
use Illuminate\Http\RedirectResponse;

class TaxPolicyController extends Controller
{
    public function store(StoreTaxPolicyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        TaxPolicy::query()->create([
            'name' => $validated['name'],
            'effective_from' => $validated['effective_from'],
            'inss_employee_rate' => $validated['inss_employee_rate'] / 100,
            'inss_employer_rate' => $validated['inss_employer_rate'] / 100,
            'inatec_rate' => $validated['inatec_rate'] / 100,
            'ir_brackets' => $request->irBrackets(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Nueva política de impuestos creada. Las planillas nuevas la usarán a partir de su fecha de vigencia.');
    }
}
