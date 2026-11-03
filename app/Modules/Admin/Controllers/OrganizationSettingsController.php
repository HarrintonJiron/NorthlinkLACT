<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Admin\Requests\StorePlantRequest;
use App\Modules\Admin\Requests\UpdateCompanySettingsRequest;
use App\Modules\Admin\Requests\UpdatePlantRequest;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function index(): Response
    {
        $company = Company::query()->with(['plants' => fn ($query) => $query->orderBy('name')])->firstOrFail();

        return Inertia::render('Settings/Organization', ['company' => $company]);
    }

    public function updateCompany(UpdateCompanySettingsRequest $request, Company $company): RedirectResponse
    {
        $old = $company->getAttributes();
        $company->update($request->validated());
        $this->audit($company, $request->user()->id, 'update', 'Actualización de datos de la empresa', $old);

        return back()->with('success', 'Información de la empresa actualizada.');
    }

    public function storePlant(StorePlantRequest $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $plant = $company->plants()->create([...$request->validated(), 'active' => true]);
        $this->audit($plant, $request->user()->id, 'create', 'Creación de sede o planta');

        return back()->with('success', 'Sede registrada correctamente.');
    }

    public function updatePlant(UpdatePlantRequest $request, Plant $plant): RedirectResponse
    {
        $old = $plant->getAttributes();
        $plant->update($request->validated());
        $this->audit($plant, $request->user()->id, 'update', 'Actualización de sede o planta', $old);

        return back()->with('success', 'Sede actualizada correctamente.');
    }

    public function togglePlant(Plant $plant): RedirectResponse
    {
        $old = $plant->getAttributes();
        $plant->update(['active' => ! $plant->active]);
        $this->audit($plant, request()->user()->id, 'status', 'Cambio de estado de sede o planta', $old);

        return back()->with('success', $plant->active ? 'Sede activada.' : 'Sede desactivada.');
    }

    private function audit(Company|Plant $entity, int $userId, string $action, string $description, ?array $old = null): void
    {
        $companyId = $entity instanceof Company ? $entity->id : $entity->company_id;
        AuditEvent::query()->create([
            'user_id' => $userId,
            'company_id' => $companyId,
            'plant_id' => $entity instanceof Plant ? $entity->id : null,
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $entity->fresh()->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
