<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatCard from '@/Components/StatCard.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import {
    CurrencyDollarIcon,
    UsersIcon,
    DocumentTextIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const filterDates = ref({
    date_from: page.props.filters?.date_from || '',
    date_to: page.props.filters?.date_to || '',
})

const summary = computed(() => page.props.summary || {
    total_payrolls: 0,
    total_gross: 0,
    total_net: 0,
    total_employees: 0,
    total_pph21: 0,
})

const payrolls = computed(() => page.props.payrolls || { data: [] })

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const statusVariant = (status) => {
    const map = {
        draft: 'default',
        processed: 'info',
        approved: 'primary',
        paid: 'success',
    }
    return map[status] || 'default'
}

const columns = [
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'period_start', label: 'Awal Periode', sortable: true },
    { key: 'period_end', label: 'Akhir Periode', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'total_employees', label: 'Karyawan', sortable: true },
    { key: 'total_net_formatted', label: 'Total Bersih', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false },
]

const rows = computed(() =>
    payrolls.value.data.map((p) => ({
        ...p,
        total_net_formatted: formatCurrency(p.total_net ?? 0),
    }))
)

const applyFilter = () => {
    router.get(
        route('reports.payroll'),
        {
            date_from: filterDates.value.date_from,
            date_to: filterDates.value.date_to,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Laporan Penggajian" description="Lihat dan analisis data penggajian lintas periode" />

        <div class="space-y-6">
            <!-- Rentang Tanggal Filter -->
            <div class="glass-card p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Rentang Tanggal</h3>
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="date_from" class="form-label">Dari</label>
                        <input id="date_from" v-model="filterDates.date_from" type="date" class="form-input" />
                    </div>
                    <div>
                        <label for="date_to" class="form-label">Sampai</label>
                        <input id="date_to" v-model="filterDates.date_to" type="date" class="form-input" />
                    </div>
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <StatCard
                    title="Total Kotor"
                    :value="formatCurrency(summary.total_gross)"
                    :icon="CurrencyDollarIcon"
                    color="indigo"
                />
                <StatCard
                    title="Total Bersih"
                    :value="formatCurrency(summary.total_net)"
                    :icon="UsersIcon"
                    color="emerald"
                />
                <StatCard
                    title="Total PPh21"
                    :value="formatCurrency(summary.total_pph21)"
                    :icon="DocumentTextIcon"
                    color="amber"
                />
            </div>

            <!-- Payroll Data Table -->
            <div class="table-container">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Penggajian</h3>
                </div>
                <DataTable
                    v-if="payrolls.data.length"
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari penggajian..."
                >
                    <template #cell-status="{ value }">
                        <Badge :variant="statusVariant(value)">{{ value }}</Badge>
                    </template>
                    <template #cell-actions="{ row }">
                        <Link
                            :href="route('payroll.show', row.id)"
                            class="btn-secondary text-xs py-1.5 px-3"
                        >
                            <EyeIcon class="w-4 h-4" />
                            Lihat
                        </Link>
                    </template>
                </DataTable>
                <EmptyState
                    v-else
                    title="Tidak ada data penggajian"
                    description="Pilih rentang tanggal dan terapkan filter untuk melihat laporan."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
