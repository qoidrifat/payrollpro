<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import DataTable from '@/Components/DataTable.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { DocumentTextIcon, FunnelIcon } from '@heroicons/vue/24/outline'

const page = usePage()

const selectedYear = ref(page.props.filters?.year || new Date().getFullYear())

const taxData = computed(() => page.props.taxData || { data: [] })
const totalPph21 = computed(() => page.props.totalPph21 || 0)
const filters = computed(() => page.props.filters || {})

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

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
        npwp: t.employee?.npwp || '\u2014',
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
    for (let y = currentYear; y >= currentYear - 5; y--) { years.push(y) }
    return years
})

const applyFilter = () => {
    router.get(route('reports.tax'), { year: selectedYear.value }, { preserveState: true, replace: true })
}
</script>

<template>
    <AuthenticatedLayout>
        <!-- ── Premium Header ─────────────────────────── -->
        <div class="mb-8">
            <div class="flex items-center gap-3.5 mb-2">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center shadow-lg shadow-purple-500/20 ring-2 ring-white/60 dark:ring-gray-900/60">
                    <DocumentTextIcon class="w-5 h-5 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Laporan Pajak (PPh21)</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ringkasan pajak PPh21 tahunan per karyawan</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- ── Premium Filter Card ─────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                <div class="relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-50/40 to-transparent dark:from-purple-950/15" />
                    <div class="relative px-6 py-5 md:px-8 md:py-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                <FunnelIcon class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Tahun</h3>
                        </div>
                        <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                            <div>
                                <label for="year" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Tahun</label>
                                <select id="year" v-model="selectedYear"
                                    class="block rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500/20 focus:ring-4 text-sm px-4 py-2.5 transition-all duration-200">
                                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-2 transition-all duration-200 shadow-md shadow-purple-500/20 active:scale-[0.97]">
                                Lihat Laporan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Total PPh21 Stat ────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-1 gap-5">
                <StatCard
                    title="Total PPh21 untuk {{ selectedYear }}"
                    :value="formatCurrency(totalPph21)"
                    :icon="DocumentTextIcon"
                    color="purple"
                />
            </div>

            <!-- ── Tax Detail Table ────────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                <div class="px-6 py-5 md:px-8 md:py-6 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Detail PPh21 — {{ selectedYear }}
                    </h3>
                </div>

                <div v-if="taxData.data.length" class="p-0">
                    <DataTable
                        :columns="columns"
                        :rows="rows"
                        search-placeholder="Cari karyawan..."
                        :server-side="true"
                        :total="taxData.total"
                        :current-page="taxData.current_page"
                        :last-page="taxData.last_page"
                        :per-page="taxData.per_page"
                        :filters="filters"
                        base-route="/reports/tax"
                    />
                </div>
                <div v-else class="p-8">
                    <EmptyState title="Tidak ada data pajak" description="Pilih tahun untuk melihat laporan PPh21." />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
