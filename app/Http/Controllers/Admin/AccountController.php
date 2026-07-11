<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'role', 'link_status']);
        $companyId = TenantScope::currentCompanyId();

        $query = $this->manageableAccountsQuery($companyId)
            ->with(['employee:id,company_id,user_id,first_name,last_name,position,department,is_active'])
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('position', 'like', "%{$search}%")
                                ->orWhere('department', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('account_status', $status))
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when($filters['link_status'] ?? null, function ($query, string $linkStatus) {
                $linkStatus === 'linked'
                    ? $query->whereHas('employee')
                    : $query->whereDoesntHave('employee');
            })
            ->latest();

        $accounts = $query
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString()
            ->through(fn (User $user) => $this->serializeAccount($user));

        $employees = Employee::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->select(['id', 'company_id', 'user_id', 'first_name', 'last_name', 'position', 'department', 'is_active'])
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'user_id' => $employee->user_id,
                'name' => $employee->full_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'is_active' => $employee->is_active,
            ]);

        return Inertia::render('Admin/Accounts/Index', [
            'accounts' => $accounts,
            'filters' => $filters,
            'employees' => $employees,
            'stats' => [
                'pending' => (clone $this->manageableAccountsQuery($companyId))->where('account_status', User::STATUS_PENDING)->count(),
                'active' => (clone $this->manageableAccountsQuery($companyId))->where('account_status', User::STATUS_ACTIVE)->count(),
                'suspended' => (clone $this->manageableAccountsQuery($companyId))->where('account_status', User::STATUS_SUSPENDED)->count(),
                'unlinked' => (clone $this->manageableAccountsQuery($companyId))->whereDoesntHave('employee')->count(),
            ],
        ]);
    }

    public function activate(User $account): RedirectResponse
    {
        $this->ensureManageable($account);

        $account->activate(request()->user());
        $this->forgetInertiaAuthMeta($account);

        return back()->with('success', 'Akun berhasil diaktifkan.');
    }

    public function suspend(User $account): RedirectResponse
    {
        $this->ensureManageable($account);

        $account->suspend();
        $this->forgetInertiaAuthMeta($account);

        return back()->with('success', 'Akun berhasil dinonaktifkan sementara.');
    }

    public function updateRole(Request $request, User $account): RedirectResponse
    {
        $this->ensureManageable($account);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['HR', 'Employee'])],
        ]);

        Role::firstOrCreate(['name' => $validated['role']]);
        $account->syncRoles([$validated['role']]);
        $this->forgetInertiaAuthMeta($account);

        return back()->with('success', 'Role akun berhasil diperbarui.');
    }

    public function linkEmployee(Request $request, User $account): RedirectResponse
    {
        $this->ensureManageable($account);
        $companyId = TenantScope::currentCompanyId();

        $validated = $request->validate([
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')
                    ->where(fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query),
            ],
        ]);

        DB::transaction(function () use ($account, $validated, $companyId) {
            $account->employee()->update(['user_id' => null]);

            if (empty($validated['employee_id'])) {
                return;
            }

            $employee = Employee::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->findOrFail($validated['employee_id']);

            if ($employee->user_id && $employee->user_id !== $account->id) {
                abort(422, 'Data karyawan ini sudah terhubung dengan akun lain.');
            }

            $account->forceFill(['company_id' => $employee->company_id])->save();
            $employee->forceFill(['user_id' => $account->id])->save();
        });
        $this->forgetInertiaAuthMeta($account);

        return back()->with('success', 'Relasi akun dan data karyawan berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $account): RedirectResponse
    {
        $this->ensureManageable($account);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $account->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('success', 'Kata sandi akun berhasil direset.');
    }

    private function serializeAccount(User $user): array
    {
        $role = $user->roles->first()?->name ?? 'Employee';
        $employee = $user->employee;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'account_status' => $user->account_status ?? User::STATUS_PENDING,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'approved_at' => $user->approved_at?->toISOString(),
            'suspended_at' => $user->suspended_at?->toISOString(),
            'last_login_at' => $user->last_login_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'is_active' => $employee->is_active,
            ] : null,
        ];
    }

    private function ensureManageable(User $account): void
    {
        abort_if($account->hasRole('Admin'), 403, 'Akun admin tidak dikelola dari halaman ini.');
        abort_if($account->id === request()->user()?->id, 403, 'Anda tidak dapat mengubah akun sendiri dari halaman ini.');

        $companyId = TenantScope::currentCompanyId();

        abort_if($companyId && (int) $account->company_id !== (int) $companyId, 403, 'Akun berada di luar perusahaan aktif.');
    }

    private function manageableAccountsQuery(?int $companyId): Builder
    {
        return User::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->whereIn('name', ['HR', 'Employee']));
    }

    private function forgetInertiaAuthMeta(User $user): void
    {
        Cache::forget("inertia:user-auth-meta:{$user->id}");
    }
}
