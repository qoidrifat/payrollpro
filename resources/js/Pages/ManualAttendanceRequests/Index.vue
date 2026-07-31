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
    DocumentTextIcon,
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
    router.get(route('manual-attendance-requests.index'), {
        ...filters.value,
        status: selectedStatus.value || null,
        page: 1,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

const clearStatusFilter = () => {
    selectedStatus.value = ''
    router.get(route('manual-attendance-requests.index'), {
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

        <div class="space-y-6 animate-fade-in">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white shadow-lg shadow-amber-500/20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-white/80">Menunggu</p>
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                                <ClockIcon class="w-5 h-5" />
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-display font-bold tracking-tight">{{ summary.pending ?? 0 }}</p>
                        <p class="mt-1 text-xs text-white/60">Perlu direview segera</p>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-lg shadow-emerald-500/20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-white/80">Disetujui</p>
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                                <CheckCircleIcon class="w-5 h-5" />
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-display font-bold tracking-tight">{{ summary.approved ?? 0 }}</p>
                        <p class="mt-1 text-xs text-white/60">Telah diproses</p>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 p-6 text-white shadow-lg shadow-red-500/20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-white/80">Ditolak</p>
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                                <XCircleIcon class="w-5 h-5" />
                            </div>
                        </div>
                        <p class="mt-2 text-3xl font-display font-bold tracking-tight">{{ summary.rejected ?? 0 }}</p>
                        <p class="mt-1 text-xs text-white/60">Tidak lolos verifikasi</p>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-sm">
                            <DocumentTextIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Pengajuan Absen Manual</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Approve hanya jika bukti dan alasan valid.</p>
                        </div>
                    </div>
                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold', realtimeStatusClass]">
                        <span class="w-1.5 h-1.5 rounded-full bg-current" />
                        {{ realtimeStatusLabel }}
                    </span>
                </div>

                <div class="p-6">
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
                                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all w-auto min-w-[150px]"
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
                                class="whitespace-nowrap text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
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
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-sm">
                                    <span class="text-xs font-semibold">
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
                                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-md active:scale-[0.98] transition-all duration-200" @click="openDetail(row)">
                                    <EyeIcon class="h-4 w-4" />
                                    Detail
                                </button>
                                <button
                                    v-if="row.status === 'pending'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200"
                                    @click="confirmApprove(row)"
                                >
                                    Setujui
                                </button>
                                <button
                                    v-if="row.status === 'pending'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500 text-white text-xs font-semibold shadow-md hover:bg-red-600 hover:shadow-lg active:scale-[0.98] transition-all duration-200"
                                    @click="openRejectModal(row)"
                                >
                                    Tolak
                                </button>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <Modal :show="showDetailModal" max-width="xl" title="Detail Pengajuan Absen Manual" @close="showDetailModal = false">
            <div v-if="selectedRequest" class="space-y-5">
                <div class="flex flex-col gap-3 rounded-xl bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-800/50 p-4 sm:flex-row sm:items-center sm:justify-between border border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest.employee_name }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ selectedRequest.employee?.department || selectedRequest.employee?.position || '-' }}
                        </p>
                    </div>
                    <Badge :variant="statusVariant(selectedRequest.status)">{{ statusLabel(selectedRequest.status) }}</Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3.5">
                        <p class="text-xs text-gray-400 font-medium">Tanggal</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(selectedRequest.requested_date) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3.5">
                        <p class="text-xs text-gray-400 font-medium">Tipe</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest.request_type_label }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3.5">
                        <p class="text-xs text-gray-400 font-medium">Jam Diajukan</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest.requested_time }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-gray-400 font-medium mb-2">Alasan</p>
                    <p class="whitespace-pre-line rounded-xl bg-white dark:bg-gray-800 p-4 text-sm leading-6 text-gray-700 dark:text-gray-200 ring-1 ring-gray-200 dark:ring-gray-700">
                        {{ selectedRequest.reason }}
                    </p>
                </div>

                <div v-if="selectedRequest.rejection_reason">
                    <p class="text-xs text-gray-400 font-medium mb-2">Alasan Penolakan</p>
                    <p class="whitespace-pre-line rounded-xl bg-red-50 dark:bg-red-950/30 p-4 text-sm leading-6 text-red-700 dark:text-red-200 ring-1 ring-red-100 dark:ring-red-900">
                        {{ selectedRequest.rejection_reason }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button class="px-4 py-2 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all" @click="showDetailModal = false">Tutup</button>
                    <button
                        v-if="selectedRequest.status === 'pending'"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-sm font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200"
                        @click="showDetailModal = false; confirmApprove(selectedRequest)"
                    >
                        Setujui
                    </button>
                    <button
                        v-if="selectedRequest.status === 'pending'"
                        class="px-4 py-2 rounded-xl bg-red-500 text-white text-sm font-semibold shadow-md hover:bg-red-600 hover:shadow-lg active:scale-[0.98] transition-all duration-200"
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
            <div class="space-y-5">
                <div class="rounded-xl bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-800/50 p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ selectedRequest?.employee_name }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ selectedRequest?.request_type_label }} - {{ formatDate(selectedRequest?.requested_date) }} {{ selectedRequest?.requested_time }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Alasan Penolakan</label>
                    <textarea
                        v-model="rejectionReason"
                        rows="4"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all"
                        placeholder="Tuliskan alasan yang jelas agar employee memahami keputusan ini."
                    />
                    <p v-if="page.props.errors?.rejection_reason" class="mt-1 text-xs text-red-600">
                        {{ page.props.errors.rejection_reason }}
                    </p>
                </div>
            </div>
            <template #footer>
                <button class="px-5 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all" :disabled="processing" @click="showRejectModal = false">Batal</button>
                <button class="px-5 py-2.5 rounded-xl bg-red-500 text-white text-sm font-semibold shadow-md hover:bg-red-600 hover:shadow-lg active:scale-[0.98] transition-all duration-200 disabled:opacity-60" :disabled="processing || rejectionReason.trim().length < 5" @click="rejectRequest">
                    {{ processing ? 'Memproses...' : 'Tolak Pengajuan' }}
                </button>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>
