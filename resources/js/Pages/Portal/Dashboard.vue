<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import Badge from '@/Components/Badge.vue'
import {
    ClockIcon, CurrencyDollarIcon, CalendarDaysIcon, ArrowRightIcon,
    CheckCircleIcon, XCircleIcon,
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
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const formatTime = (time) => time ? time.substring(0, 5) : '\u2014'

const statusVariant = (status) => {
    const map = { present: 'success', late: 'warning', absent: 'danger', leave: 'info' }
    return map[status] || 'default'
}
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Welcome Header -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-primary-500 via-purple-500 to-indigo-500" />
                <div class="p-6">
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg ring-4 ring-white dark:ring-gray-900">
                            <span class="text-2xl font-extrabold text-white">
                                {{ employee?.first_name?.charAt(0) }}{{ employee?.last_name?.charAt(0) || '' }}
                            </span>
                        </div>
                        <div>
                            <h1 class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white gradient-text">
                                Selamat datang, {{ employee?.first_name }}!
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ employee?.position }} · {{ employee?.department || '\u2014' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <StatCard title="Absensi Hari Ini" :value="todayAttendance?.clock_in ? 'Hadir' : 'Belum Absen'" :icon="ClockIcon" color="emerald" />
                <StatCard title="Jam Masuk" :value="formatTime(todayAttendance?.clock_in)" :icon="CheckCircleIcon" color="indigo" />
                <StatCard title="Jam Pulang" :value="formatTime(todayAttendance?.clock_out)" :icon="XCircleIcon" color="amber" />
                <StatCard title="Cuti Pending" :value="String(pendingLeaves)" :icon="CalendarDaysIcon" color="purple" />
            </div>

            <!-- Today's Attendance -->
            <div v-if="todayAttendance" class="glass-card p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Status Absensi Hari Ini</h3>
                    <Badge :variant="statusVariant(todayAttendance.status)" size="lg">{{ todayAttendance.status }}</Badge>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950/50 dark:to-blue-950/50 border border-indigo-100 dark:border-indigo-900/30">
                        <p class="text-[11px] font-bold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider mb-1">Clock In</p>
                        <p class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white">{{ formatTime(todayAttendance.clock_in) }}</p>
                    </div>
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/50 dark:to-orange-950/50 border border-amber-100 dark:border-amber-900/30">
                        <p class="text-[11px] font-bold text-amber-500 dark:text-amber-400 uppercase tracking-wider mb-1">Clock Out</p>
                        <p class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white">{{ formatTime(todayAttendance.clock_out) }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Payslips -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Slip Gaji Terbaru</h3>
                    <Link :href="route('portal.payroll')" class="text-sm font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                        Lihat Semua <ArrowRightIcon class="w-4 h-4" />
                    </Link>
                </div>
                <div v-if="recentPayslips.length" class="space-y-3">
                    <div v-for="slip in recentPayslips" :key="slip.payslip_id"
                        class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-all"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                                <CurrencyDollarIcon class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ slip.period }}</p>
                                <p class="text-xs text-gray-400">Take Home Pay</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(slip.net_salary) }}</p>
                            <div v-if="slip.payroll_item_id" class="flex items-center gap-2 mt-1 justify-end">
                                <a :href="route('payslips.preview', slip.payroll_item_id)" class="text-xs font-semibold text-primary-600 hover:text-primary-700 hover:underline">Lihat</a>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <a :href="route('payslips.print', slip.payroll_item_id)"
                                    class="inline-flex items-center gap-1 text-xs font-semibold bg-gradient-to-r from-primary-500 to-primary-600 text-white px-2.5 py-1 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all">
                                    Cetak PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">Belum ada slip gaji.</p>
            </div>
        </div>
    </component>
</template>
