<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import {
    CalendarDaysIcon,
    CheckCircleIcon,
    XCircleIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const leaveRequests = computed(() => page.props.leaveRequests || { data: [] })
const filters = computed(() => page.props.filters || {})
const summary = computed(() => page.props.summary || {})

const selectedStatus = ref(filters.value.status || '')
const showApproveDialog = ref(false)
const showRejectModal = ref(false)
const selectedLeave = ref(null)
const rejectionReason = ref('')
const processing = ref(false)

const columns = [
    { key: 'employee_name', label: 'Karyawan' },
    { key: 'leave_type_label', label: 'Jenis Cuti' },
    { key: 'period', label: 'Periode' },
    { key: 'total_days', label: 'Durasi' },
    { key: 'status', label: 'Status' },
    { key: 'approved_by_name', label: 'Diproses Oleh' },
    { key: 'actions', label: '', sortable: false },
]

const rows = computed(() => leaveRequests.value.data.map((leave) => ({
    ...leave,
    employee_name: leave.employee?.full_name || `${leave.employee?.first_name || ''} ${leave.employee?.last_name || ''}`.trim(),
    leave_type_label: leaveTypeLabel(leave.leave_type),
    period: `${formatDate(leave.start_date)} - ${formatDate(leave.end_date)}`,
    approved_by_name: leave.approved_by?.name || '-',
})))

const leaveTypeLabel = (type) => {
    const map = {
        annual: 'Cuti Tahunan',
        sick: 'Cuti Sakit',
        personal: 'Cuti Pribadi',
        maternity: 'Cuti Melahirkan',
        paternity: 'Cuti Ayah',
        marriage: 'Cuti Menikah',
        bereavement: 'Cuti Duka',
        unpaid: 'Cuti Tanpa Dibayar',
    }
    return map[type] || type
}

const statusLabel = (status) => {
    const map = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' }
    return map[status] || status
}

const statusVariant = (status) => {
    const map = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'default' }
    return map[status] || 'default'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date))
}

const applyStatusFilter = () => {
    router.get('/leave-requests', {
        ...filters.value,
        status: selectedStatus.value || null,
        page: 1,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

const clearStatusFilter = () => {
    selectedStatus.value = ''
    router.get('/leave-requests', {
        search: filters.value.search || null,
        page: 1,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

const confirmApprove = (leave) => {
    selectedLeave.value = leave
    showApproveDialog.value = true
}

const openRejectModal = (leave) => {
    selectedLeave.value = leave
    rejectionReason.value = ''
    showRejectModal.value = true
}

const approveLeave = () => {
    if (!selectedLeave.value) return
    processing.value = true
    router.post(route('leave-requests.approve', selectedLeave.value.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false
            showApproveDialog.value = false
            selectedLeave.value = null
        },
    })
}

const rejectLeave = () => {
    if (!selectedLeave.value) return
    processing.value = true
    router.post(route('leave-requests.reject', selectedLeave.value.id), {
        rejection_reason: rejectionReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false
            selectedLeave.value = null
            rejectionReason.value = ''
        },
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Pengajuan Cuti" description="Review, setujui, atau tolak permintaan cuti karyawan secara terpusat." />

        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Menunggu</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ summary.pending ?? 0 }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-950 flex items-center justify-center">
                            <CalendarDaysIcon class="w-5 h-5 text-amber-700 dark:text-amber-300" />
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Disetujui</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ summary.approved ?? 0 }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-emerald-700 dark:text-emerald-300" />
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ditolak</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ summary.rejected ?? 0 }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-950 flex items-center justify-center">
                            <XCircleIcon class="w-5 h-5 text-red-700 dark:text-red-300" />
                        </div>
                    </div>
                </div>
            </div>

            <DataTable
                :columns="columns"
                :rows="rows"
                search-placeholder="Cari karyawan, departemen, atau jabatan..."
                :server-side="true"
                :total="leaveRequests.total || 0"
                :current-page="leaveRequests.current_page || 1"
                :last-page="leaveRequests.last_page || 1"
                :per-page="leaveRequests.per_page || 15"
                :filters="filters"
                base-route="/leave-requests"
            >
                <template #toolbar>
                    <div class="flex items-center gap-2">
                        <FunnelIcon class="w-4 h-4 text-gray-400" />
                        <select
                            v-model="selectedStatus"
                            class="form-input w-auto min-w-[150px] text-sm py-1.5"
                            @change="applyStatusFilter"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                    <button
                        v-if="selectedStatus"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium whitespace-nowrap transition-colors"
                        @click="clearStatusFilter"
                    >
                        &times; Hapus
                    </button>
                    <div class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap border-l border-gray-200 dark:border-gray-700 pl-3">
                        Total: <strong>{{ leaveRequests.total || 0 }}</strong>
                    </div>
                </template>

                <template #cell-employee_name="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                                {{ row.employee?.first_name?.charAt(0) }}{{ row.employee?.last_name?.charAt(0) || '' }}
                            </span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ row.employee_name }}</p>
                            <p class="text-xs text-gray-400">{{ row.employee?.department || row.employee?.position || '-' }}</p>
                        </div>
                    </div>
                </template>
                <template #cell-total_days="{ value }">
                    {{ value }} hari
                </template>
                <template #cell-status="{ value }">
                    <Badge :variant="statusVariant(value)">{{ statusLabel(value) }}</Badge>
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex items-center gap-2" @click.stop>
                        <button
                            v-if="row.status === 'pending'"
                            class="btn-primary text-xs py-1.5 px-3"
                            @click="confirmApprove(row)"
                        >
                            Setujui
                        </button>
                        <button
                            v-if="row.status === 'pending'"
                            class="btn-danger text-xs py-1.5 px-3"
                            @click="openRejectModal(row)"
                        >
                            Tolak
                        </button>
                        <span v-else class="text-xs text-gray-400">Selesai</span>
                    </div>
                </template>
            </DataTable>
        </div>

        <ConfirmDialog
            :show="showApproveDialog"
            title="Setujui Pengajuan Cuti"
            :message="`Setujui cuti ${selectedLeave?.employee_name || 'karyawan'} untuk periode ${selectedLeave?.period || ''}?`"
            confirm-text="Setujui"
            confirm-variant="primary"
            :loading="processing"
            @confirm="approveLeave"
            @close="showApproveDialog = false"
        />

        <Modal :show="showRejectModal" title="Tolak Pengajuan Cuti" @close="showRejectModal = false">
            <div class="space-y-4">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 p-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedLeave?.employee_name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedLeave?.period }} · {{ selectedLeave?.total_days }} hari</p>
                </div>
                <div>
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea
                        v-model="rejectionReason"
                        rows="4"
                        class="form-input"
                        placeholder="Tuliskan alasan agar karyawan memahami keputusan ini..."
                    />
                    <p v-if="page.props.errors?.rejection_reason" class="mt-1 text-xs text-red-600">
                        {{ page.props.errors.rejection_reason }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button class="btn-secondary" :disabled="processing" @click="showRejectModal = false">Batal</button>
                <button class="btn-danger" :disabled="processing || !rejectionReason.trim()" @click="rejectLeave">
                    {{ processing ? 'Memproses...' : 'Tolak Pengajuan' }}
                </button>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>
