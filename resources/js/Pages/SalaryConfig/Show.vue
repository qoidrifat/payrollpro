<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import Badge from '@/Components/Badge.vue'
import {
    ArrowLeftIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const components = computed(() => page.props.salaryComponents || [])
const bpjsConfig = computed(() => page.props.bpjsConfigs || {})
const savingBaseSalary = ref(false)

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

// Base salary form
const baseSalaryForm = useForm({
    base_salary: employee.value?.base_salary || 0,
})

const saveBaseSalary = () => {
    baseSalaryForm.put(route('salary-config.update-base-salary', employee.value.id))
}

// Salary component CRUD
const showComponentModal = ref(false)
const editingComponent = ref(null)

const componentForm = useForm({
    type: 'allowance',
    name: '',
    amount: 0,
    is_taxable: false,
})

const openAddComponent = () => {
    editingComponent.value = null
    componentForm.reset()
    componentForm.type = 'allowance'
    componentForm.amount = 0
    componentForm.is_taxable = false
    showComponentModal.value = true
}

const openEditComponent = (comp) => {
    editingComponent.value = comp
    componentForm.type = comp.type
    componentForm.name = comp.name
    componentForm.amount = comp.amount
    componentForm.is_taxable = comp.is_taxable || false
    showComponentModal.value = true
}

const submitComponent = () => {
    if (editingComponent.value) {
        componentForm.put(
            route('salary-config.components.update', {
                employee: employee.value.id,
                component: editingComponent.value.id,
            }),
            {
                onSuccess: () => {
                    showComponentModal.value = false
                    componentForm.reset()
                },
            }
        )
    } else {
        componentForm.post(
            route('salary-config.components.store', employee.value.id),
            {
                onSuccess: () => {
                    showComponentModal.value = false
                    componentForm.reset()
                },
            }
        )
    }
}

const showDeleteConfirm = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const confirmDeleteComponent = (comp) => {
    deleteTarget.value = comp
    showDeleteConfirm.value = true
}

const deleteComponent = () => {
    if (!deleteTarget.value) return
    deleting.value = true
    router.delete(
        route('salary-config.components.destroy', {
            employee: employee.value.id,
            component: deleteTarget.value.id,
        }),
        {
            onFinish: () => {
                deleting.value = false
                showDeleteConfirm.value = false
                deleteTarget.value = null
            },
        }
    )
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="'Konfigurasi Gaji: ' + employee?.full_name" description="Kelola komponen gaji dan gaji pokok">
                <template #actions>
                    <Link :href="route('salary-config.index')" class="btn-secondary">
                        <ArrowLeftIcon class="w-5 h-5" />
                        Kembali ke Konfigurasi Gaji
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Employee Info Header -->
            <div class="glass-card p-6">
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ employee.full_name }}</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ employee.position }} — {{ employee.department }}</p>
                        </div>
                    </div>

                    <!-- Editable Base Salary -->
                    <form @submit.prevent="saveBaseSalary" class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label for="base_salary" class="form-label">Gaji Pokok</label>
                            <input
                                id="base_salary"
                                v-model.number="baseSalaryForm.base_salary"
                                type="number"
                                class="form-input max-w-xs"
                            />
                            <p v-if="baseSalaryForm.errors.base_salary" class="mt-1 text-sm text-red-600">
                                {{ baseSalaryForm.errors.base_salary }}
                            </p>
                        </div>
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="baseSalaryForm.processing"
                        >
                            {{ baseSalaryForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Salary Components Section -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Komponen Gaji</h3>
                    <button @click="openAddComponent" class="btn-primary text-sm">
                        <PlusIcon class="w-4 h-4" />
                        Tambah Komponen
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm" v-if="components.length">
                        <thead>
                            <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Tipe</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Nama</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Jumlah</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Kena Pajak</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="comp in components"
                                :key="comp.id"
                                class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/30"
                            >
                                <td class="py-3 px-4">
                                    <Badge :variant="comp.type === 'allowance' ? 'success' : 'danger'">
                                        {{ comp.type === 'allowance' ? 'Tunjangan' : 'Potongan' }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-gray-900 dark:text-white">{{ comp.name }}</td>
                                <td class="py-3 px-4 text-right"
                                    :class="comp.type === 'allowance' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ comp.type === 'deduction' ? '-' : '+' }}{{ formatCurrency(comp.amount) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <Badge :variant="comp.is_taxable ? 'warning' : 'default'">
                                        {{ comp.is_taxable ? 'Ya' : 'Tidak' }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEditComponent(comp)" class="btn-secondary text-xs py-1.5 px-3">
                                            <PencilIcon class="w-4 h-4" />
                                            Ubah
                                        </button>
                                        <button @click="confirmDeleteComponent(comp)" class="btn-danger text-xs py-1.5 px-3">
                                            <TrashIcon class="w-4 h-4" />
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                        Belum ada komponen gaji. Tambahkan tunjangan atau potongan untuk membangun struktur gaji.
                    </p>
                </div>
            </div>

            <!-- BPJS Config Reference -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Referensi Konfigurasi BPJS</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Komponen</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Tarif Pemberi Kerja</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Tarif Karyawan</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Batas Maksimal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="bpjs in bpjsConfig"
                                :key="bpjs.id"
                                class="border-b border-gray-100 dark:border-gray-800"
                            >
                                <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">{{ bpjs.name }}</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ bpjs.employer_rate }}%</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ bpjs.employee_rate }}%</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                    {{ bpjs.cap_amount ? formatCurrency(bpjs.cap_amount) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    Tarif BPJS dikonfigurasi secara global di Pengaturan. Perubahan berlaku untuk semua karyawan.
                </p>
            </div>
        </div>

        <!-- Add / Edit Component Modal -->
        <Modal
            :show="showComponentModal"
            :title="editingComponent ? 'Ubah Komponen' : 'Tambah Komponen'"
            @close="showComponentModal = false"
        >
            <form @submit.prevent="submitComponent" class="space-y-4">
                <div>
                    <label for="comp_type" class="form-label">Tipe</label>
                    <select id="comp_type" v-model="componentForm.type" class="form-input">
                        <option value="allowance">Tunjangan</option>
                        <option value="deduction">Potongan</option>
                    </select>
                    <p v-if="componentForm.errors.type" class="mt-1 text-sm text-red-600">{{ componentForm.errors.type }}</p>
                </div>
                <div>
                    <label for="comp_name" class="form-label">Nama</label>
                    <input id="comp_name" v-model="componentForm.name" type="text" class="form-input" placeholder="contoh: Tunjangan Transportasi" required />
                    <p v-if="componentForm.errors.name" class="mt-1 text-sm text-red-600">{{ componentForm.errors.name }}</p>
                </div>
                <div>
                    <label for="comp_amount" class="form-label">Jumlah (Rp)</label>
                    <input id="comp_amount" v-model.number="componentForm.amount" type="number" class="form-input" min="0" required />
                    <p v-if="componentForm.errors.amount" class="mt-1 text-sm text-red-600">{{ componentForm.errors.amount }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <input id="comp_taxable" v-model="componentForm.is_taxable" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                    <label for="comp_taxable" class="form-label !mb-0">Kena Pajak</label>
                </div>
            </form>
            <template #footer>
                <button @click="showComponentModal = false" class="btn-secondary" :disabled="componentForm.processing">Batal</button>
                <button @click="submitComponent" class="btn-primary" :disabled="componentForm.processing">
                    {{ componentForm.processing ? 'Menyimpan...' : (editingComponent ? 'Simpan' : 'Tambah') }}
                </button>
            </template>
        </Modal>

        <!-- Delete Confirm -->
        <ConfirmDialog
            :show="showDeleteConfirm"
            title="Hapus Komponen Gaji"
            :message="`Apakah Anda yakin ingin menghapus '${deleteTarget?.name}'?`"
            confirm-text="Hapus"
            confirm-variant="danger"
            :loading="deleting"
            @confirm="deleteComponent"
            @close="showDeleteConfirm = false; deleteTarget = null"
        />
    </AuthenticatedLayout>
</template>
