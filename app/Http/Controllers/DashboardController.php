<?php

namespace App\Http\Controllers;

use App\Modules\Admin\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): Response
    {
        return Inertia::render('Admin/Dashboard', [
            ...$dashboardService->data(),
            'userName' => $request->user()->name,
        ]);
    }
}
