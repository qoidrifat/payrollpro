<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Http\Requests\UpdateBpjsConfigRequest;
use App\Http\Requests\UpdatePph21ConfigRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Models\BpjsConfig;
use App\Models\Pph21Config;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Display settings page with role-based sections.
     * Admin sees all sections. HR sees attendance/operational. Employee sees personal.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('Admin');
        $isHr = $user->hasRole('HR');
        $isEmployee = $user->hasRole('Employee');

        $currentYear = date('Y');

        // ── Admin sections ────────────────────────────────────────────
        $bpjsRates = [];
        $pph21Brackets = [];

        if ($isAdmin) {
            $bpjsRates = BpjsConfig::active()
                ->forYear($currentYear)
                ->orderBy('type')
                ->orderBy('payer')
                ->get();

            $pph21Brackets = Pph21Config::active()
                ->forYear($currentYear)
                ->orderBy('income_bracket_start')
                ->get();
        }

        // ── Shared sections ───────────────────────────────────────────
        $companySettings = $isAdmin ? $this->settingService->getCompanySettings() : null;
        $attendanceSettings = ($isAdmin || $isHr) ? $this->settingService->getAttendanceSettings() : null;
        $notificationSettings = $this->settingService->getNotificationSettings($isAdmin ? null : $user->id);

        return Inertia::render('Settings/Index', [
            'role' => $isAdmin ? 'admin' : ($isHr ? 'hr' : 'employee'),
            'companySettings' => $companySettings,
            'attendanceSettings' => $attendanceSettings,
            'notificationSettings' => $notificationSettings,
            'bpjsRates' => $bpjsRates,
            'pph21Brackets' => $pph21Brackets,
        ]);
    }

    /**
     * Update company information settings (Admin only).
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
     * Update attendance operational settings (Admin & HR).
     */
    public function updateAttendance(UpdateAttendanceSettingsRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['Admin', 'HR'])) {
            abort(403, 'Unauthorized action.');
        }

        $this->settingService->updateAttendanceSettings($request->validated());

        return redirect()
            ->route('settings.index')
            ->with('success', 'Pengaturan absensi berhasil diperbarui.');
    }

    /**
     * Update notification preferences (All roles).
     */
    public function updateNotifications(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('Admin');

        $this->settingService->updateNotificationSettings(
            $request->validated(),
            $isAdmin ? null : $user->id
        );

        return redirect()
            ->route('settings.index')
            ->with('success', 'Preferensi notifikasi berhasil diperbarui.');
    }

    /**
     * Update BPJS configuration rates (Admin only).
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
     * Update PPh21 tax brackets (Admin only).
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
