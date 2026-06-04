<script setup>
import { ref, reactive } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const page = usePage()
const settings = page.props.settings || {}
const bpjsRatesProp = page.props.bpjsRates || []
const pph21BracketsProp = page.props.pph21Brackets || []

const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)

const formatNumber = (value) =>
    new Intl.NumberFormat('id-ID').format(value)

// Reactive editing state
const editingBpjs = ref(false)
const editingPph21 = ref(false)
const savingBpjs = ref(false)
const savingPph21 = ref(false)

// Local mutable copies for editing
const bpjsRates = ref([])
const pph21Brackets = ref([])

const initBpjsEditing = () => {
    bpjsRates.value = JSON.parse(JSON.stringify(bpjsRatesProp))
    editingBpjs.value = true
}

const cancelBpjs = () => {
    editingBpjs.value = false
    bpjsRates.value = []
}

const saveBpjs = () => {
    savingBpjs.value = true
    router.put(route('settings.bpjs.update'), {
        configs: bpjsRates.value.map(r => ({
            id: r.id,
            name: r.name,
            type: r.type,
            payer: r.payer,
            rate_percentage: parseFloat(r.rate_percentage),
            salary_cap: r.salary_cap ? parseFloat(r.salary_cap) : null,
            applicable_year: parseInt(r.applicable_year) || new Date().getFullYear(),
            description: r.description || '',
            is_active: r.is_active ?? true,
        }))
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingBpjs.value = false
            savingBpjs.value = false
        },
        onError: () => {
            savingBpjs.value = false
        }
    })
}

const initPph21Editing = () => {
    pph21Brackets.value = JSON.parse(JSON.stringify(pph21BracketsProp))
    editingPph21.value = true
}

const cancelPph21 = () => {
    editingPph21.value = false
    pph21Brackets.value = []
}

const savePph21 = () => {
    savingPph21.value = true
    router.put(route('settings.pph21.update'), {
        brackets: pph21Brackets.value.map(b => ({
            id: b.id,
            income_bracket_start: parseFloat(b.income_bracket_start),
            income_bracket_end: b.income_bracket_end ? parseFloat(b.income_bracket_end) : null,
            rate_percentage: parseFloat(b.rate_percentage),
            applicable_year: parseInt(b.applicable_year) || new Date().getFullYear(),
            is_active: b.is_active ?? true,
        }))
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingPph21.value = false
            savingPph21.value = false
        },
        onError: () => {
            savingPph21.value = false
        }
    })
}

const addBpjsRow = () => {
    bpjsRates.value.push({
        id: null,
        name: '',
        type: 'kesehatan',
        payer: 'company',
        rate_percentage: 0,
        salary_cap: null,
        applicable_year: new Date().getFullYear(),
        description: '',
        is_active: true,
    })
}

const removeBpjsRow = (index) => {
    bpjsRates.value.splice(index, 1)
}

const addPph21Row = () => {
    pph21Brackets.value.push({
        id: null,
        income_bracket_start: 0,
        income_bracket_end: null,
        rate_percentage: 0,
        applicable_year: new Date().getFullYear(),
        is_active: true,
    })
}

const removePph21Row = (index) => {
    pph21Brackets.value.splice(index, 1)
}

// Company info form
const form = useForm({
    company_name: settings.company_name || '',
    company_address: settings.company_address || '',
    company_phone: settings.company_phone || '',
    company_npwp: settings.company_npwp || '',
})

const submit = () => {
    form.put(route('settings.update'), {
        onSuccess: () => {
            // Optionally show success toast
        },
    })
}

const bpjsTypeOptions = ['kesehatan', 'tk_jht', 'tk_jp', 'tk_jkk', 'tk_jkm']
const bpjsPayerOptions = ['company', 'employee']
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Pengaturan" description="Kelola informasi perusahaan dan konfigurasi penggajian" />

        <div class="max-w-4xl mx-auto space-y-6 pb-8">
            <!-- Informasi Perusahaan -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Informasi Perusahaan</h3>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="company_name" class="form-label">Nama Perusahaan</label>
                        <input
                            id="company_name"
                            v-model="form.company_name"
                            type="text"
                            class="form-input"
                            placeholder="Masukkan nama perusahaan"
                            required
                        />
                        <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</p>
                    </div>

                    <div>
                        <label for="company_address" class="form-label">Alamat</label>
                        <textarea
                            id="company_address"
                            v-model="form.company_address"
                            class="form-input"
                            rows="3"
                            placeholder="Masukkan alamat perusahaan"
                        ></textarea>
                        <p v-if="form.errors.company_address" class="mt-1 text-sm text-red-600">{{ form.errors.company_address }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company_phone" class="form-label">Telepon</label>
                            <input
                                id="company_phone"
                                v-model="form.company_phone"
                                type="text"
                                class="form-input"
                                placeholder="Masukkan nomor telepon"
                            />
                            <p v-if="form.errors.company_phone" class="mt-1 text-sm text-red-600">{{ form.errors.company_phone }}</p>
                        </div>
                        <div>
                            <label for="company_npwp" class="form-label">NPWP</label>
                            <input
                                id="company_npwp"
                                v-model="form.company_npwp"
                                type="text"
                                class="form-input"
                                placeholder="Masukkan NPWP"
                            />
                            <p v-if="form.errors.company_npwp" class="mt-1 text-sm text-red-600">{{ form.errors.company_npwp }}</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Info Perusahaan' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- BPJS Rates -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tarif BPJS</h3>
                    <div class="flex gap-2">
                        <button
                            v-if="!editingBpjs"
                            @click="initBpjsEditing"
                            class="btn-secondary text-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Ubah Tarif
                        </button>
                    </div>
                </div>

                <!-- Read-only mode -->
                <div v-if="!editingBpjs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Program</th>
                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Tarif</th>
                                    <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Pembayar</th>
                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Batas Maksimal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="rate in bpjsRatesProp"
                                    :key="rate.id"
                                    class="border-b border-gray-100 dark:border-gray-800"
                                >
                                    <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">{{ rate.name }}</td>
                                    <td class="py-3 px-4 text-right text-gray-600 dark:text-gray-300">{{ rate.rate_percentage }}%</td>
                                    <td class="py-3 px-4 text-left text-gray-500 dark:text-gray-400 capitalize">{{ rate.payer }}</td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                        {{ rate.salary_cap ? formatCurrency(rate.salary_cap) : '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="!bpjsRatesProp.length" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        Tarif BPJS belum dikonfigurasi. Klik "Ubah Tarif" untuk menambahkan.
                    </p>
                </div>

                <!-- Edit mode -->
                <div v-else>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Program</th>
                                    <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Tipe</th>
                                    <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Payer</th>
                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Rate %</th>
                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Cap (Rp)</th>
                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Aktif</th>
                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400 w-10">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(rate, idx) in bpjsRates"
                                    :key="rate.id || 'new-' + idx"
                                    class="border-b border-gray-100 dark:border-gray-800"
                                >
                                    <td class="py-1.5 px-2">
                                        <input v-model="rate.name" type="text" class="form-input text-xs py-1.5 px-2 w-40" placeholder="Nama" />
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <select v-model="rate.type" class="form-input text-xs py-1.5 px-2 w-28">
                                            <option v-for="opt in bpjsTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <select v-model="rate.payer" class="form-input text-xs py-1.5 px-2 w-24">
                                            <option v-for="opt in bpjsPayerOptions" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <input v-model.number="rate.rate_percentage" type="number" step="0.01" min="0" max="100" class="form-input text-xs py-1.5 px-2 w-20 text-right" />
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <input v-model="rate.salary_cap" type="number" step="100000" min="0" class="form-input text-xs py-1.5 px-2 w-28 text-right" placeholder="—" />
                                    </td>
                                    <td class="py-1.5 px-2 text-center">
                                        <input type="checkbox" v-model="rate.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    </td>
                                    <td class="py-1.5 px-2 text-center">
                                        <button @click="removeBpjsRow(idx)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button @click="addBpjsRow" class="btn-secondary text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Baris
                        </button>
                        <div class="flex gap-2">
                            <button @click="cancelBpjs" class="btn-secondary text-sm">Batal</button>
                            <button @click="saveBpjs" class="btn-primary text-sm" :disabled="savingBpjs">
                                {{ savingBpjs ? 'Menyimpan...' : 'Simpan Tarif BPJS' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PPh21 Brackets -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bracket Pajak PPh21</h3>
                    <div class="flex gap-2">
                        <button
                            v-if="!editingPph21"
                            @click="initPph21Editing"
                            class="btn-secondary text-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Ubah Bracket
                        </button>
                    </div>
                </div>

                <!-- Read-only mode -->
                <div v-if="!editingPph21">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Bracket</th>
                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Rentang Dari</th>
                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Rentang Sampai</th>
                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Tarif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="bracket in pph21BracketsProp"
                                    :key="bracket.id"
                                    class="border-b border-gray-100 dark:border-gray-800"
                                >
                                    <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">{{ bracket.name || `Rp ${formatNumber(Number(bracket.income_bracket_start))} +` }}</td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">{{ formatCurrency(bracket.income_bracket_start) }}</td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white">
                                        {{ bracket.income_bracket_end ? formatCurrency(bracket.income_bracket_end) : 'Tidak Terbatas' }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white font-semibold">{{ bracket.rate_percentage }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="!pph21BracketsProp.length" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        Bracket PPh21 belum dikonfigurasi. Klik "Ubah Bracket" untuk menambahkan.
                    </p>
                </div>

                <!-- Edit mode -->
                <div v-else>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Dari (Rp)</th>
                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Sampai (Rp)</th>
                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Rate %</th>
                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Tahun</th>
                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400">Aktif</th>
                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400 w-10">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(bracket, idx) in pph21Brackets"
                                    :key="bracket.id || 'new-' + idx"
                                    class="border-b border-gray-100 dark:border-gray-800"
                                >
                                    <td class="py-1.5 px-2">
                                        <input v-model.number="bracket.income_bracket_start" type="number" step="1000000" min="0" class="form-input text-xs py-1.5 px-2 w-28 text-right" />
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <input v-model="bracket.income_bracket_end" type="number" step="1000000" min="0" class="form-input text-xs py-1.5 px-2 w-28 text-right" placeholder="Tidak Terbatas" />
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <input v-model.number="bracket.rate_percentage" type="number" step="0.1" min="0" max="100" class="form-input text-xs py-1.5 px-2 w-20 text-right" />
                                    </td>
                                    <td class="py-1.5 px-2">
                                        <input v-model.number="bracket.applicable_year" type="number" min="2024" max="2035" class="form-input text-xs py-1.5 px-2 w-20 text-right" />
                                    </td>
                                    <td class="py-1.5 px-2 text-center">
                                        <input type="checkbox" v-model="bracket.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    </td>
                                    <td class="py-1.5 px-2 text-center">
                                        <button @click="removePph21Row(idx)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button @click="addPph21Row" class="btn-secondary text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Baris
                        </button>
                        <div class="flex gap-2">
                            <button @click="cancelPph21" class="btn-secondary text-sm">Batal</button>
                            <button @click="savePph21" class="btn-primary text-sm" :disabled="savingPph21">
                                {{ savingPph21 ? 'Menyimpan...' : 'Simpan Bracket PPh21' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
