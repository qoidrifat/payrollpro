<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import Badge from '@/Components/Badge.vue'
import { DocumentTextIcon, ShieldCheckIcon, UsersIcon, ScaleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const taxSummary = computed(() => page.props.taxSummary || {})
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const maskNpwp = (npwp) => {
    if (!npwp) return '\u2014'
    return npwp.substring(0, 2) + 'XXXXXXXX' + npwp.substring(npwp.length - 3)
}
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-amber-500 to-orange-600" />
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                            <ScaleIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white gradient-text-amber">Informasi Pajak</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ringkasan PPh21 tahun {{ new Date().getFullYear() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="glass-card p-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5">Profil Pajak</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-5 rounded-xl bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2 mb-2">
                            <UsersIcon class="w-4 h-4 text-gray-400" />
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</span>
                        </div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ taxSummary.marital_status || '\u2014' }}</p>
                        <p class="text-xs text-gray-400">{{ taxSummary.dependents_count }} tanggungan</p>
                    </div>
                    <div class="p-5 rounded-xl bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2 mb-2">
                            <ShieldCheckIcon class="w-4 h-4 text-gray-400" />
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">NPWP</span>
                        </div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ maskNpwp(taxSummary.npwp) }}</p>
                    </div>
                    <div class="p-5 rounded-xl bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2 mb-2">
                            <ScaleIcon class="w-4 h-4 text-gray-400" />
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Bracket Pajak</span>
                        </div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ taxSummary.tax_bracket || '\u2014' }}</p>
                    </div>
                </div>
            </div>

            <!-- Annual Summary -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500" />
                <div class="p-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5">Ringkasan Tahunan</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Penghasilan Bruto</p>
                                <p class="text-xs text-gray-400">Total penghasilan tahun {{ new Date().getFullYear() }}</p>
                            </div>
                            <p class="text-base font-bold text-gray-900 dark:text-white">{{ formatCurrency(taxSummary.yearly_gross) }}</p>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Total Iuran BPJS</p>
                                <p class="text-xs text-gray-400">BPJS Kesehatan + Ketenagakerjaan (karyawan)</p>
                            </div>
                            <p class="text-base font-bold text-gray-900 dark:text-white">&minus;{{ formatCurrency(taxSummary.yearly_bpjs) }}</p>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-950/40 dark:to-rose-950/40 border border-red-100 dark:border-red-900/30">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">PPh21 Terutang</p>
                                <p class="text-xs text-red-400">Pajak penghasilan tahun {{ new Date().getFullYear() }}</p>
                            </div>
                            <p class="text-base font-bold text-red-700 dark:text-red-300">{{ formatCurrency(taxSummary.yearly_pph21) }}</p>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-950/40 dark:to-green-950/40 border border-emerald-100 dark:border-emerald-900/30">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Penghasilan Bersih</p>
                                <p class="text-xs text-emerald-400">Setelah pajak dan iuran</p>
                            </div>
                            <p class="text-base font-bold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(taxSummary.yearly_gross - taxSummary.yearly_bpjs - taxSummary.yearly_pph21) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Note -->
            <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950/40 dark:to-cyan-950/40 border border-blue-200 dark:border-blue-900/30">
                <div class="flex items-start gap-3">
                    <InformationCircleIcon class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-700 dark:text-blue-300 leading-relaxed">
                        <strong>Catatan:</strong> Ringkasan ini bersifat informatif berdasarkan data penggajian yang telah diproses. Untuk perhitungan detail dan konsultasi pajak lebih lanjut, silakan hubungi tim Finance/HR.
                    </p>
                </div>
            </div>
        </div>
    </component>
</template>
