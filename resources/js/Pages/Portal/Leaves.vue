<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import Badge from '@/Components/Badge.vue'
import {
    CalendarDaysIcon,
    PlusIcon,
    XMarkIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const leaves = computed(() => page.props.leaves || {})
const records = computed(() => leaves.value.data || [])
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const showForm = ref(false)

const statusVariant = (status) => {
    const map = { pending: 'warning', approved: 'success', rejected: 'danger', cancelled: 'default' }
    return map[status] || 'default'
}

const statusLabel = (status) => {
    const map = { pending: 'Pending', approved: 'Disetujui', rejected: 'Ditolak', cancelled: 'Dibatalkan' }
    return map[status] || status
}

const leaveTypeLabel = (type) => {
    const map = {
        annual: 'Cuti Tahunan', sick: 'Cuti Sakit', personal: 'Cuti Pribadi',
        maternity: 'Cuti Melahirkan', paternity: 'Cuti Ayah', marriage: 'Cuti Menikah',
        bereavement: 'Cuti Duka', unpaid: 'Cuti Tanpa Dibayar',
    }
    return map[type] || type
}

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const form = useForm({
    leave_type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
})

const totalDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0
    const start = new Date(form.start_date)
    const end = new Date(form.end_date)
    return Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1)
})

const submitLeave = () => {
    form.post(route('portal.leaves.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false
            form.reset()
        },
    })
}

const openForm = () => { showForm.value = true }
const closeForm = () => {
    showForm.value = false
    form.reset()
}
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-lg">
                            <CalendarDaysIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Cuti & Izin</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ajukan dan pantau status cuti Anda</p>
                        </div>
                    </div>
                    <button v-if="!showForm" @click="openForm" class="btn-primary text-sm">
                        <PlusIcon class="w-4 h-4" />
                        Ajukan Cuti
                    </button>
                </div>
            </div>

            <!-- Leave Form -->
            <div v-if="showForm" class="glass-card p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Form Pengajuan Cuti</h3>
                    <button @click="closeForm" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <form @submit.prevent="submitLeave" class="space-y-5">
                    <div>
                        <label class="form-label">Jenis Cuti</label>
                        <select v-model="form.leave_type" class="form-input">
                            <option value="annual">Cuti Tahunan</option>
                            <option value="sick">Cuti Sakit</option>
                            <option value="personal">Cuti Pribadi</option>
                            <option value="maternity">Cuti Melahirkan</option>
                            <option value="paternity">Cuti Ayah</option>
                            <option value="marriage">Cuti Menikah</option>
                            <option value="bereavement">Cuti Duka</option>
                            <option value="unpaid">Cuti Tanpa Dibayar</option>
                        </select>
                        <p v-if="form.errors.leave_type" class="mt-1 text-xs text-red-600">{{ form.errors.leave_type }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tanggal Mulai</label>
                            <input v-model="form.start_date" type="date" class="form-input" required />
                            <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="form-label">Tanggal Selesai</label>
                            <input v-model="form.end_date" type="date" class="form-input" required :min="form.start_date" />
                            <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                        </div>
                    </div>
                    <div v-if="totalDays > 0" class="text-sm text-gray-500 dark:text-gray-400">
                        Total: <span class="font-semibold text-gray-900 dark:text-white">{{ totalDays }} hari</span>
                    </div>
                    <div>
                        <label class="form-label">Alasan</label>
                        <textarea v-model="form.reason" class="form-input" rows="3" placeholder="Jelaskan alasan cuti..." required></textarea>
                        <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button @click="closeForm" type="button" class="btn-secondary text-sm">Batal</button>
                        <button type="submit" class="btn-primary text-sm" :disabled="form.processing">
                            <PaperAirplaneIcon class="w-4 h-4" />
                            {{ form.processing ? 'Mengirim...' : 'Kirim Pengajuan' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Leave History -->
            <div class="glass-card overflow-hidden">
                <div class="p-5 pb-3">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Riwayat Pengajuan</h3>
                </div>
                <div v-if="records.length" class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="leave in records" :key="leave.id"
                        class="flex items-center justify-between p-5 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
                    >
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-10 h-10 rounded-xl flex items-center justify-center',
                                leave.status === 'approved' ? 'bg-emerald-100 dark:bg-emerald-950' :
                                leave.status === 'rejected' ? 'bg-red-100 dark:bg-red-950' :
                                'bg-amber-100 dark:bg-amber-950'
                            ]">
                                <CalendarDaysIcon :class="[
                                    'w-5 h-5',
                                    leave.status === 'approved' ? 'text-emerald-600 dark:text-emerald-400' :
                                    leave.status === 'rejected' ? 'text-red-600 dark:text-red-400' :
                                    'text-amber-600 dark:text-amber-400'
                                ]" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ leaveTypeLabel(leave.leave_type) }}</p>
                                <p class="text-xs text-gray-400">{{ formatDate(leave.start_date) }} – {{ formatDate(leave.end_date) }} · {{ leave.total_days }} hari</p>
                            </div>
                        </div>
                        <Badge :variant="statusVariant(leave.status)">{{ statusLabel(leave.status) }}</Badge>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-10">
                    Belum ada pengajuan cuti.
                </p>
            </div>
        </div>
    </component>
</template>
