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
        status: `<span class="badge bg-${statusVariant(p.status)}">${p.status}</span>`,
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
                >
                    <template #cell-status="{ value }">
                        <span v-html="value"></span>
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

                <!-- Pagination -->
                <div
                    v-if="payrolls.meta && payrolls.meta.total > 0"
                    class="flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan {{ payrolls.meta.from || 0 }} sampai {{ payrolls.meta.to || 0 }} dari {{ payrolls.meta.total }} hasil
                    </p>
                    <div class="flex gap-2" v-if="payrolls.meta.links">
                        <button
                            v-for="link in payrolls.meta.links"
                            :key="link.label"
                            :disabled="!link.url || link.active"
                            :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors',
                                link.active ? 'bg-primary-600 text-white' : link.url
                                    ? 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed',
                            ]"
                            v-html="link.label"
                            @click="link.url && router.visit(link.url)"
                        ></button>
                    </div>
                </div>
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
