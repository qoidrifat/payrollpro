<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import Badge from '@/Components/Badge.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useSupabaseRealtime } from '@/composables/useSupabaseRealtime';
import { PlusIcon, PencilIcon, TrashIcon, FunnelIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const attendancesProp = page.props.attendances;
const totalRecords = page.props.total || 0;
const filters = page.props.filters || {};

const showBulkModal = ref(false);
const showDeleteDialog = ref(false);
const attendanceToDelete = ref(null);
let realtimeUnsubscribe = null;
let realtimeReloadTimer = null;
const realtime = useSupabaseRealtime();

const realtimeStatusLabel = computed(() => {
    if (!realtime.isConfigured) return 'Realtime nonaktif';
    if (realtime.status.value === 'SUBSCRIBED') return 'Realtime aktif';
    if (['CHANNEL_ERROR', 'TIMED_OUT', 'CLOSED'].includes(realtime.status.value)) return 'Realtime terputus';
    return 'Menghubungkan realtime';
});

const realtimeStatusClass = computed(() => {
    if (!realtime.isConfigured) return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    if (realtime.status.value === 'SUBSCRIBED') return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    if (['CHANNEL_ERROR', 'TIMED_OUT', 'CLOSED'].includes(realtime.status.value)) return 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300';
    return 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
});

const selectedMonth = ref(filters.month || '');

const bulkForm = ref({
    employee_ids: [],
    date: new Date().toISOString().slice(0, 10),
    status: 'present',
    type: 'wfo',
    clock_in: '08:00',
    clock_out: '17:00',
    notes: '',
});

const monthName = (m) => {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return months[parseInt(m) - 1] || m;
};

// Gunakan daftar bulan dari data absensi yang ada di database
const months = ref([]);
// Parse availableMonths
const raw = page.props.availableMonths || [];
const sorted = [...raw].sort().reverse();
months.value = sorted.map(value => ({
    value,
    label: `${monthName(value.slice(5, 7))} ${value.slice(0, 4)}`,
}));

const applyMonthFilter = () => {
    const query = { month: selectedMonth.value || null, page: 1 };
    router.get('/attendances', query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearFilter = () => {
    selectedMonth.value = '';
    router.get('/attendances', { page: 1 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const statusVariant = (status) => {
    const map = { present: 'success', absent: 'danger', late: 'warning', half_day: 'info', sick: 'info', leave: 'primary' };
    return map[status] || 'default';
};

const typeLabel = (type) => {
    const map = { wfo: 'WFO', wfh: 'WFH', remote: 'Remote' };
    return map[type] || type;
};

const confirmDelete = (attendance) => {
    attendanceToDelete.value = attendance;
    showDeleteDialog.value = true;
};

const deleteAttendance = () => {
    router.delete(`/attendances/${attendanceToDelete.value.id}`, {
        onSuccess: () => { showDeleteDialog.value = false; attendanceToDelete.value = null; },
    });
};

const submitBulk = () => {
    router.post('/attendances/bulk', bulkForm.value, {
        onSuccess: () => { showBulkModal.value = false; },
    });
};

const refreshAttendanceList = () => {
    if (realtimeReloadTimer) clearTimeout(realtimeReloadTimer);

    realtimeReloadTimer = setTimeout(() => {
        router.reload({
            only: ['attendances', 'total', 'availableMonths'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 500);
};

onMounted(() => {
    realtimeUnsubscribe = realtime.subscribeToNotifications({
        channelName: 'project-kp-attendances',
        topics: ['attendance'],
        onChange: refreshAttendanceList,
    });
});

onUnmounted(() => {
    if (realtimeReloadTimer) clearTimeout(realtimeReloadTimer);
    if (realtimeUnsubscribe) realtimeUnsubscribe();
});

const columns = [
    { key: 'date', label: 'Tanggal' },
    { key: 'employee_name', label: 'Karyawan', sortable: false },
    { key: 'status', label: 'Status' },
    { key: 'type', label: 'Tipe' },
    { key: 'clock_in', label: 'Absen Masuk' },
    { key: 'clock_out', label: 'Absen Pulang' },
    { key: 'actions', label: '', sortable: false },
];
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Absensi" description="Pantau absensi dan jam kerja karyawan.">
            <template #actions>
                <button class="btn-secondary" @click="showBulkModal = true">Entri Massal</button>
                <Link href="/attendances/create" class="btn-primary">
                    <PlusIcon class="w-5 h-5" />
                    Catat Absensi
                </Link>
            </template>
        </PageHeader>

        <DataTable
            :columns="columns"
            :rows="attendancesProp.data.map(a => ({ ...a, employee_name: a.employee?.full_name }))"
            search-placeholder="Cari berdasarkan nama karyawan..."
            :server-side="true"
            :total="attendancesProp.total"
            :current-page="attendancesProp.current_page"
            :last-page="attendancesProp.last_page"
            :per-page="attendancesProp.per_page"
            :filters="filters"
            base-route="/attendances"
        >
            <template #toolbar>
                <div class="flex items-center gap-2">
                    <FunnelIcon class="w-4 h-4 text-gray-400" />
                    <select
                        v-model="selectedMonth"
                        class="form-input w-auto min-w-[150px] text-sm py-1.5"
                        @change="applyMonthFilter"
                    >
                        <option value="">Semua Bulan</option>
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>
                <button
                    v-if="selectedMonth"
                    class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium whitespace-nowrap transition-colors"
                    @click="clearFilter"
                >
                    &times; Hapus
                </button>
                <div class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap border-l border-gray-200 dark:border-gray-700 pl-3">
                    Total: <strong>{{ totalRecords }}</strong>
                </div>
                <div :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap', realtimeStatusClass]">
                    {{ realtimeStatusLabel }}
                </div>
            </template>
            <template #cell-employee_name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                            {{ row.employee?.first_name?.charAt(0) }}
                        </span>
                    </div>
                    <span class="font-medium">{{ row.employee_name }}</span>
                </div>
            </template>
            <template #cell-status="{ value }">
                <Badge :variant="statusVariant(value)">{{ value }}</Badge>
            </template>
            <template #cell-type="{ value }">
                {{ typeLabel(value) }}
            </template>
            <template #cell-clock_in="{ value }">
                {{ value || '-' }}
            </template>
            <template #cell-clock_out="{ value }">
                {{ value || '-' }}
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2" @click.stop>
                    <Link :href="`/attendances/${row.id}/edit`" class="btn-secondary text-xs py-1.5 px-3">
                        <PencilIcon class="w-4 h-4" />
                        Edit
                    </Link>
                    <button
                        class="btn-danger text-xs py-1.5 px-3"
                        @click="confirmDelete(row)"
                    >
                        <TrashIcon class="w-4 h-4" />
                        Hapus
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Bulk Entry Modal -->
        <Modal :show="showBulkModal" title="Entri Absensi Massal" @close="showBulkModal = false">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input v-model="bulkForm.date" type="date" class="form-input" />
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select v-model="bulkForm.status" class="form-input">
                        <option value="present">Hadir</option>
                        <option value="absent">Tidak Hadir</option>
                        <option value="late">Terlambat</option>
                        <option value="half_day">Setengah Hari</option>
                        <option value="sick">Sakit</option>
                        <option value="leave">Cuti</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tipe</label>
                    <select v-model="bulkForm.type" class="form-input">
                        <option value="wfo">WFO</option>
                        <option value="wfh">WFH</option>
                        <option value="remote">Remote</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Absen Masuk</label>
                        <input v-model="bulkForm.clock_in" type="time" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Absen Pulang</label>
                        <input v-model="bulkForm.clock_out" type="time" class="form-input" />
                    </div>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <textarea v-model="bulkForm.notes" class="form-input" rows="2"></textarea>
                </div>
            </div>
            <template #footer>
                <button class="btn-secondary" @click="showBulkModal = false">Batal</button>
                <button class="btn-primary" @click="submitBulk">Simpan Absensi Massal</button>
            </template>
        </Modal>

        <ConfirmDialog
            :show="showDeleteDialog"
            title="Hapus Absensi"
            message="Apakah Anda yakin ingin menghapus catatan absensi ini?"
            @confirm="deleteAttendance"
            @close="showDeleteDialog = false"
        />
    </AuthenticatedLayout>
</template>
