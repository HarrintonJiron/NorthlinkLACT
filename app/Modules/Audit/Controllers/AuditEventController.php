<?php

namespace App\Modules\Audit\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditEventController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $events = AuditEvent::query()->with('user:id,name,username')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->whereRaw('LOWER(description) LIKE ?', ['%'.mb_strtolower($search).'%'])
                ->orWhereRaw('LOWER(entity_type) LIKE ?', ['%'.mb_strtolower($search).'%'])))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Settings/Audit', [
            'events' => $events,
            'filters' => $filters,
            'actions' => AuditEvent::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
