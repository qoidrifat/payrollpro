<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { EyeIcon, Cog6ToothIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const employees = computed(() => page.props.employees || { data: [], meta: {} })

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const columns = [
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'position', label: 'Jabatan', sortable: true },
    { key: 'base_salary_formatted', label: 'Gaji Pokok', sortable: true },
    { key: 'components_count', label: 'Jumlah Komponen', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false },
]

const rows = computed(() =>
    employees.value.data.map((e) => ({
        ...e,
        base_salary_formatted: formatCurrency(e.base_salary ?? 0),
    }))
)
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Konfigurasi Gaji" description="Kelola struktur dan komponen gaji karyawan" />

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                    <CurrencyDollarIcon class="w-5 h-5" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Karyawan</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ employees.data?.length || 0 }} karyawan terdaftar</p>
                </div>
            </div>
            <div class="p-6">
                <DataTable
                    v-if="employees.data?.length"
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari karyawan..."
                    @row-click="(row) => $inertia.visit(route('salary-config.show', row.id))"
                >
                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('salary-config.show', row.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200"
                            >
                                <Cog6ToothIcon class="w-4 h-4" />
                                Konfigurasi
                            </Link>
                        </div>
                    </template>
                </DataTable>

                <EmptyState
                    v-else
                    title="Karyawan tidak ditemukan"
                    description="Tambahkan karyawan untuk mengonfigurasi komponen gaji."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
