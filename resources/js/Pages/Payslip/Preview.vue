<script setup>
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { ArrowDownTrayIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const payslip = computed(() => page.props.payslip || {})
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const formatNumber = (value) =>
    new Intl.NumberFormat('id-ID').format(value ?? 0)

const printPayslip = () => {
    window.print()
}
</script>

<template>
    <component :is="Layout">
        <!-- ── Title + Actions ── -->
        <div class="max-w-4xl mx-auto mb-8">
            <!-- Back link -->
            <Link :href="route('portal.payroll')" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors mb-4">
                <ArrowLeftIcon class="w-4 h-4" />
                Kembali ke Riwayat Penggajian
            </Link>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Slip Gaji</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ payslip.employee_name }} · {{ payslip.period }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        :href="`/payslips/print/${payslip.item_id}`"
                        class="btn-primary text-sm"
                    >
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        <span class="hidden sm:inline">Download PDF</span>
                        <span class="sm:hidden">PDF</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Payslip Preview ── -->
        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-800 overflow-hidden print:shadow-none print:border-none">
            <!-- Gradient Header -->
            <div class="bg-gradient-to-r from-slate-800 via-blue-700 to-purple-700 text-white px-8 py-7 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/3" />
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                <div class="flex items-start justify-between relative z-10">
                    <div class="flex items-center gap-4">
                        <!-- <div class="w-16 h-16 rounded-xl bg-white shadow-lg flex items-center justify-center flex-shrink-0 p-1.5"> -->
                            <img v-if="payslip.company_logo" :src="payslip.company_logo" alt="Logo" class="w-20 h-20 object-contain" />
                            <span v-else class="text-lg font-bold text-gray-400">Logo</span>
                        <!-- </div> -->
                        <div>
                            <h2 class="text-lg font-bold tracking-tight">{{ payslip.company_name }}</h2>
                            <p class="text-xs text-blue-200/80 mt-0.5">{{ payslip.company_address }}</p>
                            <p class="text-xs text-blue-200/60 mt-0.5">Telp: {{ payslip.company_phone }} · Email: {{ payslip.company_email }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-4 py-1.5 rounded-lg bg-white/15 border border-white/20 text-xs font-semibold uppercase tracking-wider">Slip Gaji</span>
                        <p class="text-xs text-blue-200/60 mt-2">No. {{ payslip.number }}</p>
                        <p class="text-xs text-blue-200/60">Status: {{ payslip.status?.toUpperCase() }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-7">
                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-100 dark:border-gray-800">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-500 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Data Karyawan</h4>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Nama</span><span class="font-semibold text-gray-900 dark:text-white">{{ payslip.employee_name }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">NIK</span><span class="font-medium">{{ payslip.nik }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">NPWP</span><span class="font-medium">{{ payslip.npwp || '-' }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Posisi</span><span class="font-medium">{{ payslip.position }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Departemen</span><span class="font-medium">{{ payslip.department || '-' }}</span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-500 mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Periode & Pembayaran</h4>
                        <div class="space-y-1.5">
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Periode</span><span class="font-semibold text-gray-900 dark:text-white">{{ payslip.period }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Tgl Cetak</span><span class="font-medium">{{ payslip.print_date }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Bank</span><span class="font-medium">{{ payslip.bank_name || '-' }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">No. Rekening</span><span class="font-medium">{{ payslip.bank_account || '-' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Income Table -->
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-1 h-4 rounded-full bg-gradient-to-b from-emerald-500 to-emerald-400" />
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Pendapatan</h3>
                    </div>
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr><td class="px-4 py-2.5">Gaji Pokok</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.base_salary) }}</td></tr>
                            <tr v-if="payslip.allowances_total > 0"><td class="px-4 py-2.5">Tunjangan</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.allowances_total) }}</td></tr>
                            <tr v-if="payslip.bonuses_total > 0"><td class="px-4 py-2.5">Bonus</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bonuses_total) }}</td></tr>
                            <tr v-if="payslip.overtime_pay > 0"><td class="px-4 py-2.5">Uang Lembur</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.overtime_pay) }}</td></tr>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/50 font-bold"><td class="px-4 py-2.5 text-gray-900 dark:text-white">Total Pendapatan Bruto</td><td class="px-4 py-2.5 text-right text-blue-600 dark:text-blue-400">{{ formatNumber(payslip.gross_salary) }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Deduction Table -->
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-1 h-4 rounded-full bg-gradient-to-b from-red-500 to-rose-400" />
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Potongan</h3>
                    </div>
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr><td class="px-4 py-2.5">BPJS Kesehatan (1%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_kesehatan_employee) }}</td></tr>
                            <tr><td class="px-4 py-2.5">BPJS TK — JHT (2%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_tk_jht_employee) }}</td></tr>
                            <tr><td class="px-4 py-2.5">BPJS TK — JP (1%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_tk_jp_employee) }}</td></tr>
                            <tr><td class="px-4 py-2.5">PPh 21 Pajak Penghasilan</td><td class="px-4 py-2.5 text-right font-medium text-red-600">{{ formatNumber(payslip.pph21) }}</td></tr>
                            <tr v-if="payslip.deductions_total > 0"><td class="px-4 py-2.5">Potongan Lain-lain</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.deductions_total) }}</td></tr>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/50 font-bold"><td class="px-4 py-2.5 text-gray-900 dark:text-white">Total Potongan</td><td class="px-4 py-2.5 text-right text-red-600 dark:text-red-400">{{ formatNumber(payslip.total_deductions) }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Company Contributions -->
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <span class="w-1 h-4 rounded-full bg-gradient-to-b from-indigo-500 to-purple-400" />
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Iuran Perusahaan</h3>
                    </div>
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr><td class="px-4 py-2.5">BPJS Kesehatan — Iuran Pemberi Kerja (4%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_kesehatan_company) }}</td></tr>
                            <tr><td class="px-4 py-2.5">BPJS TK — JHT Pemberi Kerja (3.7%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_tk_jht_company) }}</td></tr>
                            <tr><td class="px-4 py-2.5">BPJS TK — JP Pemberi Kerja (2%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_tk_jp_company) }}</td></tr>
                            <tr><td class="px-4 py-2.5">BPJS TK — JKK (0.24%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_tk_jkk) }}</td></tr>
                            <tr><td class="px-4 py-2.5">BPJS TK — JKM (0.3%)</td><td class="px-4 py-2.5 text-right font-medium">{{ formatNumber(payslip.bpjs_tk_jkm) }}</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Net Salary -->
                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-950/50 dark:to-teal-950/50 border-2 border-emerald-500 rounded-xl p-5 flex items-center justify-between">
                    <span class="text-base font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Take Home Pay</span>
                    <span class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">Rp {{ formatNumber(payslip.net_salary) }}</span>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-10">Bangkalan, {{ payslip.print_date }}</p>
                </div>

                <!-- Signature -->
                <div class="grid grid-cols-2 gap-8 rounded-xl border border-slate-200 bg-slate-50/90 p-6 dark:border-slate-300 dark:bg-slate-100/95">
                    <div class="text-center pt-8">
                        <div class="w-72 h-40 mx-auto -mb-10 overflow-hidden rounded-md ">
                            <img src="/ttd-direktur.jpg" alt="Tanda tangan direktur" class="w-72 h-auto object-contain">
                        </div>
                        <div class="w-2/3 mx-auto border-b border-slate-500 dark:border-slate-500 mb-1.5" />
                        <p class="text-sm font-bold text-slate-950 dark:text-slate-950">{{ payslip.company_director }}</p>
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-700">Direktur {{ payslip.company_name }}</p>
                    </div>
                    <div class="text-center pt-36">
                        <div class="w-2/3 mx-auto border-b border-slate-500 dark:border-slate-500 mb-1.5" />
                        <p class="text-sm font-bold text-slate-950 dark:text-slate-950">{{ payslip.employee_name }}</p>
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-700">Karyawan</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-3 text-center text-xs text-gray-400 dark:text-gray-600 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                Dokumen ini diterbitkan secara elektronik oleh {{ payslip.company_name }} dan sah tanpa tanda tangan basah.<br>
                Dicetak: {{ payslip.print_date }} {{ payslip.print_time }} WIB
            </div>
            <div class="px-8 py-3 text-center text-xs leading-none text-gray-400 dark:text-gray-600 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                <span class="block mb-0">Sistem dikembangkan oleh:</span>
                <img src="/logoo.png" alt="Logo" class="block w-28 h-auto mx-auto mt-2 object-contain">
            </div>
        </div>
    </component>
</template>

<style>
@media print {
    header, .btn-primary, [type="button"], button, a[href] {
        display: none !important;
    }
    body {
        background: white !important;
    }
    .max-w-4xl {
        max-width: 100% !important;
    }
    .rounded-2xl {
        border-radius: 0 !important;
    }
    .shadow-lg {
        box-shadow: none !important;
    }
}
</style>
