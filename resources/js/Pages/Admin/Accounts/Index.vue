<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    LinkIcon,
    LockClosedIcon,
    NoSymbolIcon,
    ShieldCheckIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const accounts = computed(() => page.props.accounts);
const filters = computed(() => page.props.filters || {});
const employees = computed(() => page.props.employees || []);
const stats = computed(() => page.props.stats || {});

const selectedAccount = ref(null);
const confirmAction = ref(null);
const showRoleModal = ref(false);
const showEmployeeModal = ref(false);
const showPasswordModal = ref(false);

const roleForm = useForm({ role: 'Employee' });
const employeeForm = useForm({ employee_id: '' });
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const columns = [
    { key: 'user', label: 'Akun' },
    { key: 'role', label: 'Role' },
    { key: 'account_status', label: 'Status' },
    { key: 'employee', label: 'Data Karyawan', sortable: false },
    { key: 'last_login_at', label: 'Login Terakhir' },
    { key: 'actions', label: '', sortable: false },
];

const statusMeta = {
    pending: { label: 'Pending', variant: 'warning' },
    active: { label: 'Aktif', variant: 'success' },
    suspended: { label: 'Suspended', variant: 'danger' },
};

const roleVariant = (role) => role === 'HR' ? 'primary' : 'info';

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const updateFilters = (extra = {}) => {
    router.get(route('admin.accounts.index'), {
        ...filters.value,
        ...extra,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearFilters = () => {
    router.get(route('admin.accounts.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const openRoleModal = (account) => {
    selectedAccount.value = account;
    roleForm.role = account.role || 'Employee';
    roleForm.clearErrors();
    showRoleModal.value = true;
};

const submitRole = () => {
    roleForm.put(route('admin.accounts.role.update', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => showRoleModal.value = false,
    });
};

const openEmployeeModal = (account) => {
    selectedAccount.value = account;
    employeeForm.employee_id = account.employee?.id || '';
    employeeForm.clearErrors();
    showEmployeeModal.value = true;
};

const availableEmployees = computed(() => {
    const currentUserId = selectedAccount.value?.id;

    return employees.value.filter((employee) => {
        return !employee.user_id || employee.user_id === currentUserId;
    });
});

const submitEmployee = () => {
    employeeForm.put(route('admin.accounts.employee.update', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => showEmployeeModal.value = false,
    });
};

const openPasswordModal = (account) => {
    selectedAccount.value = account;
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const submitPassword = () => {
    passwordForm.put(route('admin.accounts.password.update', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showPasswordModal.value = false;
        },
    });
};

const askActivate = (account) => {
    selectedAccount.value = account;
    confirmAction.value = 'activate';
};

const askSuspend = (account) => {
    selectedAccount.value = account;
    confirmAction.value = 'suspend';
};

const submitConfirmAction = () => {
    const action = confirmAction.value;

    router.post(action === 'activate' ? route('admin.accounts.activate', selectedAccount.value.id) : route('admin.accounts.suspend', selectedAccount.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            confirmAction.value = null;
            selectedAccount.value = null;
        },
    });
};

const confirmDialog = computed(() => {
    if (confirmAction.value === 'activate') {
        return {
            title: 'Aktifkan Akun',
            message: `Aktifkan ${selectedAccount.value?.name}? Akun dapat login dan diproses lebih lanjut setelah data karyawan terhubung.`,
            confirmText: 'Aktifkan',
            confirmVariant: 'primary',
        };
    }

    return {
        title: 'Nonaktifkan Akun',
        message: `Nonaktifkan sementara ${selectedAccount.value?.name}? Akun tidak dapat login sampai diaktifkan kembali.`,
        confirmText: 'Nonaktifkan',
        confirmVariant: 'danger',
    };
});
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader
            title="Kelola Akun"
            description="Aktivasi akun employee pending, kelola role HR, dan hubungkan akun dengan data karyawan."
        />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white shadow-lg shadow-amber-500/20">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-white/80">Pending Review</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight">{{ stats.pending || 0 }}</p>
                        </div>
                        <ExclamationTriangleIcon class="w-8 h-8 text-white/60" />
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-lg shadow-emerald-500/20">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-white/80">Aktif</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight">{{ stats.active || 0 }}</p>
                        </div>
                        <CheckCircleIcon class="w-8 h-8 text-white/60" />
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-6 text-white shadow-lg shadow-blue-500/20">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-white/80">Belum Terhubung</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight">{{ stats.unlinked || 0 }}</p>
                        </div>
                        <LinkIcon class="w-8 h-8 text-white/60" />
                    </div>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 p-6 text-white shadow-lg shadow-red-500/20">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-white/80">Suspended</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight">{{ stats.suspended || 0 }}</p>
                        </div>
                        <NoSymbolIcon class="w-8 h-8 text-white/60" />
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-sm">
                    <UserGroupIcon class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Akun</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ accounts.total || 0 }} total akun terdaftar</p>
                </div>
            </div>
            <div class="p-6">
                <DataTable
                    server-side
                    base-route="/admin/accounts"
                    :columns="columns"
                    :rows="accounts.data"
                    :filters="filters"
                    :total="accounts.total"
                    :current-page="accounts.current_page"
                    :last-page="accounts.last_page"
                    :per-page="accounts.per_page"
                    search-placeholder="Cari nama, email, jabatan, atau departemen..."
                >
                    <template #toolbar>
                        <select
                            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all w-40"
                            :value="filters.status || ''"
                            @change="updateFilters({ status: $event.target.value || null })"
                        >
                            <option value="">Semua status</option>
                            <option value="pending">Pending</option>
                            <option value="active">Aktif</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        <select
                            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all w-36"
                            :value="filters.role || ''"
                            @change="updateFilters({ role: $event.target.value || null })"
                        >
                            <option value="">Semua role</option>
                            <option value="HR">HR</option>
                            <option value="Employee">Employee</option>
                        </select>
                        <select
                            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all w-44"
                            :value="filters.link_status || ''"
                            @change="updateFilters({ link_status: $event.target.value || null })"
                        >
                            <option value="">Semua relasi</option>
                            <option value="linked">Terhubung</option>
                            <option value="unlinked">Belum terhubung</option>
                        </select>
                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-md active:scale-[0.98] transition-all duration-200" @click="clearFilters">Reset</button>
                    </template>

                    <template #cell-user="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-sm">
                                <span class="text-sm font-semibold">{{ row.name?.charAt(0) || 'U' }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ row.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ row.email }}</p>
                            </div>
                        </div>
                    </template>

                    <template #cell-role="{ value }">
                        <Badge :variant="roleVariant(value)">
                            <span class="inline-flex items-center gap-1">
                                <ShieldCheckIcon class="w-3.5 h-3.5" />
                                {{ value }}
                            </span>
                        </Badge>
                    </template>

                    <template #cell-account_status="{ value }">
                        <Badge :variant="statusMeta[value]?.variant || 'default'">
                            {{ statusMeta[value]?.label || value }}
                        </Badge>
                    </template>

                    <template #cell-employee="{ row }">
                        <div v-if="row.employee" class="min-w-56">
                            <p class="font-medium text-gray-900 dark:text-white">{{ row.employee.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ row.employee.position }} · {{ row.employee.department || '-' }}
                            </p>
                        </div>
                        <div v-else class="inline-flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                            <ExclamationTriangleIcon class="w-4 h-4" />
                            Belum terhubung
                        </div>
                    </template>

                    <template #cell-last_login_at="{ value }">
                        {{ formatDate(value) }}
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="flex items-center justify-end gap-1" @click.stop>
                            <button
                                class="p-2 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950 transition-all"
                                title="Aktifkan akun"
                                @click="askActivate(row)"
                            >
                                <CheckCircleIcon class="w-4 h-4" />
                            </button>
                            <button
                                class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950 transition-all"
                                title="Nonaktifkan akun"
                                @click="askSuspend(row)"
                            >
                                <NoSymbolIcon class="w-4 h-4" />
                            </button>
                            <button
                                class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all"
                                title="Ubah role"
                                @click="openRoleModal(row)"
                            >
                                <ShieldCheckIcon class="w-4 h-4" />
                            </button>
                            <button
                                class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950 transition-all"
                                title="Hubungkan data karyawan"
                                @click="openEmployeeModal(row)"
                            >
                                <LinkIcon class="w-4 h-4" />
                            </button>
                            <button
                                class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all"
                                title="Reset password"
                                @click="openPasswordModal(row)"
                            >
                                <LockClosedIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Role Modal -->
        <Modal :show="showRoleModal" title="Ubah Role Akun" @close="showRoleModal = false">
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center">
                        <span class="text-sm font-semibold">{{ selectedAccount?.name?.charAt(0) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAccount?.email }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Role</label>
                    <select v-model="roleForm.role" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all">
                        <option value="Employee">Employee</option>
                        <option value="HR">HR</option>
                    </select>
                    <p v-if="roleForm.errors.role" class="mt-1 text-sm text-red-600">{{ roleForm.errors.role }}</p>
                </div>
            </div>
            <template #footer>
                <button class="px-5 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all" @click="showRoleModal = false">Batal</button>
                <button class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 disabled:opacity-60" :disabled="roleForm.processing" @click="submitRole">
                    {{ roleForm.processing ? 'Menyimpan...' : 'Simpan Role' }}
                </button>
            </template>
        </Modal>

        <!-- Employee Modal -->
        <Modal :show="showEmployeeModal" title="Hubungkan Data Karyawan" max-width="xl" @close="showEmployeeModal = false">
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center">
                        <span class="text-sm font-semibold">{{ selectedAccount?.name?.charAt(0) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAccount?.email }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Data Karyawan</label>
                    <select v-model="employeeForm.employee_id" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all">
                        <option value="">Tidak dihubungkan</option>
                        <option v-for="employee in availableEmployees" :key="employee.id" :value="employee.id">
                            {{ employee.name }} - {{ employee.position }} / {{ employee.department || '-' }}
                        </option>
                    </select>
                    <p v-if="employeeForm.errors.employee_id" class="mt-1 text-sm text-red-600">{{ employeeForm.errors.employee_id }}</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Akun pending boleh login, tetapi tidak dianggap karyawan payroll aktif sampai data karyawan terhubung dan akun diaktifkan.
                    </p>
                </div>
            </div>
            <template #footer>
                <button class="px-5 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all" @click="showEmployeeModal = false">Batal</button>
                <button class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 disabled:opacity-60" :disabled="employeeForm.processing" @click="submitEmployee">
                    {{ employeeForm.processing ? 'Menyimpan...' : 'Simpan Relasi' }}
                </button>
            </template>
        </Modal>

        <!-- Password Modal -->
        <Modal :show="showPasswordModal" title="Reset Password" @close="showPasswordModal = false">
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center">
                        <span class="text-sm font-semibold">{{ selectedAccount?.name?.charAt(0) }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedAccount?.email }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Password baru</label>
                    <input v-model="passwordForm.password" type="password" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all" autocomplete="new-password" />
                    <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Konfirmasi password</label>
                    <input v-model="passwordForm.password_confirmation" type="password" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all" autocomplete="new-password" />
                    <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password_confirmation }}</p>
                </div>
            </div>
            <template #footer>
                <button class="px-5 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all" @click="showPasswordModal = false">Batal</button>
                <button class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 disabled:opacity-60" :disabled="passwordForm.processing" @click="submitPassword">
                    {{ passwordForm.processing ? 'Mereset...' : 'Reset Password' }}
                </button>
            </template>
        </Modal>

        <ConfirmDialog
            :show="Boolean(confirmAction)"
            :title="confirmDialog.title"
            :message="confirmDialog.message"
            :confirm-text="confirmDialog.confirmText"
            :confirm-variant="confirmDialog.confirmVariant"
            @confirm="submitConfirmAction"
            @close="confirmAction = null"
        />
    </AuthenticatedLayout>
</template>
