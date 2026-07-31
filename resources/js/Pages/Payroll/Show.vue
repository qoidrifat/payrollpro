<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Badge from '@/Components/Badge.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { useSupabaseRealtime } from '@/composables/useSupabaseRealtime'
import {
    ArrowLeftIcon, ArrowPathIcon, CheckIcon, DocumentTextIcon,
    EyeIcon, PrinterIcon, ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const payroll = computed(() => page.props.payroll)
const items = computed(() => page.props.payroll?.items || [])
const realtime = useSupabaseRealtime()
const syncing = ref(false)
const syncError = ref('')
let pollTimer = null
let realtimeUnsubscribe = null

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const statusVariant = (status) => {
    const map = { draft: 'default', processed: 'info', approved: 'primary', paid: 'success' }
    return map[status] || 'default'
}

const pph21DeductionName = (item) => {
    const pph21 = Number(item.pph21) || 0
    const details = item.calculation_details || {}
    if (pph21 === 0 && Number(details.ptkp || 0) > 0) {
        return `PPh 21 (di bawah PTKP ${details.ptkp_category || ''})`.trim()
    }
    return 'PPh 21'
}

const processing = ref(false)
const isPayrollProcessing = computed(() => payroll.value?.status === 'processing')

const realtimeLabel = computed(() => {
    if (!realtime.isConfigured) return 'Polling aktif'
    if (realtime.status.value === 'SUBSCRIBED') return 'Realtime aktif'
    if (['CHANNEL_ERROR', 'TIMED_OUT', 'CLOSED'].includes(realtime.status.value)) return 'Realtime terputus, polling aktif'
    return 'Menghubungkan realtime'
})

const refreshPayroll = () => {
    if (syncing.value || !payroll.value?.id) return
    syncing.value = true
    syncError.value = ''
    router.reload({
        only: ['payroll'],
        preserveScroll: true,
        preserveState: true,
        onError: () => { syncError.value = 'Gagal menyinkronkan data payroll.' },
        onFinish: () => { syncing.value = false },
    })
}

const startPolling = () => {
    if (pollTimer) return
    pollTimer = setInterval(() => { if (isPayrollProcessing.value) refreshPayroll() }, 10000)
}

const stopPolling = () => {
    if (!pollTimer) return
    clearInterval(pollTimer)
    pollTimer = null
}

const processPayroll = () => {
    processing.value = true
    router.post(route('payroll.process', payroll.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { refreshPayroll(); startPolling() },
        onFinish: () => { processing.value = false },
    })
}

const approving = ref(false)
const approvePayroll = () => {
    approving.value = true
    router.post(route('payroll.approve', payroll.value.id), {}, {
        onFinish: () => { approving.value = false },
    })
}

const generatingPayslips = ref(false)
const generatePayslips = () => {
    generatingPayslips.value = true
    router.post(route('payroll.generate-payslips', payroll.value.id), {}, {
        onFinish: () => { generatingPayslips.value = false },
    })
}

const itemDeductions = (item) => {
    const d = [
        { name: 'BPJS Kesehatan', amount: Number(item.bpjs_kesehatan_employee) || 0 },
        { name: 'BPJS TK JHT', amount: Number(item.bpjs_tk_jht_employee) || 0 },
        { name: 'BPJS TK JP', amount: Number(item.bpjs_tk_jp_employee) || 0 },
        { name: pph21DeductionName(item), amount: Number(item.pph21) || 0 },
    ]
    if (Number(item.deductions_total) > 0) d.push({ name: 'Potongan Lain', amount: Number(item.deductions_total) })
    return d
}

const itemTotalDeductions = (item) => itemDeductions(item).reduce((sum, d) => sum + d.amount, 0)

const totals = computed(() => {
    if (!items.value.length) return { gross: 0, deductions: 0, net: 0 }
    return items.value.reduce(
        (acc, item) => ({
            gross: acc.gross + (Number(item.gross_salary) || 0),
            deductions: acc.deductions + itemTotalDeductions(item),
            net: acc.net + (Number(item.net_salary) || 0),
        }),
        { gross: 0, deductions: 0, net: 0 }
    )
})

const showDeductionModal = ref(false)
const selectedItemId = ref(null)
const selectedItem = computed(() => items.value.find((item) => item.id === selectedItemId.value) || null)
const selectedDeductions = computed(() => selectedItem.value ? itemDeductions(selectedItem.value) : [])

const viewDeductions = (item) => {
    selectedItemId.value = item.id
    showDeductionModal.value = true
}

watch(isPayrollProcessing, (processing) => {
    if (processing) startPolling()
    else stopPolling()
}, { immediate: true })

onMounted(() => {
    realtimeUnsubscribe = realtime.subscribeToNotifications({
        channelName: `project-kp-payroll-${payroll.value?.id || 'detail'}`,
        topics: ['payroll'],
        onChange: () => refreshPayroll(),
    })
})

onUnmounted(() => {
    stopPolling()
    if (realtimeUnsubscribe) realtimeUnsubscribe()
})
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader :title="payroll?.name || 'Detail Penggajian'" description="Lihat dan kelola detail penggajian">
            <template #actions>
                <Link :href="route('payroll.index')" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali
                </Link>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Header Info -->
            <div v-if="payroll" class="glass-card p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h2 class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white gradient-text">{{ payroll.name }}</h2>
                            <Badge :variant="statusVariant(payroll.status)" size="lg">{{ payroll.status }}</Badge>
                        </div>
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ payroll.period_start }} — {{ payroll.period_end }}
                            </span>
                            <span v-if="payroll.created_at" class="text-gray-400">Dibuat: {{ payroll.created_at }}</span>
                            <span v-if="payroll.processed_at" class="text-gray-400">Diproses: {{ payroll.processed_at }}</span>
                            <span v-if="payroll.approved_at" class="text-gray-400">Disetujui: {{ payroll.approved_at }}</span>
                            <span v-if="syncing" class="text-primary-600 animate-pulse-subtle">Menyinkronkan...</span>
                        </div>
                        <p v-if="syncError" class="text-sm text-red-600 dark:text-red-400">{{ syncError }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button v-if="payroll.status === 'draft'" @click="processPayroll" class="btn-primary" :disabled="processing">
                            <ArrowPathIcon class="w-5 h-5" />
                            {{ processing ? 'Memproses...' : 'Proses' }}
                        </button>
                        <button v-if="payroll.status === 'processed'" @click="approvePayroll" class="btn-success" :disabled="approving">
                            <CheckIcon class="w-5 h-5" />
                            {{ approving ? 'Menyetujui...' : 'Setujui' }}
                        </button>
                        <button v-if="payroll.status === 'approved'" @click="generatePayslips" class="btn-primary" :disabled="generatingPayslips">
                            <DocumentTextIcon class="w-5 h-5" />
                            {{ generatingPayslips ? 'Membuat...' : 'Buat Slip Gaji' }}
                        </button>
                        <a v-if="payroll.status === 'paid' || payroll.status === 'approved'" :href="route('payslips.bulk', payroll.id)" class="btn-primary">
                            <PrinterIcon class="w-5 h-5" /> Cetak Semua PDF
                        </a>
                        <a v-if="payroll.status === 'paid' || payroll.status === 'approved'" :href="route('payslips.export', payroll.id)" class="btn-secondary">
                            <ArrowDownTrayIcon class="w-5 h-5" /> Ekspor Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div v-if="items.length" class="glass-card overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Item Penggajian</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30">
                                <th class="text-left py-3.5 px-4 font-bold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Karyawan</th>
                                <th class="text-right py-3.5 px-4 font-bold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Gaji Kotor</th>
                                <th class="text-right py-3.5 px-4 font-bold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Potongan</th>
                                <th class="text-right py-3.5 px-4 font-bold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Gaji Bersih</th>
                                <th class="text-center py-3.5 px-4 font-bold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in items" :key="item.id"
                                class="border-b border-gray-50 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">{{ item.employee?.full_name || 'N/A' }}</td>
                                <td class="py-3.5 px-4 text-right text-gray-900 dark:text-white">{{ formatCurrency(item.gross_salary) }}</td>
                                <td class="py-3.5 px-4 text-right text-red-600 dark:text-red-400">{{ formatCurrency(itemTotalDeductions(item)) }}</td>
                                <td class="py-3.5 px-4 text-right font-bold text-gray-900 dark:text-white">{{ formatCurrency(item.net_salary) }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button @click="viewDeductions(item)" class="btn-ghost text-xs py-1.5 px-3">
                                            <EyeIcon class="w-4 h-4" /> Rincian
                                        </button>
                                        <a :href="route('payslips.print', item.id)" class="btn-ghost text-xs py-1.5 px-3" title="Cetak Slip Gaji">
                                            <PrinterIcon class="w-4 h-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-900/30 font-bold">
                                <td class="py-3.5 px-4 text-gray-900 dark:text-white">Total</td>
                                <td class="py-3.5 px-4 text-right text-gray-900 dark:text-white">{{ formatCurrency(totals.gross) }}</td>
                                <td class="py-3.5 px-4 text-right text-red-600 dark:text-red-400">{{ formatCurrency(totals.deductions) }}</td>
                                <td class="py-3.5 px-4 text-right text-gray-900 dark:text-white">{{ formatCurrency(totals.net) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <EmptyState v-else title="Belum ada item penggajian" description="Proses penggajian ini untuk menghasilkan item." />

            <!-- Deductions Modal -->
            <Modal :show="showDeductionModal" title="Rincian Potongan" @close="showDeductionModal = false">
                <div class="space-y-3">
                    <table class="w-full text-sm" v-if="selectedDeductions.length">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="text-left py-2.5 px-3 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase">Komponen</th>
                                <th class="text-right py-2.5 px-3 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in selectedDeductions" :key="d.id || d.name" class="border-b border-gray-50 dark:border-gray-800/50">
                                <td class="py-2.5 px-3 text-gray-900 dark:text-white">{{ d.name }}</td>
                                <td class="py-2.5 px-3 text-right font-semibold text-red-600 dark:text-red-400">{{ formatCurrency(d.amount) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold">
                                <td class="py-2.5 px-3 text-gray-900 dark:text-white">Total</td>
                                <td class="py-2.5 px-3 text-right text-red-600 dark:text-red-400">{{ formatCurrency(selectedDeductions.reduce((sum, d) => sum + (Number(d.amount) || 0), 0)) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">Tidak ada potongan.</p>
                </div>
                <template #footer>
                    <button @click="showDeductionModal = false" class="btn-secondary">Tutup</button>
                </template>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
