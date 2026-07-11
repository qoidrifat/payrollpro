<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import EmptyState from '@/Components/EmptyState.vue'

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
    router.get(
        route('reports.attendance'),
        { month: selectedMonth.value },
        {
            preserveState: true,
            replace: true,
        }
    )
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Laporan Absensi" description="Ringkasan absensi bulanan per karyawan" />

        <div class="space-y-6">
            <!-- Pilih Bulan -->
            <div class="glass-card p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Pilih Bulan</h3>
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="month" class="form-label">Bulan</label>
                        <input
                            id="month"
                            v-model="selectedMonth"
                            type="month"
                            class="form-input"
                        />
                    </div>
                    <button type="submit" class="btn-primary">Lihat Laporan</button>
                </form>
            </div>

            <!-- Attendance Table -->
            <div class="table-container">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Ringkasan Absensi
                        <span v-if="selectedMonth" class="text-sm font-normal text-gray-400 ml-2">{{ selectedMonth }}</span>
                    </h3>
                </div>
                <DataTable
                    v-if="attendanceData.data.length"
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
                <EmptyState
                    v-else
                    title="Tidak ada data absensi"
                    description="Pilih bulan untuk melihat laporan absensi."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
