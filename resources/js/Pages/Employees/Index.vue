<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const employees = page.props.employees;
const filters = page.props.filters;
const canManageEmployees = page.props.auth?.user?.permissions?.includes('manage-employees') ?? false;

const showDeleteDialog = ref(false);
const employeeToDelete = ref(null);

const confirmDelete = (employee) => {
    employeeToDelete.value = employee;
    showDeleteDialog.value = true;
};

const deleteEmployee = () => {
    router.delete(`/employees/${employeeToDelete.value.id}`, {
        onSuccess: () => { showDeleteDialog.value = false; employeeToDelete.value = null; },
    });
};

const statusVariant = (status) => {
    const map = {
        permanent: 'success',
        contract: 'info',
        probation: 'warning',
        intern: 'default',
    };
    return map[status] || 'default';
};

const columns = [
    { key: 'nik', label: 'NIK' },
    { key: 'full_name', label: 'Nama' },
    { key: 'position', label: 'Jabatan' },
    { key: 'department', label: 'Departemen' },
    { key: 'employment_status', label: 'Status' },
    { key: 'actions', label: '', sortable: false },
];
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Karyawan" description="Kelola anggota tim dan informasi mereka.">
            <template #actions>
                <Link v-if="canManageEmployees" href="/employees/create" class="btn-primary">
                    <PlusIcon class="w-5 h-5" />
                    Tambah Karyawan
                </Link>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="employees.data.map(e => ({ ...e, full_name: [e.first_name, e.last_name].filter(Boolean).join(' ') }))"
            search-placeholder="Cari berdasarkan nama, NIK, atau jabatan..."
            :server-side="true"
            :total="employees.total"
            :current-page="employees.current_page"
            :last-page="employees.last_page"
            :per-page="employees.per_page"
            :filters="filters"
            base-route="/employees"
            @row-click="(row) => router.get(`/employees/${row.id}`)"
        >
            <template #cell-full_name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                            {{ row.first_name?.charAt(0) }}{{ row.last_name?.charAt(0) || '' }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ row.full_name }}</p>
                        <p class="text-xs text-gray-400">{{ row.email }}</p>
                    </div>
                </div>
            </template>
            <template #cell-department="{ value }">
                <span>{{ value || '-' }}</span>
            </template>
            <template #cell-employment_status="{ value }">
                <Badge :variant="statusVariant(value)">{{ value }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-1" @click.stop>
                    <Link :href="`/employees/${row.id}`" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <EyeIcon class="w-4 h-4" />
                    </Link>
                    <Link :href="`/employees/${row.id}/edit`" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <PencilIcon class="w-4 h-4" />
                    </Link>
                    <button
                        class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                        @click="confirmDelete(row)"
                    >
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            :show="showDeleteDialog"
            title="Hapus Karyawan"
            :message="`Apakah Anda yakin ingin menghapus ${employeeToDelete?.full_name || employeeToDelete?.first_name}? Tindakan ini tidak dapat dibatalkan.`"
            @confirm="deleteEmployee"
            @close="showDeleteDialog = false"
        />
    </AuthenticatedLayout>
</template>
