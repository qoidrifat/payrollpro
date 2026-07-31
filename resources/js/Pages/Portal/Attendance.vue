<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import Badge from '@/Components/Badge.vue'
import { ClockIcon, FunnelIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const attendances = computed(() => page.props.attendances || {})
const records = computed(() => attendances.value.data || [])
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const months = computed(() => {
    const list = []
    const now = new Date()
    for (let i = 0; i < 6; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
        const val = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
        const label = d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
        list.push({ value: val, label })
    }
    return list
})

const selectedMonth = ref('')

const statusVariant = (status) => {
    const map = { present: 'success', late: 'warning', absent: 'danger', leave: 'info', sick: 'info', half_day: 'default' }
    return map[status] || 'default'
}

const statusLabel = (status) => {
    const map = { present: 'Hadir', late: 'Terlambat', absent: 'Tidak Hadir', leave: 'Cuti', sick: 'Sakit', half_day: 'Setengah Hari' }
    return map[status] || status
}

const formatTime = (time) => time ? time.substring(0, 5) : '\u2014'
const formatDate = (date) => {
    const d = new Date(date)
    return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })
}

const filterByMonth = () => {
    if (selectedMonth.value) router.get(route('portal.attendance'), { month: selectedMonth.value })
}

const summary = computed(() => {
    const s = { present: 0, late: 0, absent: 0, leave: 0, sick: 0, half_day: 0 }
    records.value.forEach(r => { if (s[r.status] !== undefined) s[r.status]++ })
    return s
})
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500" />
                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg">
                            <ClockIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl lg:text-2xl font-extrabold text-gray-900 dark:text-white gradient-text-emerald">Riwayat Absensi</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ employee?.first_name }} {{ employee?.last_name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/50 dark:to-green-950/50 border border-emerald-100 dark:border-emerald-900/30 text-center">
                    <p class="text-xl font-extrabold text-emerald-700 dark:text-emerald-300">{{ summary.present }}</p>
                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mt-1">Hadir</p>
                </div>
                <div class="p-4 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/50 dark:to-orange-950/50 border border-amber-100 dark:border-amber-900/30 text-center">
                    <p class="text-xl font-extrabold text-amber-700 dark:text-amber-300">{{ summary.late }}</p>
                    <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mt-1">Terlambat</p>
                </div>
                <div class="p-4 rounded-xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/50 dark:to-rose-950/50 border border-red-100 dark:border-red-900/30 text-center">
                    <p class="text-xl font-extrabold text-red-700 dark:text-red-300">{{ summary.absent }}</p>
                    <p class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase tracking-wider mt-1">Alfa</p>
                </div>
                <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-950/50 dark:to-cyan-950/50 border border-blue-100 dark:border-blue-900/30 text-center">
                    <p class="text-xl font-extrabold text-blue-700 dark:text-blue-300">{{ summary.leave + summary.sick }}</p>
                    <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mt-1">Izin</p>
                </div>
            </div>

            <!-- Filter -->
            <div class="glass-card p-4">
                <div class="flex items-center gap-3">
                    <FunnelIcon class="w-4 h-4 text-gray-400" />
                    <select v-model="selectedMonth" @change="filterByMonth" class="form-input w-auto text-sm py-1.5">
                        <option value="">Semua Bulan</option>
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>
            </div>

            <!-- Attendance List -->
            <div class="glass-card overflow-hidden">
                <div class="px-6 pt-6 pb-2">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Kehadiran</h3>
                </div>
                <div v-if="records.length" class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="att in records" :key="att.id"
                        class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
                    >
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-10 h-10 rounded-xl flex items-center justify-center',
                                att.status === 'present' ? 'bg-emerald-100 dark:bg-emerald-950' :
                                att.status === 'late' ? 'bg-amber-100 dark:bg-amber-950' :
                                'bg-gray-100 dark:bg-gray-800'
                            ]">
                                <ClockIcon :class="[
                                    'w-5 h-5',
                                    att.status === 'present' ? 'text-emerald-600 dark:text-emerald-400' :
                                    att.status === 'late' ? 'text-amber-600 dark:text-amber-400' :
                                    'text-gray-400'
                                ]" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(att.date) }}</p>
                                <p class="text-xs text-gray-400">{{ att.type === 'wfo' ? 'Work From Office' : att.type === 'wfh' ? 'Work From Home' : 'Remote' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-[11px] font-bold text-gray-400 uppercase">Masuk</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatTime(att.clock_in) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-bold text-gray-400 uppercase">Pulang</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatTime(att.clock_out) }}</p>
                            </div>
                            <Badge :variant="statusVariant(att.status)">{{ statusLabel(att.status) }}</Badge>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-10">Belum ada data absensi.</p>
            </div>
        </div>
    </component>
</template>
