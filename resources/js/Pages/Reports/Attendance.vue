<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { ClockIcon, FunnelIcon } from '@heroicons/vue/24/outline'

const page = usePage()

const selectedMonth = ref(page.props.filters?.month || '')

const attendanceData = computed(() => page.props.attendanceData || { data: [] })
const filters = computed(() => page.props.filters || {})

const columns = [
    { key: 'employee_name', label: 'Karyawan', sortable: true },
    { key: 'total_days', label: 'Total Hari', sortable: true },
    { key: 'present', label: 'Hadir', sortable: true },
    { key: 'absent', label: 'Tidak Hadir', sortable: true },
    { key: 'late', label: 'Terlambat', sortable: true },
    { key: 'sick', label: 'Sakit', sortable: true },
    { key: 'leave', label: 'Cuti', sortable: true },
    { key: 'attendance_rate', label: 'Persentase', sortable: true },
]

const rows = computed(() =>
    attendanceData.value.data.map((a) => ({
        ...a,
        employee_name: a.employee?.name || 'N/A',
        attendance_rate: a.total_days > 0
            ? `${Math.round((a.present / a.total_days) * 100)}%`
            : '0%',
    }))
)

const applyFilter = () => {
    router.get(route('reports.attendance'), { month: selectedMonth.value }, { preserveState: true, replace: true })
}
</script>

<template>
    <AuthenticatedLayout>
        <!-- ── Premium Header ─────────────────────────── -->
        <div class="mb-8">
            <div class="flex items-center gap-3.5 mb-2">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20 ring-2 ring-white/60 dark:ring-gray-900/60">
                    <ClockIcon class="w-5 h-5 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Laporan Absensi</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ringkasan absensi bulanan per karyawan</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- ── Premium Filter Card ─────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                <div class="relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-amber-50/40 to-transparent dark:from-amber-950/15" />
                    <div class="relative px-6 py-5 md:px-8 md:py-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                                <FunnelIcon class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Bulan</h3>
                        </div>
                        <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                            <div>
                                <label for="month" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Bulan</label>
                                <input id="month" v-model="selectedMonth" type="month"
                                    class="block rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus:ring-4 text-sm px-4 py-2.5 transition-all duration-200" />
                            </div>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-2 transition-all duration-200 shadow-md shadow-amber-500/20 active:scale-[0.97]">
                                Lihat Laporan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Attendance Table ────────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                <div class="px-6 py-5 md:px-8 md:py-6 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Ringkasan Absensi
                        <span v-if="selectedMonth" class="text-sm font-normal text-gray-400 dark:text-gray-500 ml-2">{{ selectedMonth }}</span>
                    </h3>
                </div>

                <div v-if="attendanceData.data.length" class="p-0">
                    <DataTable
                        :columns="columns"
                        :rows="rows"
                        search-placeholder="Cari karyawan..."
                        :server-side="true"
                        :total="attendanceData.total"
                        :current-page="attendanceData.current_page"
                        :last-page="attendanceData.last_page"
                        :per-page="attendanceData.per_page"
                        :filters="filters"
                        base-route="/reports/attendance"
                    >
                        <template #cell-attendance_rate="{ value }">
                            <Badge
                                :variant="
                                    parseFloat(value) >= 90 ? 'success' :
                                    parseFloat(value) >= 75 ? 'warning' : 'danger'
                                "
                            >
                                {{ value }}
                            </Badge>
                        </template>
                    </DataTable>
                </div>
                <div v-else class="p-8">
                    <EmptyState title="Tidak ada data absensi" description="Pilih bulan untuk melihat laporan absensi." />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
