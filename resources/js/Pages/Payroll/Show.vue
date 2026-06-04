<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Badge from '@/Components/Badge.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import EmptyState from '@/Components/EmptyState.vue'
import {
    ArrowLeftIcon,
    ArrowPathIcon,
    CheckIcon,
    DocumentTextIcon,
    EyeIcon,
    PrinterIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const payroll = computed(() => page.props.payroll)
const items = computed(() => page.props.payroll?.items || [])

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const statusVariant = (status) => {
    const map = {
        draft: 'default',
        processed: 'info',
        approved: 'primary',
        paid: 'success',
    }
    return map[status] || 'default'
}

const processing = ref(false)

const processPayroll = () => {
    processing.value = true
    router.post(
        route('payroll.process', payroll.value.id),
        {},
        {
            onFinish: () => {
                processing.value = false
            },
        }
    )
}

const approving = ref(false)

const approvePayroll = () => {
    approving.value = true
    router.post(
        route('payroll.approve', payroll.value.id),
        {},
        {
            onFinish: () => {
                approving.value = false
            },
        }
    )
}

const generatingPayslips = ref(false)

const generatePayslips = () => {
    generatingPayslips.value = true
    router.post(
        route('payroll.generate-payslips', payroll.value.id),
        {},
        {
            onFinish: () => {
                generatingPayslips.value = false
            },
        }
    )
}

const itemDeductions = (item) => {
    const d = [
        { name: 'BPJS Kesehatan', amount: Number(item.bpjs_kesehatan_employee) || 0 },
        { name: 'BPJS TK JHT', amount: Number(item.bpjs_tk_jht_employee) || 0 },
        { name: 'BPJS TK JP', amount: Number(item.bpjs_tk_jp_employee) || 0 },
        { name: 'PPh 21', amount: Number(item.pph21) || 0 },
    ]
    if (Number(item.deductions_total) > 0) {
        d.push({ name: 'Potongan Lain', amount: Number(item.deductions_total) })
    }
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
const selectedDeductions = ref([])

const viewDeductions = (item) => {
    selectedDeductions.value = itemDeductions(item)
    showDeductionModal.value = true
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader :title="payroll?.name || 'Detail Penggajian'" description="Lihat dan kelola detail penggajian">
            <template #actions>
                <Link :href="route('payroll.index')" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali ke Penggajian
                </Link>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Payroll Info Header -->
            <div class="glass-card p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ payroll.name }}</h2>
                            <Badge :variant="statusVariant(payroll.status)">
                                {{ payroll.status }}
                            </Badge>
                        </div>
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                            <span>Periode: {{ payroll.period_start }} — {{ payroll.period_end }}</span>
                            <span v-if="payroll.created_at">Dibuat: {{ payroll.created_at }}</span>
                            <span v-if="payroll.processed_at">Diproses: {{ payroll.processed_at }}</span>
                            <span v-if="payroll.approved_at">Disetujui: {{ payroll.approved_at }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            v-if="payroll.status === 'draft'"
                            @click="processPayroll"
                            class="btn-primary"
                            :disabled="processing"
                        >
                            <ArrowPathIcon class="w-5 h-5" />
                            {{ processing ? 'Memproses...' : 'Proses' }}
                        </button>
                        <button
                            v-if="payroll.status === 'processed'"
                            @click="approvePayroll"
                            class="btn-primary"
                            :disabled="approving"
                        >
                            <CheckIcon class="w-5 h-5" />
                            {{ approving ? 'Menyetujui...' : 'Setujui' }}
                        </button>
                        <button
                            v-if="payroll.status === 'approved'"
                            @click="generatePayslips"
                            class="btn-primary"
                            :disabled="generatingPayslips"
                        >
                            <DocumentTextIcon class="w-5 h-5" />
                            {{ generatingPayslips ? 'Membuat...' : 'Buat Slip Gaji' }}
                        </button>
                        <a
                            v-if="payroll.status === 'paid' || payroll.status === 'approved'"
                            :href="route('payslips.bulk', payroll.id)"
                            class="btn-primary"
                        >
                            <PrinterIcon class="w-5 h-5" />
                            Cetak Semua PDF
                        </a>
                        <a
                            v-if="payroll.status === 'paid' || payroll.status === 'approved'"
                            :href="route('payslips.export', payroll.id)"
                            class="btn-secondary"
                        >
                            <ArrowDownTrayIcon class="w-5 h-5" />
                            Ekspor Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payroll Items Table -->
            <div class="table-container" v-if="items.length">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Item Penggajian</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Karyawan</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Gaji Kotor</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Potongan</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Gaji Bersih</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in items"
                                :key="item.id"
                                class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/30"
                            >
                                <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">
                                    {{ item.employee?.full_name || 'N/A' }}
                                </td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.gross_salary) }}
                                </td>
                                <td class="py-3 px-4 text-right text-red-600 dark:text-red-400">
                                    {{ formatCurrency(itemTotalDeductions(item)) }}
                                </td>
                                <td class="py-3 px-4 text-right font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.net_salary) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            @click="viewDeductions(item)"
                                            class="btn-secondary text-xs py-1.5 px-3"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                            Rincian
                                        </button>
                                        <a
                                            :href="route('payslips.print', item.id)"
                                            class="btn-secondary text-xs py-1.5 px-3"
                                            title="Cetak Slip Gaji"
                                        >
                                            <PrinterIcon class="w-4 h-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <!-- Totals Row -->
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 font-semibold">
                                <td class="py-3 px-4 text-gray-900 dark:text-white">Total</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                    {{ formatCurrency(totals.gross) }}
                                </td>
                                <td class="py-3 px-4 text-right text-red-600 dark:text-red-400">
                                    {{ formatCurrency(totals.deductions) }}
                                </td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                    {{ formatCurrency(totals.net) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <EmptyState
                v-else
                title="Belum ada item penggajian"
                description="Proses penggajian ini untuk menghasilkan item untuk setiap karyawan."
            />

            <!-- Deductions Breakdown Modal -->
            <Modal
                :show="showDeductionModal"
                title="Rincian Potongan"
                @close="showDeductionModal = false"
            >
                <div class="space-y-3">
                    <table class="w-full text-sm" v-if="selectedDeductions.length">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Komponen</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="d in selectedDeductions"
                                :key="d.id || d.name"
                                class="border-b border-gray-100 dark:border-gray-800"
                            >
                                <td class="py-2 px-2 text-gray-900 dark:text-white">{{ d.name }}</td>
                                <td class="py-2 px-2 text-right text-red-600 dark:text-red-400">
                                    {{ formatCurrency(d.amount) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td class="py-2 px-2 text-gray-900 dark:text-white">Total</td>
                                <td class="py-2 px-2 text-right text-red-600 dark:text-red-400">
                                    {{ formatCurrency(selectedDeductions.reduce((sum, d) => sum + (Number(d.amount) || 0), 0)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        Tidak ada potongan untuk karyawan ini.
                    </p>
                </div>
                <template #footer>
                    <button @click="showDeductionModal = false" class="btn-secondary">Tutup</button>
                </template>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
