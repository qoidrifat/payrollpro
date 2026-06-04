<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import Badge from '@/Components/Badge.vue'
import {
    DocumentTextIcon,
    ShieldCheckIcon,
    UsersIcon,
    ScaleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const taxSummary = computed(() => page.props.taxSummary || {})
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const maskNpwp = (npwp) => {
    if (!npwp) return '—'
    return npwp.substring(0, 2) + 'XXXXXXXX' + npwp.substring(npwp.length - 3)
}
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                        <ScaleIcon class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Informasi Pajak</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan PPh21 tahun {{ new Date().getFullYear() }}</p>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="glass-card p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Profil Pajak</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-2 mb-2">
                            <UsersIcon class="w-4 h-4 text-gray-400" />
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Status</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ taxSummary.marital_status || '—' }}</p>
                        <p class="text-xs text-gray-400">{{ taxSummary.dependents_count }} tanggungan</p>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-2 mb-2">
                            <ShieldCheckIcon class="w-4 h-4 text-gray-400" />
                            <span class="text-xs text-gray-400 uppercase tracking-wider">NPWP</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ maskNpwp(taxSummary.npwp) }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-2 mb-2">
                            <ScaleIcon class="w-4 h-4 text-gray-400" />
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Bracket Pajak</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ taxSummary.tax_bracket || '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Annual Summary -->
            <div class="glass-card p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Ringkasan Tahunan</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Penghasilan Bruto</p>
                            <p class="text-xs text-gray-400">Total penghasilan tahun {{ new Date().getFullYear() }}</p>
                        </div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">{{ formatCurrency(taxSummary.yearly_gross) }}</p>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Total Iuran BPJS</p>
                            <p class="text-xs text-gray-400">BPJS Kesehatan + Ketenagakerjaan (karyawan)</p>
                        </div>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">—{{ formatCurrency(taxSummary.yearly_bpjs) }}</p>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-xl bg-red-50 dark:bg-red-950">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">PPh21 Terutang</p>
                            <p class="text-xs text-red-400">Pajak penghasilan tahun {{ new Date().getFullYear() }}</p>
                        </div>
                        <p class="text-base font-semibold text-red-700 dark:text-red-300">{{ formatCurrency(taxSummary.yearly_pph21) }}</p>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Penghasilan Bersih</p>
                            <p class="text-xs text-emerald-400">Setelah pajak dan iuran</p>
                        </div>
                        <p class="text-base font-semibold text-emerald-700 dark:text-emerald-300">
                            {{ formatCurrency(taxSummary.yearly_gross - taxSummary.yearly_bpjs - taxSummary.yearly_pph21) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info Note -->
            <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800">
                <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                    <strong>Catatan:</strong> Ringkasan ini bersifat informatif berdasarkan data penggajian yang telah diproses. Untuk perhitungan detail dan konsultasi pajak lebih lanjut, silakan hubungi tim Finance/HR.
                </p>
            </div>
        </div>
    </component>
</template>
