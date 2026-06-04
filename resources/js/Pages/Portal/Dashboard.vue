<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import Badge from '@/Components/Badge.vue'
import {
    ClockIcon,
    CurrencyDollarIcon,
    CalendarDaysIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const todayAttendance = computed(() => page.props.todayAttendance)
const pendingLeaves = computed(() => page.props.pendingLeaves ?? 0)
const recentPayslips = computed(() => page.props.recentPayslips || [])
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const formatTime = (time) => time ? time.substring(0, 5) : '—'

const statusVariant = (status) => {
    const map = { present: 'success', late: 'warning', absent: 'danger', leave: 'info' }
    return map[status] || 'default'
}
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Welcome Header -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <span class="text-2xl font-display font-bold text-white">
                            {{ employee?.first_name?.charAt(0) }}{{ employee?.last_name?.charAt(0) || '' }}
                        </span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">
                            Selamat datang, {{ employee?.first_name }}!
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ employee?.position }} · {{ employee?.department || '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <StatCard
                    title="Absensi Hari Ini"
                    :value="todayAttendance?.clock_in ? 'Hadir' : 'Belum Absen'"
                    :icon="ClockIcon"
                    color="emerald"
                />
                <StatCard
                    title="Jam Masuk"
                    :value="formatTime(todayAttendance?.clock_in)"
                    :icon="CheckCircleIcon"
                    color="indigo"
                />
                <StatCard
                    title="Jam Pulang"
                    :value="formatTime(todayAttendance?.clock_out)"
                    :icon="XCircleIcon"
                    color="amber"
                />
                <StatCard
                    title="Cuti Pending"
                    :value="String(pendingLeaves)"
                    :icon="CalendarDaysIcon"
                    color="purple"
                />
            </div>

            <!-- Today's Attendance Status -->
            <div v-if="todayAttendance" class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status Absensi Hari Ini</h3>
                    <Badge :variant="statusVariant(todayAttendance.status)">{{ todayAttendance.status }}</Badge>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Clock In</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatTime(todayAttendance.clock_in) }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Clock Out</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatTime(todayAttendance.clock_out) }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Payslips -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Slip Gaji Terbaru</h3>
                    <Link href="/portal/payroll" class="text-sm font-medium text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                        Lihat Semua
                        <ArrowRightIcon class="w-4 h-4" />
                    </Link>
                </div>
                <div v-if="recentPayslips.length" class="space-y-3">
                    <div v-for="slip in recentPayslips" :key="slip.payslip_id"
                        class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                <CurrencyDollarIcon class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ slip.period }}</p>
                                <p class="text-xs text-gray-400">Take Home Pay</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(slip.net_salary) }}</p>
                            <div v-if="slip.payroll_item_id" class="flex items-center gap-2 mt-1">
                                <a
                                    :href="`/payslips/${slip.payroll_item_id}/preview`"
                                    class="text-xs font-medium text-primary-600 hover:text-primary-700 hover:underline"
                                >
                                    Lihat
                                </a>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <a
                                    :href="`/payslips/print/${slip.payroll_item_id}`"
                                    class="inline-flex items-center gap-1 text-xs font-medium bg-gradient-to-r from-primary-500 to-primary-600 text-white px-2.5 py-1 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200"
                                >
                                    Cetak PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">
                    Belum ada slip gaji.
                </p>
            </div>
        </div>
    </component>
</template>
