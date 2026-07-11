<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { useSupabaseRealtime } from '@/composables/useSupabaseRealtime'
import {
    CheckCircleIcon,
    ClockIcon,
    EyeIcon,
    FunnelIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const manualRequests = computed(() => page.props.manualRequests || { data: [] })
const filters = computed(() => page.props.filters || {})
const summary = computed(() => page.props.summary || {})
const selectedStatus = ref(filters.value.status || '')
const selectedRequest = ref(null)
const showDetailModal = ref(false)
const showApproveDialog = ref(false)
const showRejectModal = ref(false)
const rejectionReason = ref('')
const processing = ref(false)
const lastKnownUpdate = ref(manualRequests.value.data?.[0]?.updated_at || null)
const realtime = useSupabaseRealtime()
let realtimeUnsubscribe = null
let pollingTimer = null
let reloadTimer = null

const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'requested_date', label: 'Tanggal' },
    { key: 'request_type_label', label: 'Tipe' },
    { key: 'requested_time', label: 'Jam' },
    { key: 'reason', label: 'Alasan' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', sortable: false },
]

const rows = computed(() => manualRequests.value.data.map((request) => ({
    ...request,
    employee_name: request.employee?.full_name || '-',
})))

const realtimeStatusLabel = computed(() => {
    if (!realtime.isConfigured) return 'Fallback polling aktif'
    if (realtime.status.value === 'SUBSCRIBED') return 'Realtime aktif'
    if (['CHANNEL_ERROR', 'TIMED_OUT', 'CLOSED'].includes(realtime.status.value)) return 'Realtime terputus, polling aktif'
    return 'Menghubungkan realtime'
})

const realtimeStatusClass = computed(() => {
    if (!realtime.isConfigured) return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
    if (realtime.status.value === 'SUBSCRIBED') return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
    if (['CHANNEL_ERROR', 'TIMED_OUT', 'CLOSED'].includes(realtime.status.value)) return 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
    return 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
})

const statusLabel = (status) => ({
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
}[status] || status)

const statusVariant = (status) => ({
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
}[status] || 'default')

const formatDate = (date) => {
    if (!date) return '-'
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date))
}

const truncate = (value, length = 72) => {
    if (!value) return '-'
    return value.length > length ? `${value.slice(0, length)}...` : value
}

const reloadList = () => {
    if (reloadTimer) clearTimeout(reloadTimer)
    reloadTimer = setTimeout(() => {
        router.reload({
            only: ['manualRequests', 'summary'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                lastKnownUpdate.value = page.props.manualRequests?.data?.[0]?.updated_at || lastKnownUpdate.value
            },
        })
    }, 300)
}

const pollForChanges = async () => {
    if (realtime.isConfigured && realtime.status.value === 'SUBSCRIBED') {
        return
    }

    try {
        const { data } = await window.axios.get(route('manual-attendance-requests.poll'), {
            params: { status: selectedStatus.value || null, search: filters.value.search || null },
        })

        if (data.latestUpdatedAt && data.latestUpdatedAt !== lastKnownUpdate.value) {
            lastKnownUpdate.value = data.latestUpdatedAt
            reloadList()
        }
    } catch {
        // Polling is a fallback only; page actions still use normal Inertia requests.
    }
}

const applyStatusFilter = () => {
    router.get('/manual-attendance-requests', {
        ...filters.value,
        status: selectedStatus.value || null,
        page: 1,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

const clearStatusFilter = () => {
    selectedStatus.value = ''
    router.get('/manual-attendance-requests', {
        search: filters.value.search || null,
        page: 1,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

const openDetail = (request) => {
    selectedRequest.value = request
    showDetailModal.value = true
}

const confirmApprove = (request) => {
    selectedRequest.value = request
    showApproveDialog.value = true
}

const openRejectModal = (request) => {
    selectedRequest.value = request
    rejectionReason.value = ''
    showRejectModal.value = true
}

const approveRequest = () => {
    if (!selectedRequest.value) return

    processing.value = true
    router.post(route('manual-attendance-requests.approve', selectedRequest.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showApproveDialog.value = false
            selectedRequest.value = null
            reloadList()
        },
        onFinish: () => {
            processing.value = false
        },
    })
}

const rejectRequest = () => {
    if (!selectedRequest.value) return

    processing.value = true
    router.post(route('manual-attendance-requests.reject', selectedRequest.value.id), {
        rejection_reason: rejectionReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false
            selectedRequest.value = null
            rejectionReason.value = ''
            reloadList()
        },
        onFinish: () => {
            processing.value = false
        },
    })
}

onMounted(() => {
    realtimeUnsubscribe = realtime.subscribeToNotifications({
        channelName: 'project-kp-manual-attendance-admin',
        topics: ['manual_attendance', 'attendance'],
        onChange: reloadList,
    })
    pollingTimer = setInterval(pollForChanges, 60000)
})

onUnmounted(() => {
    if (pollingTimer) clearInterval(pollingTimer)
    if (reloadTimer) clearTimeout(reloadTimer)
    if (realtimeUnsubscribe) realtimeUnsubscribe()
})
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Pengajuan Absen Manual" description="Review kendala absensi manual sebelum menjadi attendance resmi." />

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Menunggu</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ summary.pending ?? 0 }}</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-950">
                            <ClockIcon class="h-5 w-5 text-amber-700 dark:text-amber-300" />
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Disetujui</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ summary.approved ?? 0 }}</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950">
                            <CheckCircleIcon class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ditolak</p>
                            <p class="mt-1 text-2xl font-display font-bold text-gray-900 dark:text-white">{{ summary.rejected ?? 0 }}</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 dark:bg-red-950">
                            <XCircleIcon class="h-5 w-5 text-red-700 dark:text-red-300" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-800">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Pengajuan Absen Manual</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Approve hanya jika bukti dan alasan valid.</p>
                        </div>
                        <span :class="['inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold', realtimeStatusClass]">
                            {{ realtimeStatusLabel }}
                        </span>
                    </div>
                </div>

                <EmptyState
                    v-if="!rows.length"
                    title="Belum ada pengajuan absen manual"
                    description="Pengajuan dari halaman my-qr employee akan tampil di sini otomatis."
                />

                <DataTable
                    v-else
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari karyawan, departemen, atau jabatan..."
                    :server-side="true"
                    :total="manualRequests.total || 0"
                    :current-page="manualRequests.current_page || 1"
                    :last-page="manualRequests.last_page || 1"
                    :per-page="manualRequests.per_page || 15"
                    :filters="filters"
                    base-route="/manual-attendance-requests"
                    @row-click="openDetail"
                >
                    <template #toolbar>
                        <div class="flex items-center gap-2">
                            <FunnelIcon class="h-4 w-4 text-gray-400" />
                            <select
                                v-model="selectedStatus"
                                class="form-input w-auto min-w-[150px] py-1.5 text-sm"
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
                            class="whitespace-nowrap text-xs font-medium text-primary-600 transition-colors hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                            @click="clearStatusFilter"
                        >
                            &times; Hapus
                        </button>
                        <div class="whitespace-nowrap border-l border-gray-200 pl-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Total: <strong>{{ manualRequests.total || 0 }}</strong>
                        </div>
                    </template>

                    <template #cell-employee_name="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900">
                                <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                                    {{ row.employee?.first_name?.charAt(0) || '?' }}
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ row.employee_name }}</p>
                                <p class="text-xs text-gray-400">{{ row.employee?.department || row.employee?.position || '-' }}</p>
                            </div>
                        </div>
                    </template>
                    <template #cell-requested_date="{ value }">
                        {{ formatDate(value) }}
                    </template>
                    <template #cell-reason="{ value }">
                        <span class="block max-w-[260px] truncate">{{ truncate(value) }}</span>
                    </template>
                    <template #cell-status="{ value }">
                        <Badge :variant="statusVariant(value)">{{ statusLabel(value) }}</Badge>
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-2" @click.stop>
                            <button class="btn-secondary px-3 py-1.5 text-xs" @click="openDetail(row)">
                                <EyeIcon class="h-4 w-4" />
                                Detail
                            </button>
                            <button
                                v-if="row.status === 'pending'"
                                class="btn-primary px-3 py-1.5 text-xs"
                                @click="confirmApprove(row)"
                            >
                                Setujui
                            </button>
                            <button
                                v-if="row.status === 'pending'"
                                class="btn-danger px-3 py-1.5 text-xs"
                                @click="openRejectModal(row)"
                            >
                                Tolak
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <Modal :show="showDetailModal" max-width="xl" title="Detail Pengajuan Absen Manual" @close="showDetailModal = false">
            <div v-if="selectedRequest" class="space-y-5">
                <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ selectedRequest.employee_name }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ selectedRequest.employee?.department || selectedRequest.employee?.position || '-' }}
                        </p>
                    </div>
                    <Badge :variant="statusVariant(selectedRequest.status)">{{ statusLabel(selectedRequest.status) }}</Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-gray-400">Tanggal</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(selectedRequest.requested_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Tipe</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest.request_type_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Jam Diajukan</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest.requested_time }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-400">Alasan</p>
                    <p class="mt-2 whitespace-pre-line rounded-xl bg-white p-4 text-sm leading-6 text-gray-700 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-800">
                        {{ selectedRequest.reason }}
                    </p>
                </div>

                <div v-if="selectedRequest.rejection_reason">
                    <p class="text-xs text-gray-400">Alasan Penolakan</p>
                    <p class="mt-2 whitespace-pre-line rounded-xl bg-red-50 p-4 text-sm leading-6 text-red-700 ring-1 ring-red-100 dark:bg-red-950/30 dark:text-red-200 dark:ring-red-900">
                        {{ selectedRequest.rejection_reason }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button class="btn-secondary" @click="showDetailModal = false">Tutup</button>
                    <button
                        v-if="selectedRequest.status === 'pending'"
                        class="btn-primary"
                        @click="showDetailModal = false; confirmApprove(selectedRequest)"
                    >
                        Setujui
                    </button>
                    <button
                        v-if="selectedRequest.status === 'pending'"
                        class="btn-danger"
                        @click="showDetailModal = false; openRejectModal(selectedRequest)"
                    >
                        Tolak
                    </button>
                </div>
            </div>
        </Modal>

        <ConfirmDialog
            :show="showApproveDialog"
            title="Setujui Pengajuan Absen Manual"
            :message="`Setujui ${selectedRequest?.request_type_label || 'pengajuan'} milik ${selectedRequest?.employee_name || 'karyawan'}? Attendance resmi akan dibuat atau diperbarui.`"
            confirm-text="Setujui"
            confirm-variant="primary"
            :loading="processing"
            @confirm="approveRequest"
            @close="showApproveDialog = false"
        />

        <Modal :show="showRejectModal" title="Tolak Pengajuan Absen Manual" @close="showRejectModal = false">
            <div class="space-y-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest?.employee_name }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ selectedRequest?.request_type_label }} - {{ formatDate(selectedRequest?.requested_date) }} {{ selectedRequest?.requested_time }}
                    </p>
                </div>
                <div>
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea
                        v-model="rejectionReason"
                        rows="4"
                        class="form-input"
                        placeholder="Tuliskan alasan yang jelas agar employee memahami keputusan ini."
                    />
                    <p v-if="page.props.errors?.rejection_reason" class="mt-1 text-xs text-red-600">
                        {{ page.props.errors.rejection_reason }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button class="btn-secondary" :disabled="processing" @click="showRejectModal = false">Batal</button>
                <button class="btn-danger" :disabled="processing || rejectionReason.trim().length < 5" @click="rejectRequest">
                    {{ processing ? 'Memproses...' : 'Tolak Pengajuan' }}
                </button>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>
