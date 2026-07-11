<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()
const flash = computed(() => page.props.flash || {})

// ── Role & Data ─────────────────────────────────────────────────────
const role = page.props.role || 'admin'
const isAdmin = role === 'admin'
const isHr = role === 'hr'

const companySettingsProp = page.props.companySettings || null
const attendanceSettingsProp = page.props.attendanceSettings || null
const notificationSettingsProp = page.props.notificationSettings || null
const bpjsRatesProp = page.props.bpjsRates || []
const pph21BracketsProp = page.props.pph21Brackets || []

// ── Active Section ──────────────────────────────────────────────────
const activeSection = ref('company')

const setActiveSection = (section) => {
    activeSection.value = section
    // Scroll to top on mobile
    if (window.innerWidth < 768) {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

// ── Section Definitions (role-based) ────────────────────────────────
const sections = computed(() => {
    const items = []

    // Admin: Company Profile
    if (isAdmin) {
        items.push({
            id: 'company',
            label: 'Profil Perusahaan',
            icon: '🏢',
            description: 'Informasi dan data perusahaan',
            roles: ['admin'],
        })
    }

    // Admin & HR: Attendance Operations
    if (isAdmin || isHr) {
        items.push({
            id: 'attendance',
            label: 'Operasional Absensi',
            icon: '⏰',
            description: 'Jam operasional, QR code, timezone',
            roles: ['admin', 'hr'],
        })
    }

    // Admin: Payroll & Tax
    if (isAdmin) {
        items.push({
            id: 'payroll',
            label: 'Penggajian & Pajak',
            icon: '💰',
            description: 'BPJS, PPh21, konfigurasi gaji',
            roles: ['admin'],
        })
    }

    // All: Notifications
    items.push({
        id: 'notifications',
        label: 'Notifikasi',
        icon: '🔔',
        description: 'Preferensi notifikasi email & in-app',
        roles: ['admin', 'hr', 'employee'],
    })

    return items
})

// Auto-select first available section on mount
if (sections.value.length > 0 && !sections.value.find(s => s.id === activeSection.value)) {
    activeSection.value = sections.value[0].id
}

// ── Company Profile Form ───────────────────────────────────────────
const companyForm = useForm({
    company_name: companySettingsProp?.company_name || '',
    company_address: companySettingsProp?.company_address || '',
    company_phone: companySettingsProp?.company_phone || '',
    company_npwp: companySettingsProp?.company_npwp || '',
    company_email: companySettingsProp?.company_email || '',
})

const submitCompany = () => {
    companyForm.put(route('settings.update'), {
        preserveScroll: true,
        // Adopt the just-saved values as the new baseline; reset() would revert
        // to the stale pre-save defaults captured at mount.
        onSuccess: () => companyForm.defaults(),
    })
}

// ── Attendance Settings Form ────────────────────────────────────────
const attendanceForm = useForm({
    operational_start: attendanceSettingsProp?.operational_start || '06:30',
    operational_end: attendanceSettingsProp?.operational_end || '17:00',
    qr_refresh_interval: attendanceSettingsProp?.qr_refresh_interval || 300,
    timezone: attendanceSettingsProp?.timezone || 'Asia/Jakarta',
})

const submitAttendance = () => {
    attendanceForm.put(route('settings.attendance.update'), {
        preserveScroll: true,
        onSuccess: () => attendanceForm.defaults(),
    })
}

// ── Notification Settings Form ─────────────────────────────────────
const notificationForm = useForm({
    email_notifications: notificationSettingsProp?.email_notifications ?? true,
    in_app_notifications: notificationSettingsProp?.in_app_notifications ?? true,
})

const submitNotifications = () => {
    notificationForm.put(route('settings.notifications.update'), {
        preserveScroll: true,
        onSuccess: () => notificationForm.defaults(),
    })
}

// ── BPJS Rates ─────────────────────────────────────────────────────
const editingBpjs = ref(false)
const savingBpjs = ref(false)
const bpjsRates = ref([])

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
        onSuccess: () => { editingBpjs.value = false; savingBpjs.value = false },
        onError: () => { savingBpjs.value = false },
    })
}
const addBpjsRow = () => {
    bpjsRates.value.push({
        id: null, name: '', type: 'kesehatan', payer: 'company',
        rate_percentage: 0, salary_cap: null,
        applicable_year: new Date().getFullYear(), description: '', is_active: true,
    })
}
const removeBpjsRow = (index) => bpjsRates.value.splice(index, 1)

// ── PPh21 Brackets ─────────────────────────────────────────────────
const editingPph21 = ref(false)
const savingPph21 = ref(false)
const pph21Brackets = ref([])

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
        onSuccess: () => { editingPph21.value = false; savingPph21.value = false },
        onError: () => { savingPph21.value = false },
    })
}
const addPph21Row = () => {
    pph21Brackets.value.push({
        id: null, income_bracket_start: 0, income_bracket_end: null,
        rate_percentage: 0, applicable_year: new Date().getFullYear(), is_active: true,
    })
}
const removePph21Row = (index) => pph21Brackets.value.splice(index, 1)

// ── Utilities ──────────────────────────────────────────────────────
const formatCurrency = (value) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)

const formatNumber = (value) =>
    new Intl.NumberFormat('id-ID').format(value)

const bpjsTypeOptions = ['kesehatan', 'tk_jht', 'tk_jp', 'tk_jkk', 'tk_jkm']
const bpjsPayerOptions = ['company', 'employee']
const timezoneOptions = [
    'Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura',
    'Asia/Singapore', 'Asia/Bangkok',
]

const toast = ref(null)
let toastTimeout = null

watch(flash, (val) => {
    if (val?.success) {
        toast.value = { type: 'success', message: val.success }
        clearTimeout(toastTimeout)
        toastTimeout = setTimeout(() => { toast.value = null }, 4000)
    }
    if (val?.error) {
        toast.value = { type: 'error', message: val.error }
        clearTimeout(toastTimeout)
        toastTimeout = setTimeout(() => { toast.value = null }, 5000)
    }
}, { immediate: true, deep: true })

onUnmounted(() => {
    clearTimeout(toastTimeout)
})
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto pb-24 md:pb-12">
            <!-- ── Header ─────────────────────────────────────────────── -->
            <div class="mb-6 md:mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                    Pengaturan
                </h1>
                <p class="mt-1.5 text-sm md:text-base text-gray-500 dark:text-gray-400">
                    Kelola preferensi dan konfigurasi sistem sesuai akses akun Anda.
                </p>
            </div>

            <!-- ── Toast Notification ─────────────────────────────────── -->
            <Teleport to="body">
                <Transition name="toast-slide">
                    <div
                        v-if="toast"
                        :class="[
                            'fixed top-4 right-4 z-[100] px-5 py-3.5 rounded-xl shadow-2xl backdrop-blur-md text-sm font-medium max-w-sm transition-all duration-300',
                            toast.type === 'success'
                                ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800'
                                : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
                        ]"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="text-lg">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
                            <span>{{ toast.message }}</span>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <div class="flex flex-col md:flex-row gap-6 md:gap-8">
                <!-- ── Mobile/Tablet: Section Navigation ──────────────── -->
                <nav class="md:w-64 flex-shrink-0">
                    <div class="md:sticky md:top-24 space-y-1.5">
                        <div class="hidden md:block mb-3">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                Menu Pengaturan
                            </p>
                        </div>
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            @click="setActiveSection(section.id)"
                            :class="[
                                'w-full text-left px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 flex items-center gap-3',
                                activeSection === section.id
                                    ? 'bg-primary-50 text-primary-700 shadow-sm ring-1 ring-primary-200 dark:bg-primary-950/40 dark:text-primary-300 dark:ring-primary-800/50'
                                    : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50',
                            ]"
                        >
                            <span class="text-lg flex-shrink-0">{{ section.icon }}</span>
                            <span class="truncate">{{ section.label }}</span>
                        </button>
                    </div>
                </nav>

                <!-- ── Content Area ───────────────────────────────────── -->
                <div class="flex-1 min-w-0 space-y-6">
                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- COMPANY PROFILE (Admin only) -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <div v-if="activeSection === 'company' && isAdmin" class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="px-5 py-5 md:px-6 md:py-6 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center text-lg flex-shrink-0">
                                        🏢
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profil Perusahaan</h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Informasi dasar perusahaan Anda</p>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="submitCompany" class="p-5 md:p-6 space-y-5">
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Nama Perusahaan <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="company_name"
                                        v-model="companyForm.company_name"
                                        type="text"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                        placeholder="Masukkan nama perusahaan"
                                    />
                                    <p v-if="companyForm.errors.company_name" class="mt-1.5 text-sm text-red-600">{{ companyForm.errors.company_name }}</p>
                                </div>

                                <div>
                                    <label for="company_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Alamat <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        id="company_address"
                                        v-model="companyForm.company_address"
                                        rows="3"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3 resize-none"
                                        placeholder="Masukkan alamat perusahaan"
                                    ></textarea>
                                    <p v-if="companyForm.errors.company_address" class="mt-1.5 text-sm text-red-600">{{ companyForm.errors.company_address }}</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="company_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telepon</label>
                                        <input
                                            id="company_phone"
                                            v-model="companyForm.company_phone"
                                            type="text"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                            placeholder="Nomor telepon"
                                        />
                                        <p v-if="companyForm.errors.company_phone" class="mt-1.5 text-sm text-red-600">{{ companyForm.errors.company_phone }}</p>
                                    </div>
                                    <div>
                                        <label for="company_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                                        <input
                                            id="company_email"
                                            v-model="companyForm.company_email"
                                            type="email"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                            placeholder="admin@perusahaan.com"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label for="company_npwp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">NPWP</label>
                                    <input
                                        id="company_npwp"
                                        v-model="companyForm.company_npwp"
                                        type="text"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                        placeholder="XX.XXX.XXX.X-XXX.XXX"
                                    />
                                    <p v-if="companyForm.errors.company_npwp" class="mt-1.5 text-sm text-red-600">{{ companyForm.errors.company_npwp }}</p>
                                </div>

                                <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        type="submit"
                                        :disabled="companyForm.processing"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 shadow-sm"
                                    >
                                        <svg v-if="companyForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        {{ companyForm.processing ? 'Menyimpan...' : 'Simpan Informasi Perusahaan' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- ATTENDANCE OPERATIONAL (Admin & HR) -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <div v-if="activeSection === 'attendance' && (isAdmin || isHr)" class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="px-5 py-5 md:px-6 md:py-6 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-lg flex-shrink-0">
                                        ⏰
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Operasional Absensi</h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Jam operasional, QR code, dan zona waktu</p>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="submitAttendance" class="p-5 md:p-6 space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="operational_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                            Jam Mulai <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="operational_start"
                                            v-model="attendanceForm.operational_start"
                                            type="time"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                        />
                                        <p v-if="attendanceForm.errors.operational_start" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.operational_start }}</p>
                                    </div>
                                    <div>
                                        <label for="operational_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                            Jam Selesai <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="operational_end"
                                            v-model="attendanceForm.operational_end"
                                            type="time"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                        />
                                        <p v-if="attendanceForm.errors.operational_end" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.operational_end }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="qr_refresh_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        Interval Refresh QR (detik)
                                    </label>
                                    <input
                                        id="qr_refresh_interval"
                                        v-model.number="attendanceForm.qr_refresh_interval"
                                        type="number"
                                        min="30"
                                        max="3600"
                                        step="30"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                    />
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Minimal 30 detik, maksimal 3600 detik (1 jam)</p>
                                    <p v-if="attendanceForm.errors.qr_refresh_interval" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.qr_refresh_interval }}</p>
                                </div>

                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Zona Waktu</label>
                                    <select
                                        id="timezone"
                                        v-model="attendanceForm.timezone"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm px-4 py-3"
                                    >
                                        <option v-for="tz in timezoneOptions" :key="tz" :value="tz">{{ tz }}</option>
                                    </select>
                                    <p v-if="attendanceForm.errors.timezone" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.timezone }}</p>
                                </div>

                                <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        type="submit"
                                        :disabled="attendanceForm.processing"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 shadow-sm"
                                    >
                                        <svg v-if="attendanceForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        {{ attendanceForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Absensi' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- PAYROLL & TAX (Admin only) -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <div v-if="activeSection === 'payroll' && isAdmin" class="space-y-6">
                        <!-- BPJS Rates -->
                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="px-5 py-5 md:px-6 md:py-6 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-lg flex-shrink-0">📋</div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tarif BPJS</h2>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Konfigurasi iuran BPJS Kesehatan & Ketenagakerjaan</p>
                                        </div>
                                    </div>
                                    <button v-if="!editingBpjs" @click="initBpjsEditing" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-950/50 dark:hover:bg-primary-900/50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Ubah Tarif
                                    </button>
                                </div>
                            </div>

                            <div class="p-5 md:p-6">
                                <!-- Read-only BPJS -->
                                <div v-if="!editingBpjs">
                                    <div class="overflow-x-auto -mx-5 md:-mx-6">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                                    <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Program</th>
                                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Tarif</th>
                                                    <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Pembayar</th>
                                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Batas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="rate in bpjsRatesProp" :key="rate.id" class="border-b border-gray-100 dark:border-gray-800">
                                                    <td class="py-3 px-4 text-gray-900 dark:text-white font-medium whitespace-nowrap">{{ rate.name }}</td>
                                                    <td class="py-3 px-4 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ rate.rate_percentage }}%</td>
                                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400 capitalize whitespace-nowrap">{{ rate.payer }}</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white whitespace-nowrap">{{ rate.salary_cap ? formatCurrency(rate.salary_cap) : '—' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p v-if="!bpjsRatesProp.length" class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">
                                        Tarif BPJS belum dikonfigurasi. Klik "Ubah Tarif" untuk menambahkan.
                                    </p>
                                    <div class="sm:hidden mt-4">
                                        <button @click="initBpjsEditing" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-3 rounded-xl text-sm font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-950/50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            Ubah Tarif BPJS
                                        </button>
                                    </div>
                                </div>

                                <!-- Edit BPJS -->
                                <div v-else>
                                    <div class="overflow-x-auto -mx-5 md:-mx-6">
                                        <table class="w-full text-sm min-w-[600px]">
                                            <thead>
                                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                                    <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Program</th>
                                                    <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Tipe</th>
                                                    <th class="text-left py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Payer</th>
                                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Rate %</th>
                                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Cap</th>
                                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Aktif</th>
                                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400 w-10">#</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(rate, idx) in bpjsRates" :key="rate.id || 'new-' + idx" class="border-b border-gray-100 dark:border-gray-800">
                                                    <td class="py-1.5 px-2"><input v-model="rate.name" type="text" class="w-28 md:w-36 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" placeholder="Nama" /></td>
                                                    <td class="py-1.5 px-2">
                                                        <select v-model="rate.type" class="w-24 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5">
                                                            <option v-for="opt in bpjsTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                        </select>
                                                    </td>
                                                    <td class="py-1.5 px-2">
                                                        <select v-model="rate.payer" class="w-22 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5">
                                                            <option v-for="opt in bpjsPayerOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                        </select>
                                                    </td>
                                                    <td class="py-1.5 px-2"><input v-model.number="rate.rate_percentage" type="number" step="0.01" min="0" max="100" class="w-20 text-right rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" /></td>
                                                    <td class="py-1.5 px-2"><input v-model="rate.salary_cap" type="number" step="100000" min="0" class="w-24 text-right rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" placeholder="—" /></td>
                                                    <td class="py-1.5 px-2 text-center"><input type="checkbox" v-model="rate.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" /></td>
                                                    <td class="py-1.5 px-2 text-center">
                                                        <button @click="removeBpjsRow(idx)" class="text-red-400 hover:text-red-600 transition-colors p-1">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <button @click="addBpjsRow" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            Tambah Baris
                                        </button>
                                        <div class="flex gap-2">
                                            <button @click="cancelBpjs" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">Batal</button>
                                            <button @click="saveBpjs" :disabled="savingBpjs" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 transition-colors">
                                                {{ savingBpjs ? 'Menyimpan...' : 'Simpan' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PPh21 Brackets -->
                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="px-5 py-5 md:px-6 md:py-6 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-lg flex-shrink-0">📊</div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bracket Pajak PPh21</h2>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Lapisan tarif pajak penghasilan progresif</p>
                                        </div>
                                    </div>
                                    <button v-if="!editingPph21" @click="initPph21Editing" class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-950/50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Ubah Bracket
                                    </button>
                                </div>
                            </div>

                            <div class="p-5 md:p-6">
                                <div v-if="!editingPph21">
                                    <div class="overflow-x-auto -mx-5 md:-mx-6">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                                    <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Bracket</th>
                                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Dari</th>
                                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Sampai</th>
                                                    <th class="text-right py-3 px-4 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Tarif</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="bracket in pph21BracketsProp" :key="bracket.id" class="border-b border-gray-100 dark:border-gray-800">
                                                    <td class="py-3 px-4 text-gray-900 dark:text-white font-medium whitespace-nowrap">{{ bracket.name || `Rp ${formatNumber(Number(bracket.income_bracket_start))} +` }}</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white whitespace-nowrap">{{ formatCurrency(bracket.income_bracket_start) }}</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white whitespace-nowrap">{{ bracket.income_bracket_end ? formatCurrency(bracket.income_bracket_end) : '∞' }}</td>
                                                    <td class="py-3 px-4 text-right text-gray-900 dark:text-white font-semibold whitespace-nowrap">{{ bracket.rate_percentage }}%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p v-if="!pph21BracketsProp.length" class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">
                                        Bracket PPh21 belum dikonfigurasi. Klik "Ubah Bracket" untuk menambahkan.
                                    </p>
                                    <div class="sm:hidden mt-4">
                                        <button @click="initPph21Editing" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-3 rounded-xl text-sm font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 dark:text-primary-300 dark:bg-primary-950/50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            Ubah Bracket PPh21
                                        </button>
                                    </div>
                                </div>

                                <div v-else>
                                    <div class="overflow-x-auto -mx-5 md:-mx-6">
                                        <table class="w-full text-sm min-w-[500px]">
                                            <thead>
                                                <tr class="border-y border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Dari (Rp)</th>
                                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Sampai (Rp)</th>
                                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Rate %</th>
                                                    <th class="text-right py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Tahun</th>
                                                    <th class="text-center py-2 px-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Aktif</th>
                                                    <th class="text-center py-2 px-2 w-10">#</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(bracket, idx) in pph21Brackets" :key="bracket.id || 'new-' + idx" class="border-b border-gray-100 dark:border-gray-800">
                                                    <td class="py-1.5 px-2"><input v-model.number="bracket.income_bracket_start" type="number" step="1000000" min="0" class="w-24 text-right rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" /></td>
                                                    <td class="py-1.5 px-2"><input v-model="bracket.income_bracket_end" type="number" step="1000000" min="0" class="w-24 text-right rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" placeholder="∞" /></td>
                                                    <td class="py-1.5 px-2"><input v-model.number="bracket.rate_percentage" type="number" step="0.1" min="0" max="100" class="w-16 text-right rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" /></td>
                                                    <td class="py-1.5 px-2"><input v-model.number="bracket.applicable_year" type="number" min="2024" max="2035" class="w-20 text-right rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-sm px-2.5 py-1.5" /></td>
                                                    <td class="py-1.5 px-2 text-center"><input type="checkbox" v-model="bracket.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" /></td>
                                                    <td class="py-1.5 px-2 text-center"><button @click="removePph21Row(idx)" class="text-red-400 hover:text-red-600 transition-colors p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <button @click="addPph21Row" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            Tambah Baris
                                        </button>
                                        <div class="flex gap-2">
                                            <button @click="cancelPph21" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">Batal</button>
                                            <button @click="savePph21" :disabled="savingPph21" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 transition-colors">
                                                {{ savingPph21 ? 'Menyimpan...' : 'Simpan' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════════════════════════════════════════════════ -->
                    <!-- NOTIFICATIONS (All Roles) -->
                    <!-- ════════════════════════════════════════════════════ -->
                    <div v-if="activeSection === 'notifications'" class="space-y-6">
                        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                            <div class="px-5 py-5 md:px-6 md:py-6 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/50 flex items-center justify-center text-lg flex-shrink-0">🔔</div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Preferensi Notifikasi</h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Atur bagaimana notifikasi dikirimkan kepada Anda</p>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="submitNotifications" class="p-5 md:p-6 space-y-5">
                                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Notifikasi Email</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Terima notifikasi melalui email</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="notificationForm.email_notifications" class="sr-only peer" />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Notifikasi In-App</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tampilkan notifikasi di dalam aplikasi</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="notificationForm.in_app_notifications" class="sr-only peer" />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    </label>
                                </div>

                                <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                                    <button
                                        type="submit"
                                        :disabled="notificationForm.processing"
                                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 shadow-sm"
                                    >
                                        <svg v-if="notificationForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        {{ notificationForm.processing ? 'Menyimpan...' : 'Simpan Preferensi Notifikasi' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ── Empty State ──────────────────────────────────── -->
                    <div v-if="activeSection && !sections.find(s => s.id === activeSection)" class="text-center py-12">
                        <p class="text-gray-400 dark:text-gray-500">Pilih menu pengaturan di samping untuk memulai.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Unscoped: Teleport moves toast to body, so styles must not be scoped */
.toast-slide-enter-active {
    transition: all 0.3s ease-out;
}
.toast-slide-leave-active {
    transition: all 0.2s ease-in;
}
.toast-slide-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.toast-slide-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>
