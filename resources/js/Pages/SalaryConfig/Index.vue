<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { EyeIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline'

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

        <div class="table-container">
                <DataTable
                    v-if="employees.data.length"
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari karyawan..."
                    @row-click="(row) => $inertia.visit(route('salary-config.show', row.id))"
                >
                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('salary-config.show', row.id)"
                                class="btn-secondary text-xs py-1.5 px-3"
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
    </AuthenticatedLayout>
</template>
