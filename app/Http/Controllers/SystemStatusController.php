<?php

namespace App\Http\Controllers;

use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SystemStatusController extends Controller
{
    public function __construct(
        private readonly StatusService $statusService,
    ) {}

    /**
     * Public system status page.
     */
    public function index(): Response
    {
        $overview = $this->statusService->getOverview();

        return Inertia::render('Status/Public', [
            'overview' => $overview,
        ]);
    }

    /**
     * API health endpoint — lightweight, no auth required.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '1.0.0'),
            'services'  => [
                'database' => $this->checkDbQuick() ? 'up' : 'down',
                'cache'    => $this->checkCacheQuick() ? 'up' : 'down',
            ],
        ]);
    }

    /**
     * API: full status data (for realtime polling).
     */
    public function apiStatus(): JsonResponse
    {
        return response()->json($this->statusService->getOverview());
    }

    private function checkDbQuick(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function checkCacheQuick(): bool
    {
        try {
            \Illuminate\Support\Facades\Cache::set('health_check', true, 10);
            return \Illuminate\Support\Facades\Cache::get('health_check') === true;
        } catch (\Exception) {
            return false;
        }
    }
}
