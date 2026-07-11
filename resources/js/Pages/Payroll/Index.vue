<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { PlusIcon, EyeIcon, TrashIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const payrolls = page.props.payrolls || { data: [], meta: {} }
const filters = page.props.filters || {}

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
    { key: 'period', label: 'Periode', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'total_employees', label: 'Total Karyawan', sortable: true },
    { key: 'total_net_formatted', label: 'Total Bersih', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false },
]

const rows = computed(() =>
    payrolls.data.map((p) => ({
        ...p,
        period: `${p.period_start} — ${p.period_end}`,
        total_net_formatted: formatCurrency(p.total_net ?? 0),
        // Keep status as the raw value — rendered safely via the Badge
        // component in the #cell-status slot (text interpolation escapes it).
    }))
)

const showDeleteConfirm = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const confirmDelete = (payroll) => {
    deleteTarget.value = payroll
    showDeleteConfirm.value = true
}

const deletePayroll = () => {
    if (!deleteTarget.value) return
    deleting.value = true
    router.delete(route('payroll.destroy', deleteTarget.value.id), {
        onFinish: () => {
            deleting.value = false
            showDeleteConfirm.value = false
            deleteTarget.value = null
        },
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Penggajian" description="Kelola pemrosesan dan riwayat penggajian">
            <template #actions>
                <Link :href="route('payroll.create')" class="btn-primary">
                    <PlusIcon class="w-5 h-5" />
                    Penggajian Baru
                </Link>
            </template>
        </PageHeader>

        <div class="table-container">
            <DataTable
                    v-if="payrolls.data.length"
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari penggajian..."
                    :server-side="true"
                    :total="payrolls.total"
                    :current-page="payrolls.current_page"
                    :last-page="payrolls.last_page"
                    :per-page="payrolls.per_page"
                    :filters="filters"
                    base-route="/payroll"
                >
                    <template #cell-status="{ value }">
                        <Badge :variant="statusVariant(value)">{{ value }}</Badge>
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('payroll.show', row.id)"
                                class="btn-secondary text-xs py-1.5 px-3"
                            >
                                <EyeIcon class="w-4 h-4" />
                                Lihat
                            </Link>
                            <button
                                v-if="row.status === 'draft'"
                                @click="confirmDelete(row)"
                                class="btn-danger text-xs py-1.5 px-3"
                            >
                                <TrashIcon class="w-4 h-4" />
                                Hapus
                            </button>
                        </div>
                    </template>
                </DataTable>

                <EmptyState
                    v-else
                    title="Belum ada penggajian"
                    description="Buat penggajian pertama Anda untuk memulai."
                >
                    <template #actions>
                        <Link :href="route('payroll.create')" class="btn-primary">
                            <PlusIcon class="w-5 h-5" />
                            Buat Penggajian
                        </Link>
                    </template>
                </EmptyState>

            </div>

        <ConfirmDialog
            :show="showDeleteConfirm"
            title="Hapus Penggajian"
            message="Apakah Anda yakin ingin menghapus penggajian ini? Tindakan ini tidak dapat dibatalkan."
            confirm-text="Hapus"
            confirm-variant="danger"
            :loading="deleting"
            @confirm="deletePayroll"
            @close="showDeleteConfirm = false; deleteTarget = null"
        />
    </AuthenticatedLayout>
</template>
