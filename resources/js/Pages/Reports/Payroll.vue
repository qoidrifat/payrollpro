<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import {
    CurrencyDollarIcon,
    UsersIcon,
    DocumentTextIcon,
    EyeIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()

const filterDates = ref({
    date_from: page.props.filters?.date_from || '',
    date_to: page.props.filters?.date_to || '',
})

const summary = computed(() => page.props.summary || {
    total_payrolls: 0, total_gross: 0, total_net: 0, total_employees: 0, total_pph21: 0,
})

const payrolls = computed(() => page.props.payrolls || { data: [] })
const filters = computed(() => page.props.filters || {})

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const statusVariant = (status) => {
    const map = { draft: 'default', processed: 'info', approved: 'primary', paid: 'success' }
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
    payrolls.value.data.map((p) => ({ ...p, total_net_formatted: formatCurrency(p.total_net ?? 0) }))
)

const applyFilter = () => {
    router.get(route('reports.payroll'), {
        date_from: filterDates.value.date_from,
        date_to: filterDates.value.date_to,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <AuthenticatedLayout>
        <!-- ── Premium Header ─────────────────────────── -->
        <div class="mb-8">
            <div class="flex items-center gap-3.5 mb-2">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 ring-2 ring-white/60 dark:ring-gray-900/60">
                    <CurrencyDollarIcon class="w-5 h-5 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Laporan Penggajian</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Lihat dan analisis data penggajian lintas periode</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- ── Premium Filter Card ─────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                <div class="relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/40 to-transparent dark:from-indigo-950/15" />
                    <div class="relative px-6 py-5 md:px-8 md:py-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                                <FunnelIcon class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Rentang Tanggal</h3>
                        </div>
                        <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                            <div>
                                <label for="date_from" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Dari</label>
                                <input id="date_from" v-model="filterDates.date_from" type="date"
                                    class="block rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 focus:ring-4 text-sm px-4 py-2.5 transition-all duration-200" />
                            </div>
                            <div>
                                <label for="date_to" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Sampai</label>
                                <input id="date_to" v-model="filterDates.date_to" type="date"
                                    class="block rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 focus:ring-4 text-sm px-4 py-2.5 transition-all duration-200" />
                            </div>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 transition-all duration-200 shadow-md shadow-indigo-500/20 active:scale-[0.97]">
                                Terapkan Filter
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Summary Stats ───────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <StatCard title="Total Kotor" :value="formatCurrency(summary.total_gross)" :icon="CurrencyDollarIcon" color="indigo" />
                <StatCard title="Total Bersih" :value="formatCurrency(summary.total_net)" :icon="UsersIcon" color="emerald" />
                <StatCard title="Total PPh21" :value="formatCurrency(summary.total_pph21)" :icon="DocumentTextIcon" color="amber" />
            </div>

            <!-- ── Payroll Data Table ──────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                <div class="px-6 py-5 md:px-8 md:py-6 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Daftar Penggajian</h3>
                </div>

                <div v-if="payrolls.data.length" class="p-0">
                    <DataTable
                        :columns="columns"
                        :rows="rows"
                        search-placeholder="Cari penggajian..."
                        :server-side="true"
                        :total="payrolls.total"
                        :current-page="payrolls.current_page"
                        :last-page="payrolls.last_page"
                        :per-page="payrolls.per_page"
                        :filters="filters"
                        base-route="/reports/payroll"
                    >
                        <template #cell-status="{ value }">
                            <Badge :variant="statusVariant(value)">{{ value }}</Badge>
                        </template>
                        <template #cell-actions="{ row }">
                            <Link :href="route('payroll.show', row.id)"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-300 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/50 transition-all duration-200 active:scale-[0.97]">
                                <EyeIcon class="w-4 h-4" />
                                Lihat
                            </Link>
                        </template>
                    </DataTable>
                </div>
                <div v-else class="p-8">
                    <EmptyState
                        title="Tidak ada data penggajian"
                        description="Pilih rentang tanggal dan terapkan filter untuk melihat laporan."
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
