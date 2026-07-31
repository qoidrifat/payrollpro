<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Badge from '@/Components/Badge.vue'
import { useSupabaseRealtime } from '@/composables/useSupabaseRealtime'
import axios from 'axios'
import {
    UsersIcon,
    ClockIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    ArrowRightIcon,
    ChartBarIcon,
    QrCodeIcon,
    CalendarDaysIcon,
    BanknotesIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const stats = computed(() => page.props.stats || {})
const employee = computed(() => page.props.employee || null)
const employeeData = computed(() => page.props.employeeData || {})
const permissions = computed(() => page.props.auth?.user?.permissions || [])
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const liveAttendance = ref(null)
let pollTimer = null
let realtimeUnsubscribe = null
let realtimeReloadTimer = null
const realtime = useSupabaseRealtime()

const fetchLiveAttendance = async () => {
    try {
        const { data } = await axios.get('/api/today-attendance')
        liveAttendance.value = data
    } catch (e) {}
}

const refreshAdminDashboard = () => {
    fetchLiveAttendance()
    if (realtimeReloadTimer) clearTimeout(realtimeReloadTimer)
    realtimeReloadTimer = setTimeout(() => {
        router.reload({ only: ['stats'], preserveScroll: true, preserveState: true })
    }, 500)
}

const shouldPollLiveAttendance = () => !realtime.isConfigured || realtime.status.value !== 'SUBSCRIBED'

onMounted(() => {
    if (!isEmployee.value) {
        fetchLiveAttendance()
        realtimeUnsubscribe = realtime.subscribeToNotifications({
            channelName: 'project-kp-dashboard',
            topics: ['attendance', 'payroll', 'leave'],
            onChange: refreshAdminDashboard,
        })
        pollTimer = setInterval(() => {
            if (shouldPollLiveAttendance()) fetchLiveAttendance()
        }, 60000)
    }
})

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer)
    if (realtimeReloadTimer) clearTimeout(realtimeReloadTimer)
    if (realtimeUnsubscribe) realtimeUnsubscribe()
})

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const formatTime = (time) => time ? time.substring(0, 5) : '\u2014'

const payrollStatusVariant = (status) => {
    const map = { draft: 'default', processed: 'info', approved: 'primary', paid: 'success' }
    return map[status] || 'default'
}

const adminQuickActions = computed(() =>
    [
        { label: 'Proses Penggajian', href: route('payroll.create'), icon: DocumentTextIcon, description: 'Buat & proses penggajian bulan ini', color: 'from-indigo-500 to-purple-600', permission: 'manage-payroll' },
        { label: 'Tambah Karyawan', href: route('employees.create'), icon: UsersIcon, description: 'Tambahkan anggota tim baru', color: 'from-emerald-500 to-teal-600', permission: 'manage-employees' },
        { label: 'Persetujuan Cuti', href: route('leave-requests.index'), icon: ClipboardDocumentCheckIcon, description: `${stats.value.leaveApprovals?.pending ?? 0} pengajuan menunggu`, color: 'from-rose-500 to-pink-600', permission: 'manage-attendance' },
        { label: 'Absensi QR', href: route('attendance.my-qr'), icon: QrCodeIcon, description: 'Scan QR untuk absensi cepat', color: 'from-amber-500 to-orange-600', permission: 'view-attendance' },
        { label: 'Lihat Laporan', href: route('reports.payroll'), icon: ChartBarIcon, description: 'Akses laporan penggajian & pajak', color: 'from-sky-500 to-blue-600', permission: 'view-reports' },
    ].filter(a => permissions.value.includes(a.permission))
)

const employeeQuickActions = computed(() =>
    [
        { label: 'Absensi Saya', href: route('portal.attendance'), icon: ClockIcon, description: 'Riwayat jam kerja & kehadiran', color: 'from-sky-500 to-blue-600', gradient: 'from-sky-400 via-blue-500 to-indigo-600' },
        { label: 'Slip Gaji', href: route('portal.payroll'), icon: BanknotesIcon, description: 'Lihat, unduh & cetak slip gaji', color: 'from-indigo-500 to-purple-600', gradient: 'from-indigo-400 via-purple-500 to-pink-600' },
        { label: 'QR Absensi', href: route('attendance.my-qr'), icon: QrCodeIcon, description: 'Scan QR untuk clock-in & clock-out', color: 'from-amber-500 to-orange-600', gradient: 'from-amber-400 via-orange-500 to-red-600' },
        { label: 'Cuti & Izin', href: route('portal.leaves'), icon: CalendarDaysIcon, description: 'Ajukan & pantau status pengajuan cuti', color: 'from-rose-500 to-red-600', gradient: 'from-rose-400 via-red-500 to-pink-600' },
    ])

const quickActions = computed(() =>
    isEmployee.value ? employeeQuickActions.value : adminQuickActions.value
)

const attendanceVariant = (status) => {
    const map = { present: 'success', late: 'warning', absent: 'danger', leave: 'info', sick: 'info' }
    return map[status] || 'default'
}

const attendanceLabel = (status) => {
    const map = { present: 'Hadir', late: 'Terlambat', absent: 'Tidak Hadir', leave: 'Cuti', sick: 'Sakit' }
    return map[status] || status
}
</script>

<template>
    <component :is="Layout">
        <PageHeader
            :title="isEmployee ? 'Dashboard Saya' : 'Dashboard'"
            :description="isEmployee ? `Selamat datang, ${employee?.first_name || 'Karyawan'}` : 'Ringkasan sistem penggajian Anda'"
        />

        <div class="space-y-8">
            <!-- ═══ EMPLOYEE VIEW ═══ -->
            <template v-if="isEmployee">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <StatCard title="Absensi Hari Ini" :value="employeeData?.todayAttendance?.clock_in ? 'Hadir' : 'Belum Absen'" :icon="ClockIcon" color="emerald" />
                    <StatCard title="Jam Masuk" :value="formatTime(employeeData?.todayAttendance?.clock_in)" :icon="CheckCircleIcon" color="indigo" />
                    <StatCard title="Jam Pulang" :value="formatTime(employeeData?.todayAttendance?.clock_out)" :icon="XCircleIcon" color="amber" />
                    <StatCard title="Cuti Pending" :value="String(employeeData?.pendingLeaves ?? 0)" :icon="CalendarDaysIcon" color="purple" />
                </div>

                <!-- Today's Attendance -->
                <div v-if="employeeData?.todayAttendance" class="glass-card p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Status Absensi Hari Ini</h3>
                        <Badge :variant="attendanceVariant(employeeData.todayAttendance.status)" size="lg">
                            {{ attendanceLabel(employeeData.todayAttendance.status) }}
                        </Badge>
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950/50 dark:to-blue-950/50 border border-indigo-100 dark:border-indigo-900/30">
                            <p class="text-[11px] text-indigo-500 dark:text-indigo-400 uppercase tracking-wider font-semibold mb-1">Clock In</p>
                            <p class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white">{{ formatTime(employeeData.todayAttendance.clock_in) }}</p>
                        </div>
                        <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/50 dark:to-orange-950/50 border border-amber-100 dark:border-amber-900/30">
                            <p class="text-[11px] text-amber-500 dark:text-amber-400 uppercase tracking-wider font-semibold mb-1">Clock Out</p>
                            <p class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white">{{ formatTime(employeeData.todayAttendance.clock_out) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card p-5 lg:p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Aktivitas Saya</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Akses cepat ke fitur utama</p>
                        </div>
                    </div>
                    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.href"
                            class="group relative flex flex-col p-5 rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900/50 hover:shadow-lg hover:-translate-y-0.5 hover:border-gray-200 dark:hover:border-gray-700 transition-all duration-300 overflow-hidden"
                        >
                            <div :class="`absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r ${action.gradient} opacity-70 group-hover:opacity-100 transition-opacity duration-300`" />
                            <div class="flex items-start gap-4 relative z-10">
                                <div :class="`relative w-11 h-11 rounded-xl bg-gradient-to-br ${action.gradient} flex items-center justify-center shadow-sm group-hover:shadow-md group-hover:scale-105 transition-all duration-300`">
                                    <component :is="action.icon" class="w-5 h-5 text-white" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:gradient-text transition-all duration-300">
                                        {{ action.label }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                        {{ action.description }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-1.5">
                                <span class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 group-hover:translate-x-0.5 transition-transform duration-300">
                                    Buka
                                </span>
                                <ArrowRightIcon class="w-3 h-3 text-primary-500 group-hover:translate-x-1 transition-transform duration-300" />
                            </div>
                        </Link>
                    </div>
                </div>
            </template>

            <!-- ═══ ADMIN/HR VIEW ═══ -->
            <template v-else>
                <!-- Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <StatCard title="Total Karyawan" :value="String(stats.totalActiveEmployees ?? 0)" :icon="UsersIcon" color="indigo" />
                    <StatCard title="Hadir Hari Ini" :value="`${stats.todayAttendance?.present ?? 0} / ${stats.todayAttendance?.total ?? 0}`" :icon="ClockIcon" color="emerald" />
                    <StatCard title="Penggajian Bulan Ini" :value="formatCurrency(stats.currentMonthPayrollNet ?? 0)" :icon="CurrencyDollarIcon" color="purple" />
                    <StatCard title="Penggajian Tertunda" :value="String(stats.pendingPayrollCount ?? 0)" :icon="DocumentTextIcon" color="amber" />
                </div>

                <!-- Middle Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <!-- Pending Approvals -->
                    <div class="glass-card p-5 lg:p-6 lg:col-span-1">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Persetujuan Cuti</p>
                                <p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">{{ stats.leaveApprovals?.pending ?? 0 }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Menunggu review</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center shadow-sm">
                                <ClipboardDocumentCheckIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-emerald-50 dark:bg-emerald-950/60 px-4 py-3 border border-emerald-100 dark:border-emerald-900/30">
                            <span class="text-sm text-emerald-700 dark:text-emerald-300 font-medium">Disetujui bulan ini</span>
                            <span class="text-sm font-bold text-emerald-800 dark:text-emerald-200">{{ stats.leaveApprovals?.approvedThisMonth ?? 0 }}</span>
                        </div>
                    </div>

                    <!-- Recent Leave Requests -->
                    <div class="glass-card p-5 lg:p-6 lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Pengajuan Cuti Terbaru</h3>
                            <Link :href="route('leave-requests.index')" class="text-sm font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                                Kelola Semua
                                <ArrowRightIcon class="w-4 h-4" />
                            </Link>
                        </div>
                        <div v-if="stats.leaveApprovals?.recentPending?.length" class="space-y-2">
                            <div
                                v-for="leave in stats.leaveApprovals.recentPending"
                                :key="leave.id"
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-colors"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ leave.employee_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ leave.department || leave.position || 'Karyawan' }} · {{ leave.leave_type }}</p>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 sm:text-right flex-shrink-0">
                                    <p>{{ leave.start_date }} - {{ leave.end_date }}</p>
                                    <p class="text-xs text-gray-400">{{ leave.total_days }} hari</p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">Tidak ada pengajuan cuti yang menunggu.</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="glass-card p-5 lg:p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Aksi Cepat</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Langkah cepat untuk mengelola sistem</p>
                        </div>
                    </div>
                    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.href"
                            class="group relative flex flex-col p-5 rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900/50 hover:shadow-lg hover:-translate-y-0.5 hover:border-gray-200 dark:hover:border-gray-700 transition-all duration-300 overflow-hidden"
                        >
                            <div :class="`absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r ${action.color} opacity-70 group-hover:opacity-100 transition-opacity`" />
                            <div :class="`w-11 h-11 rounded-xl bg-gradient-to-br ${action.color} flex items-center justify-center shadow-sm group-hover:shadow-md group-hover:scale-105 transition-all duration-300`">
                                <component :is="action.icon" class="w-5 h-5 text-white" />
                            </div>
                            <div class="mt-3 min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:gradient-text transition-all duration-300">{{ action.label }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{{ action.description }}</p>
                            </div>
                            <div class="mt-auto self-end pt-2">
                                <ArrowRightIcon class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 group-hover:translate-x-1 transition-all duration-300" />
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Live Attendance -->
                <div v-if="liveAttendance" class="glass-card overflow-hidden">
                    <div class="flex items-center justify-between px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                            </span>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Absensi Hari Ini — Live</h3>
                        </div>
                        <span class="text-sm text-gray-400">{{ liveAttendance.date }}</span>
                    </div>
                    <div class="px-6 pb-6">
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/50 dark:to-green-950/50 border border-emerald-100 dark:border-emerald-900/30 text-center">
                                <p class="text-2xl font-extrabold text-emerald-700 dark:text-emerald-300">{{ liveAttendance.present + liveAttendance.late }}</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">Hadir</p>
                            </div>
                            <div class="p-4 rounded-xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/50 dark:to-rose-950/50 border border-red-100 dark:border-red-900/30 text-center">
                                <p class="text-2xl font-extrabold text-red-700 dark:text-red-300">{{ liveAttendance.absent }}</p>
                                <p class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">Tidak Hadir</p>
                            </div>
                            <div class="p-4 rounded-xl bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-200 dark:border-gray-800 text-center">
                                <p class="text-2xl font-extrabold text-gray-700 dark:text-gray-300">{{ liveAttendance.total }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mt-1">Total</p>
                            </div>
                        </div>
                        <div v-if="liveAttendance.records?.length" class="space-y-1 max-h-48 overflow-y-auto scrollbar-thin">
                            <div v-for="rec in liveAttendance.records" :key="rec.id" class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-2 h-2 rounded-full', rec.clock_out ? 'bg-emerald-500' : 'bg-amber-500']" />
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ rec.employee_name }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-xs text-gray-400">
                                    <span>{{ rec.clock_in || '\u2014' }}</span>
                                    <span>{{ rec.clock_out || '\u2014' }}</span>
                                    <Badge :variant="rec.status === 'present' ? 'success' : rec.status === 'late' ? 'warning' : 'danger'">{{ rec.status }}</Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payroll -->
                <div class="glass-card overflow-hidden">
                    <div class="flex items-center justify-between px-6 pt-6 pb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Penggajian Terbaru</h3>
                        <Link :href="route('payroll.index')" class="text-sm font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                            Lihat Semua <ArrowRightIcon class="w-4 h-4" />
                        </Link>
                    </div>
                    <div class="px-6 pb-6">
                        <table class="w-full text-sm" v-if="stats.latestPayrolls?.length">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <th class="text-left py-3 px-2 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Nama</th>
                                    <th class="text-left py-3 px-2 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Periode</th>
                                    <th class="text-left py-3 px-2 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Status</th>
                                    <th class="text-right py-3 px-2 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Total Bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payroll in stats.latestPayrolls" :key="payroll.id"
                                    class="border-b border-gray-50 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800/30 cursor-pointer transition-colors"
                                    @click="$inertia.visit(route('payroll.show', payroll.id))"
                                >
                                    <td class="py-3 px-2 text-gray-900 dark:text-white font-semibold">{{ payroll.name }}</td>
                                    <td class="py-3 px-2 text-gray-500 dark:text-gray-400">{{ payroll.period_start }} - {{ payroll.period_end }}</td>
                                    <td class="py-3 px-2"><Badge :variant="payrollStatusVariant(payroll.status)">{{ payroll.status }}</Badge></td>
                                    <td class="py-3 px-2 text-right font-semibold text-gray-900 dark:text-white">{{ formatCurrency(payroll.total_net ?? 0) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-center py-8">Belum ada penggajian.</p>
                    </div>
                </div>
            </template>
        </div>
    </component>
</template>
