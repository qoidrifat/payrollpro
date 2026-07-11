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
    router.get('/admin/accounts', {
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
    router.get('/admin/accounts', {}, {
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
    roleForm.put(`/admin/accounts/${selectedAccount.value.id}/role`, {
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
    employeeForm.put(`/admin/accounts/${selectedAccount.value.id}/employee`, {
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
    passwordForm.put(`/admin/accounts/${selectedAccount.value.id}/password`, {
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

    router.post(`/admin/accounts/${selectedAccount.value.id}/${action}`, {}, {
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
            <div class="glass-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pending Review</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending || 0 }}</p>
                    </div>
                    <ExclamationTriangleIcon class="w-8 h-8 text-amber-500" />
                </div>
            </div>
            <div class="glass-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aktif</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active || 0 }}</p>
                    </div>
                    <CheckCircleIcon class="w-8 h-8 text-emerald-500" />
                </div>
            </div>
            <div class="glass-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum Terhubung</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.unlinked || 0 }}</p>
                    </div>
                    <LinkIcon class="w-8 h-8 text-blue-500" />
                </div>
            </div>
            <div class="glass-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Suspended</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.suspended || 0 }}</p>
                    </div>
                    <NoSymbolIcon class="w-8 h-8 text-red-500" />
                </div>
            </div>
        </div>

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
                    class="form-input w-40"
                    :value="filters.status || ''"
                    @change="updateFilters({ status: $event.target.value || null })"
                >
                    <option value="">Semua status</option>
                    <option value="pending">Pending</option>
                    <option value="active">Aktif</option>
                    <option value="suspended">Suspended</option>
                </select>
                <select
                    class="form-input w-36"
                    :value="filters.role || ''"
                    @change="updateFilters({ role: $event.target.value || null })"
                >
                    <option value="">Semua role</option>
                    <option value="HR">HR</option>
                    <option value="Employee">Employee</option>
                </select>
                <select
                    class="form-input w-44"
                    :value="filters.link_status || ''"
                    @change="updateFilters({ link_status: $event.target.value || null })"
                >
                    <option value="">Semua relasi</option>
                    <option value="linked">Terhubung</option>
                    <option value="unlinked">Belum terhubung</option>
                </select>
                <button class="btn-secondary" @click="clearFilters">Reset</button>
            </template>

            <template #cell-user="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-950 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                            {{ row.name?.charAt(0) || 'U' }}
                        </span>
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
                <div v-else class="inline-flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
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
                        class="p-2 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950"
                        title="Aktifkan akun"
                        @click="askActivate(row)"
                    >
                        <CheckCircleIcon class="w-4 h-4" />
                    </button>
                    <button
                        class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                        title="Nonaktifkan akun"
                        @click="askSuspend(row)"
                    >
                        <NoSymbolIcon class="w-4 h-4" />
                    </button>
                    <button
                        class="p-2 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950"
                        title="Ubah role"
                        @click="openRoleModal(row)"
                    >
                        <ShieldCheckIcon class="w-4 h-4" />
                    </button>
                    <button
                        class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950"
                        title="Hubungkan data karyawan"
                        @click="openEmployeeModal(row)"
                    >
                        <LinkIcon class="w-4 h-4" />
                    </button>
                    <button
                        class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                        title="Reset password"
                        @click="openPasswordModal(row)"
                    >
                        <LockClosedIcon class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <Modal :show="showRoleModal" title="Ubah Role Akun" @close="showRoleModal = false">
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedAccount?.email }}</p>
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <select v-model="roleForm.role" class="form-input">
                        <option value="Employee">Employee</option>
                        <option value="HR">HR</option>
                    </select>
                    <p v-if="roleForm.errors.role" class="form-error">{{ roleForm.errors.role }}</p>
                </div>
            </div>
            <template #footer>
                <button class="btn-secondary" @click="showRoleModal = false">Batal</button>
                <button class="btn-primary" :disabled="roleForm.processing" @click="submitRole">
                    {{ roleForm.processing ? 'Menyimpan...' : 'Simpan Role' }}
                </button>
            </template>
        </Modal>

        <Modal :show="showEmployeeModal" title="Hubungkan Data Karyawan" max-width="xl" @close="showEmployeeModal = false">
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedAccount?.email }}</p>
                </div>
                <div>
                    <label class="form-label">Data karyawan</label>
                    <select v-model="employeeForm.employee_id" class="form-input">
                        <option value="">Tidak dihubungkan</option>
                        <option v-for="employee in availableEmployees" :key="employee.id" :value="employee.id">
                            {{ employee.name }} - {{ employee.position }} / {{ employee.department || '-' }}
                        </option>
                    </select>
                    <p v-if="employeeForm.errors.employee_id" class="form-error">{{ employeeForm.errors.employee_id }}</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Akun pending boleh login, tetapi tidak dianggap karyawan payroll aktif sampai data karyawan terhubung dan akun diaktifkan.
                    </p>
                </div>
            </div>
            <template #footer>
                <button class="btn-secondary" @click="showEmployeeModal = false">Batal</button>
                <button class="btn-primary" :disabled="employeeForm.processing" @click="submitEmployee">
                    {{ employeeForm.processing ? 'Menyimpan...' : 'Simpan Relasi' }}
                </button>
            </template>
        </Modal>

        <Modal :show="showPasswordModal" title="Reset Password" @close="showPasswordModal = false">
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedAccount?.email }}</p>
                </div>
                <div>
                    <label class="form-label">Password baru</label>
                    <input v-model="passwordForm.password" type="password" class="form-input" autocomplete="new-password" />
                    <p v-if="passwordForm.errors.password" class="form-error">{{ passwordForm.errors.password }}</p>
                </div>
                <div>
                    <label class="form-label">Konfirmasi password</label>
                    <input v-model="passwordForm.password_confirmation" type="password" class="form-input" autocomplete="new-password" />
                    <p v-if="passwordForm.errors.password_confirmation" class="form-error">{{ passwordForm.errors.password_confirmation }}</p>
                </div>
            </div>
            <template #footer>
                <button class="btn-secondary" @click="showPasswordModal = false">Batal</button>
                <button class="btn-primary" :disabled="passwordForm.processing" @click="submitPassword">
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
