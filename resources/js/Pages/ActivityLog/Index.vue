<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const logs = computed(() => page.props.logs || { data: [] })

const selectedAction = ref(page.props.filters?.action || '')

const actionOptions = [
    { value: '', label: 'Semua Aksi' },
    { value: 'create', label: 'Buat' },
    { value: 'update', label: 'Perbarui' },
    { value: 'delete', label: 'Hapus' },
    { value: 'process', label: 'Proses' },
    { value: 'approve', label: 'Setujui' },
    { value: 'login', label: 'Masuk' },
]

const actionVariant = (action) => {
    const map = {
        create: 'success',
        update: 'info',
        delete: 'danger',
        process: 'primary',
        approve: 'success',
        login: 'default',
    }
    return map[action] || 'default'
}

const columns = [
    { key: 'created_at', label: 'Tanggal', sortable: true },
    { key: 'user_name', label: 'Pengguna', sortable: true },
    { key: 'action', label: 'Aksi', sortable: true },
    { key: 'description', label: 'Deskripsi', sortable: true },
]

const rows = computed(() =>
    logs.value.data.map((log) => ({
        ...log,
        user_name: log.user?.name || 'Sistem',
        created_at: log.created_at_formatted || log.created_at,
    }))
)

const applyFilter = () => {
    router.get(
        route('activity-log.index'),
        { action: selectedAction.value },
        {
            preserveState: true,
            replace: true,
        }
    )
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Log Aktivitas" description="Pantau aksi dan perubahan pengguna di seluruh sistem" />

        <div class="space-y-6">
            <!-- Filter Aksi -->
            <div class="glass-card p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Filter berdasarkan Tipe Aksi</h3>
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label for="action" class="form-label">Tipe Aksi</label>
                        <select id="action" v-model="selectedAction" class="form-input" @change="applyFilter">
                            <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Log Aktivitas Table -->
            <div class="table-container">
                <DataTable
                    v-if="logs.data.length"
                    :columns="columns"
                    :rows="rows"
                    search-placeholder="Cari log aktivitas..."
                >
                    <template #cell-action="{ value }">
                        <Badge :variant="actionVariant(value)">{{ value }}</Badge>
                    </template>
                    <template #cell-description="{ value }">
                        <span class="text-gray-600 dark:text-gray-300">{{ value }}</span>
                    </template>
                </DataTable>
                <EmptyState
                    v-else
                    title="Belum ada log aktivitas"
                    description="Log aktivitas akan muncul di sini saat pengguna melakukan tindakan."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
