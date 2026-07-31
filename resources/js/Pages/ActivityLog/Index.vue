<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { MagnifyingGlassIcon, ClockIcon } from '@heroicons/vue/24/outline'

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

        <div class="space-y-6 animate-fade-in">
            <!-- Filter Card -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-sm">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Filter Log Aktivitas</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Saring berdasarkan tipe aksi</p>
                    </div>
                </div>
                <div class="p-5">
                    <form @submit.prevent="applyFilter" class="flex flex-wrap items-end gap-4">
                        <div class="min-w-[200px]">
                            <label for="action" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tipe Aksi</label>
                            <select id="action" v-model="selectedAction" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all" @change="applyFilter">
                                <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Log Table -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-sm">
                        <ClockIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Riwayat Aktivitas</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ logs.data?.length || 0 }} log ditemukan</p>
                    </div>
                </div>
                <div class="p-6">
                    <DataTable
                        v-if="logs.data?.length"
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
        </div>
    </AuthenticatedLayout>
</template>
