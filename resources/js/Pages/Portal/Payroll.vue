<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { CurrencyDollarIcon, ArrowDownTrayIcon, DocumentTextIcon, EyeIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const payrollItems = computed(() => page.props.payrollItems || {})
const records = computed(() => payrollItems.value.data || [])
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const formatTime = (date) => {
    if (!date) return '\u2014'
    const d = new Date(date)
    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
}
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-indigo-500 to-purple-600" />
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <CurrencyDollarIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white">Riwayat Penggajian</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ employee?.first_name }} {{ employee?.last_name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll List -->
            <div class="space-y-4">
                <div v-for="item in records" :key="item.id"
                    class="glass-card overflow-hidden hover:shadow-card-hover transition-all duration-300"
                >
                    <div class="h-1 bg-gradient-to-r from-emerald-500 to-teal-500" />
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                    <DocumentTextIcon class="w-6 h-6 text-white" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ item.payroll?.name || formatTime(item.created_at) }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Gaji Pokok: {{ formatCurrency(item.gross_salary) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-extrabold text-gray-900 dark:text-white">{{ formatCurrency(item.net_salary) }}</p>
                                <div v-if="item.id" class="flex items-center justify-end gap-2 mt-2">
                                    <a :href="`/payslips/${item.id}/preview`"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 active:scale-[0.97] transition-all duration-200">
                                        <EyeIcon class="w-3.5 h-3.5" /> Lihat
                                    </a>
                                    <a :href="`/payslips/print/${item.id}`"
                                        class="group/btn inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 text-white text-xs font-bold shadow-sm hover:shadow-md hover:from-primary-600 hover:to-primary-700 active:scale-[0.97] transition-all duration-200">
                                        <ArrowDownTrayIcon class="w-3.5 h-3.5 group-hover/btn:translate-y-0.5 transition-transform duration-200" /> Cetak
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Breakdown -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">BPJS Kesehatan</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ formatCurrency(item.bpjs_kesehatan_employee) }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">BPJS TK JHT</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ formatCurrency(item.bpjs_tk_jht_employee) }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">BPJS TK JP</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ formatCurrency(item.bpjs_tk_jp_employee) }}</p>
                            </div>
                            <div class="p-3 rounded-xl bg-red-50 dark:bg-red-950/40">
                                <p class="text-[10px] font-bold text-red-500 dark:text-red-400 uppercase tracking-wider">PPh21</p>
                                <p class="text-sm font-semibold text-red-600 dark:text-red-400 mt-0.5">{{ formatCurrency(item.pph21) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="!records.length" class="text-sm text-gray-500 dark:text-gray-400 text-center py-10">Belum ada riwayat penggajian.</p>
            </div>
        </div>
    </component>
</template>
