<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatCard from '@/Components/StatCard.vue'
import DataTable from '@/Components/DataTable.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { DocumentTextIcon } from '@heroicons/vue/24/outline'

const page = usePage()

const selectedYear = ref(page.props.filters?.year || new Date().getFullYear())

const taxData = computed(() => page.props.taxData || { data: [] })
const totalPph21 = computed(() => page.props.totalPph21 || 0)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const columns = [
    { key: 'employee_name', label: 'Karyawan', sortable: true },
    { key: 'npwp', label: 'NPWP', sortable: true },
    { key: 'gross_annual', label: 'Kotor Tahunan', sortable: true },
    { key: 'ptkp', label: 'PTKP', sortable: true },
    { key: 'pkp', label: 'PKP', sortable: true },
    { key: 'pph21_per_month', label: 'PPh21/Bulan', sortable: true },
    { key: 'pph21_annual', label: 'PPh21 Tahunan', sortable: true },
]

const rows = computed(() =>
    taxData.value.data.map((t) => ({
        ...t,
        employee_name: t.employee?.name || 'N/A',
        npwp: t.employee?.npwp || '—',
        gross_annual: formatCurrency(t.gross_annual ?? 0),
        ptkp: formatCurrency(t.ptkp ?? 0),
        pkp: formatCurrency(t.pkp ?? 0),
        pph21_per_month: formatCurrency(t.pph21_per_month ?? 0),
        pph21_annual: formatCurrency(t.pph21_annual ?? 0),
    }))
)

const yearOptions = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    for (let y = currentYear; y >= currentYear - 5; y--) {
        years.push(y)
    }
    return years
})

const applyFilter = () => {
    router.get(
        route('reports.tax'),
        { year: selectedYear.value },
        {
            preserveState: true,
            replace: true,
        }
    )
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Laporan Pajak (PPh21)" description="Ringkasan pajak PPh21 tahunan per karyawan" />

        <div class="space-y-6">
            <!-- Year Selector -->
            <div class="glass-card p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Pilih Tahun</h3>
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="year" class="form-label">Tahun</label>
                        <select id="year" v-model="selectedYear" class="form-input">
                            <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Lihat Laporan</button>
                </form>
            </div>

            <!-- Total PPh21 -->
            <div class="grid grid-cols-1 sm:grid-cols-1 gap-6">
                <StatCard
                    title="Total PPh21 untuk {{ selectedYear }}"
                    :value="formatCurrency(totalPph21)"
                    :icon="DocumentTextIcon"
                    color="amber"
                />
            </div>

            <!-- Tax Detail Table -->
            <div class="table-container">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Detail PPh21 — {{ selectedYear }}
                    </h3>
                </div>
                <DataTable
                    v-if="taxData.data.length"
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari karyawan..."
                />
                <EmptyState
                    v-else
                    title="Tidak ada data pajak"
                    description="Pilih tahun untuk melihat laporan PPh21."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
