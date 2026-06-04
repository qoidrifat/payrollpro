<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBpjsConfigRequest;
use App\Http\Requests\UpdatePph21ConfigRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\BpjsConfig;
use App\Models\Pph21Config;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Display current application settings including BPJS & PPh21 configs.
     */
    public function index(): Response
    {
        Gate::authorize('manage-settings');

        $currentYear = date('Y');

        $bpjsRates = BpjsConfig::active()
            ->forYear($currentYear)
            ->orderBy('type')
            ->orderBy('payer')
            ->get();

        $pph21Brackets = Pph21Config::active()
            ->forYear($currentYear)
            ->orderBy('income_bracket_start')
            ->get();

        return Inertia::render('Settings/Index', [
            'settings' => $this->settingService->getCompanySettings(),
            'bpjsRates' => $bpjsRates,
            'pph21Brackets' => $pph21Brackets,
        ]);
    }

    /**
     * Update company information settings.
     */
    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $this->settingService->updateCompanySettings($request->validated());

        return redirect()
            ->route('settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui.');
    }

    /**
     * Update BPJS configuration rates.
     */
    public function updateBpjs(UpdateBpjsConfigRequest $request): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $configs = $request->validated('configs');
        $submittedIds = [];

        foreach ($configs as $data) {
            if (!empty($data['id'])) {
                $submittedIds[] = $data['id'];
                BpjsConfig::where('id', $data['id'])->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'payer' => $data['payer'],
                    'rate_percentage' => $data['rate_percentage'],
                    'salary_cap' => $data['salary_cap'] ?? null,
                    'applicable_year' => $data['applicable_year'],
                    'description' => $data['description'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            } else {
                BpjsConfig::create([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'payer' => $data['payer'],
                    'rate_percentage' => $data['rate_percentage'],
                    'salary_cap' => $data['salary_cap'] ?? null,
                    'applicable_year' => $data['applicable_year'],
                    'description' => $data['description'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
            }
        }

        // Deactivate records that were removed from the UI
        if (!empty($submittedIds)) {
            BpjsConfig::whereNotIn('id', $submittedIds)
                ->where('is_active', true)
                ->where('applicable_year', date('Y'))
                ->update(['is_active' => false]);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Tarif BPJS berhasil diperbarui.');
    }

    /**
     * Update PPh21 tax brackets.
     */
    public function updatePph21(UpdatePph21ConfigRequest $request): RedirectResponse
    {
        Gate::authorize('manage-settings');

        $brackets = $request->validated('brackets');
        $submittedIds = [];

        foreach ($brackets as $data) {
            if (!empty($data['id'])) {
                $submittedIds[] = $data['id'];
                Pph21Config::where('id', $data['id'])->update([
                    'income_bracket_start' => $data['income_bracket_start'],
                    'income_bracket_end' => $data['income_bracket_end'] ?? null,
                    'rate_percentage' => $data['rate_percentage'],
                    'applicable_year' => $data['applicable_year'],
                    'is_active' => $data['is_active'] ?? true,
                ]);
            } else {
                Pph21Config::create([
                    'income_bracket_start' => $data['income_bracket_start'],
                    'income_bracket_end' => $data['income_bracket_end'] ?? null,
                    'rate_percentage' => $data['rate_percentage'],
                    'applicable_year' => $data['applicable_year'],
                    'is_active' => $data['is_active'] ?? true,
                ]);
            }
        }

        // Deactivate brackets that were removed from the UI
        if (!empty($submittedIds)) {
            Pph21Config::whereNotIn('id', $submittedIds)
                ->where('is_active', true)
                ->where('applicable_year', date('Y'))
                ->update(['is_active' => false]);
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Bracket PPh21 berhasil diperbarui.');
    }
}
