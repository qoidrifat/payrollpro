<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    BuildingOffice2Icon,
    ClockIcon,
    CurrencyDollarIcon,
    BellIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    ChevronRightIcon,
    InformationCircleIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline'

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
    if (window.innerWidth < 768) {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

// ── Section Definitions ─────────────────────────────────────────────
const sections = computed(() => {
    const items = []
    if (isAdmin) {
        items.push({
            id: 'company',
            label: 'Profil Perusahaan',
            icon: BuildingOffice2Icon,
            gradient: 'from-blue-500 to-indigo-600',
            description: 'Informasi dan data perusahaan',
        })
    }
    if (isAdmin || isHr) {
        items.push({
            id: 'attendance',
            label: 'Operasional Absensi',
            icon: ClockIcon,
            gradient: 'from-amber-500 to-orange-600',
            description: 'Jam operasional, QR code, timezone',
        })
    }
    if (isAdmin) {
        items.push({
            id: 'payroll',
            label: 'Penggajian & Pajak',
            icon: CurrencyDollarIcon,
            gradient: 'from-emerald-500 to-teal-600',
            description: 'BPJS, PPh21, konfigurasi gaji',
        })
    }
    items.push({
        id: 'notifications',
        label: 'Notifikasi',
        icon: BellIcon,
        gradient: 'from-sky-500 to-blue-600',
        description: 'Preferensi notifikasi email & in-app',
    })
    return items
})

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

// ── Notification Settings ─────────────────────────────────────────
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
const cancelBpjs = () => { editingBpjs.value = false; bpjsRates.value = [] }
const saveBpjs = () => {
    savingBpjs.value = true
    router.put(route('settings.bpjs.update'), {
        configs: bpjsRates.value.map(r => ({
            id: r.id, name: r.name, type: r.type, payer: r.payer,
            rate_percentage: parseFloat(r.rate_percentage),
            salary_cap: r.salary_cap ? parseFloat(r.salary_cap) : null,
            applicable_year: parseInt(r.applicable_year) || new Date().getFullYear(),
            description: r.description || '', is_active: r.is_active ?? true,
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
const cancelPph21 = () => { editingPph21.value = false; pph21Brackets.value = [] }
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

// ── Toast Notification ─────────────────────────────────────────────
const toast = ref(null)
let toastTimeout = null

watch(flash, (val) => {
    if (val?.success) {
        toast.value = { type: 'success', message: val.success, icon: CheckCircleIcon }
        clearTimeout(toastTimeout)
        toastTimeout = setTimeout(() => { toast.value = null }, 4000)
    }
    if (val?.error) {
        toast.value = { type: 'error', message: val.error, icon: XCircleIcon }
        clearTimeout(toastTimeout)
        toastTimeout = setTimeout(() => { toast.value = null }, 5000)
    }
}, { immediate: true, deep: true })

onUnmounted(() => { clearTimeout(toastTimeout) })
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto pb-24 md:pb-12">
            <!-- ── Premium Header ─────────────────────────────────── -->
            <div class="mb-8">
                <div class="flex items-center gap-3.5 mb-2">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/20 ring-2 ring-white/60 dark:ring-gray-900/60">
                        <Cog6ToothIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">Pengaturan</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola preferensi dan konfigurasi sistem</p>
                    </div>
                </div>
            </div>

            <!-- ── Premium Toast ──────────────────────────────────── -->
            <Teleport to="body">
                <Transition name="toast-slide">
                    <div v-if="toast"
                        :class="[
                            'fixed top-5 right-5 z-[100] flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl backdrop-blur-xl text-sm font-medium max-w-sm border transition-all duration-300',
                            toast.type === 'success'
                                ? 'bg-emerald-50/95 text-emerald-800 border-emerald-200/70 dark:bg-emerald-950/90 dark:text-emerald-300 dark:border-emerald-800/60'
                                : 'bg-red-50/95 text-red-800 border-red-200/70 dark:bg-red-950/90 dark:text-red-300 dark:border-red-800/60',
                        ]"
                    >
                        <div :class="['w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0',
                            toast.type === 'success' ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-red-100 dark:bg-red-900']">
                            <component :is="toast.icon" class="w-5 h-5" />
                        </div>
                        <span>{{ toast.message }}</span>
                    </div>
                </Transition>
            </Teleport>

            <div class="flex flex-col md:flex-row gap-8">
                <!-- ── Premium Section Navigation ─────────────────── -->
                <nav class="md:w-64 flex-shrink-0">
                    <div class="md:sticky md:top-24 space-y-1.5">
                        <div class="hidden md:block mb-4 px-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-gray-500">Menu Pengaturan</p>
                        </div>

                        <button
                            v-for="section in sections"
                            :key="section.id"
                            @click="setActiveSection(section.id)"
                            :class="[
                                'w-full text-left px-4 py-3.5 rounded-2xl text-sm font-medium transition-all duration-300 flex items-center gap-3.5 group relative overflow-hidden',
                                activeSection === section.id
                                    ? 'bg-white text-gray-900 shadow-md shadow-gray-200/50 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-white dark:ring-gray-700'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-white/70 dark:hover:bg-gray-900/50',
                            ]"
                        >
                            <!-- Active indicator bar -->
                            <div v-if="activeSection === section.id"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 rounded-r-full"
                                :class="section.gradient"
                            />

                            <!-- Icon container -->
                            <div :class="[
                                'w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-300',
                                activeSection === section.id
                                    ? 'shadow-sm ring-1 ring-gray-200 dark:ring-gray-700'
                                    : 'bg-gray-100 dark:bg-gray-800 group-hover:bg-white dark:group-hover:bg-gray-800',
                            ]">
                                <component :is="section.icon" :class="[
                                    'w-5 h-5 transition-colors',
                                    activeSection === section.id ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500',
                                ]" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold truncate">{{ section.label }}</p>
                                <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ section.description }}</p>
                            </div>

                            <ChevronRightIcon :class="[
                                'w-4 h-4 flex-shrink-0 transition-all duration-300',
                                activeSection === section.id ? 'text-gray-400 rotate-90' : 'text-gray-300 dark:text-gray-600',
                            ]" />
                        </button>
                    </div>
                </nav>

                <!-- ── Content Area ───────────────────────────────── -->
                <div class="flex-1 min-w-0">
                    <Transition name="section-fade" mode="out-in">
                        <div :key="activeSection" class="space-y-6">

                            <!-- ════════════════════════════════════════ -->
                            <!-- COMPANY PROFILE -->
                            <!-- ════════════════════════════════════════ -->
                            <div v-if="activeSection === 'company' && isAdmin">
                                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                                    <!-- Premium header with gradient -->
                                    <div class="relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/50 to-indigo-50/30 dark:from-blue-950/20 dark:to-transparent" />
                                        <div class="relative px-6 py-6 md:px-8 md:py-7 border-b border-gray-100 dark:border-gray-800">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 flex-shrink-0">
                                                    <BuildingOffice2Icon class="w-6 h-6 text-white" />
                                                </div>
                                                <div>
                                                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Profil Perusahaan</h2>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Informasi dasar perusahaan Anda</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form @submit.prevent="submitCompany" class="px-6 py-6 md:px-8 md:py-7 space-y-6">
                                        <div class="grid grid-cols-1 gap-6">
                                            <div>
                                                <label for="company_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nama Perusahaan <span class="text-red-500">*</span></label>
                                                <input id="company_name" v-model="companyForm.company_name" type="text"
                                                    class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-blue-500 focus:ring-blue-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200"
                                                    placeholder="Masukkan nama perusahaan" />
                                                <p v-if="companyForm.errors.company_name" class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><InformationCircleIcon class="w-4 h-4" />{{ companyForm.errors.company_name }}</p>
                                            </div>

                                            <div>
                                                <label for="company_address" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Alamat <span class="text-red-500">*</span></label>
                                                <textarea id="company_address" v-model="companyForm.company_address" rows="3"
                                                    class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-blue-500 focus:ring-blue-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200 resize-none"
                                                    placeholder="Masukkan alamat perusahaan" />
                                                <p v-if="companyForm.errors.company_address" class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><InformationCircleIcon class="w-4 h-4" />{{ companyForm.errors.company_address }}</p>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                                <div>
                                                    <label for="company_phone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Telepon</label>
                                                    <input id="company_phone" v-model="companyForm.company_phone" type="text"
                                                        class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-blue-500 focus:ring-blue-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200"
                                                        placeholder="Nomor telepon" />
                                                </div>
                                                <div>
                                                    <label for="company_email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email</label>
                                                    <input id="company_email" v-model="companyForm.company_email" type="email"
                                                        class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-blue-500 focus:ring-blue-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200"
                                                        placeholder="admin@perusahaan.com" />
                                                </div>
                                            </div>

                                            <div>
                                                <label for="company_npwp" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">NPWP</label>
                                                <input id="company_npwp" v-model="companyForm.company_npwp" type="text"
                                                    class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-blue-500 focus:ring-blue-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200"
                                                    placeholder="XX.XXX.XXX.X-XXX.XXX" />
                                                <p v-if="companyForm.errors.company_npwp" class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><InformationCircleIcon class="w-4 h-4" />{{ companyForm.errors.company_npwp }}</p>
                                            </div>
                                        </div>

                                        <div class="pt-5 border-t border-gray-100 dark:border-gray-800">
                                            <button type="submit" :disabled="companyForm.processing"
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-blue-500/20 active:scale-[0.98]">
                                                <svg v-if="companyForm.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>
                                                {{ companyForm.processing ? 'Menyimpan...' : 'Simpan Informasi Perusahaan' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════════ -->
                            <!-- ATTENDANCE OPERATIONAL -->
                            <!-- ════════════════════════════════════════ -->
                            <div v-if="activeSection === 'attendance' && (isAdmin || isHr)">
                                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                                    <div class="relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-amber-50/50 to-orange-50/30 dark:from-amber-950/20 dark:to-transparent" />
                                        <div class="relative px-6 py-6 md:px-8 md:py-7 border-b border-gray-100 dark:border-gray-800">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 flex-shrink-0">
                                                    <ClockIcon class="w-6 h-6 text-white" />
                                                </div>
                                                <div>
                                                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Operasional Absensi</h2>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Jam operasional, QR code, dan zona waktu</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form @submit.prevent="submitAttendance" class="px-6 py-6 md:px-8 md:py-7 space-y-6">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                            <div>
                                                <label for="operational_start" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Jam Mulai <span class="text-red-500">*</span></label>
                                                <input id="operational_start" v-model="attendanceForm.operational_start" type="time"
                                                    class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200" />
                                                <p v-if="attendanceForm.errors.operational_start" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.operational_start }}</p>
                                            </div>
                                            <div>
                                                <label for="operational_end" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Jam Selesai <span class="text-red-500">*</span></label>
                                                <input id="operational_end" v-model="attendanceForm.operational_end" type="time"
                                                    class="block w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200" />
                                                <p v-if="attendanceForm.errors.operational_end" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.operational_end }}</p>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="qr_refresh_interval" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Interval Refresh QR (detik)</label>
                                            <input id="qr_refresh_interval" v-model.number="attendanceForm.qr_refresh_interval" type="number" min="30" max="3600" step="30"
                                                class="block w-full max-w-xs rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200" />
                                            <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1"><InformationCircleIcon class="w-3.5 h-3.5" />Minimal 30 detik, maksimal 3600 detik (1 jam)</p>
                                            <p v-if="attendanceForm.errors.qr_refresh_interval" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.qr_refresh_interval }}</p>
                                        </div>

                                        <div>
                                            <label for="timezone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Zona Waktu</label>
                                            <select id="timezone" v-model="attendanceForm.timezone"
                                                class="block w-full max-w-xs rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500/20 focus:ring-4 text-sm px-4 py-3.5 transition-all duration-200">
                                                <option v-for="tz in timezoneOptions" :key="tz" :value="tz">{{ tz.replace('_', ' ') }}</option>
                                            </select>
                                            <p v-if="attendanceForm.errors.timezone" class="mt-1.5 text-sm text-red-600">{{ attendanceForm.errors.timezone }}</p>
                                        </div>

                                        <div class="pt-5 border-t border-gray-100 dark:border-gray-800">
                                            <button type="submit" :disabled="attendanceForm.processing"
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-amber-500/20 active:scale-[0.98]">
                                                <svg v-if="attendanceForm.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>
                                                {{ attendanceForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Absensi' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════════ -->
                            <!-- PAYROLL & TAX -->
                            <!-- ════════════════════════════════════════ -->
                            <div v-if="activeSection === 'payroll' && isAdmin" class="space-y-6">
                                <!-- BPJS Rates -->
                                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                                    <div class="relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-50/50 to-teal-50/30 dark:from-emerald-950/20 dark:to-transparent" />
                                        <div class="relative px-6 py-6 md:px-8 md:py-7 border-b border-gray-100 dark:border-gray-800">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 flex-shrink-0">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                                    </div>
                                                    <div>
                                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tarif BPJS</h2>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Konfigurasi iuran BPJS Kesehatan &amp; Ketenagakerjaan</p>
                                                    </div>
                                                </div>
                                                <button v-if="!editingBpjs" @click="initBpjsEditing"
                                                    class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 transition-all duration-200 shadow-sm ring-1 ring-emerald-200/50 dark:ring-emerald-800/30 hover:shadow-md active:scale-[0.97]">
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                    Ubah Tarif
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-6 md:p-8">
                                        <!-- Read-only -->
                                        <div v-if="!editingBpjs" class="space-y-4">
                                            <div class="overflow-x-auto -mx-6 md:-mx-8">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-y border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950/50">
                                                            <th class="text-left py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Program</th>
                                                            <th class="text-right py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Tarif</th>
                                                            <th class="text-left py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Pembayar</th>
                                                            <th class="text-right py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Batas Gaji</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="rate in bpjsRatesProp" :key="rate.id"
                                                            class="border-b border-gray-50 dark:border-gray-800/50 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                            <td class="py-3.5 px-6 text-gray-900 dark:text-white font-medium">{{ rate.name }}</td>
                                                            <td class="py-3.5 px-6 text-right">
                                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">{{ rate.rate_percentage }}%</span>
                                                            </td>
                                                            <td class="py-3.5 px-6">
                                                                <span class="inline-flex items-center gap-1.5 capitalize text-gray-600 dark:text-gray-300">
                                                                    <span :class="['w-1.5 h-1.5 rounded-full', rate.payer === 'company' ? 'bg-blue-500' : 'bg-amber-500']" />
                                                                    {{ rate.payer === 'company' ? 'Perusahaan' : 'Karyawan' }}
                                                                </span>
                                                            </td>
                                                            <td class="py-3.5 px-6 text-right font-medium text-gray-900 dark:text-white">{{ rate.salary_cap ? formatCurrency(rate.salary_cap) : '—' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p v-if="!bpjsRatesProp.length" class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">Tarif BPJS belum dikonfigurasi. Klik "Ubah Tarif" untuk menambahkan.</p>
                                            <div class="sm:hidden">
                                                <button @click="initBpjsEditing" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/60 transition-colors ring-1 ring-emerald-200/50 dark:ring-emerald-800/30">
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                    Ubah Tarif BPJS
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Edit mode -->
                                        <div v-else class="space-y-4">
                                            <div class="overflow-x-auto -mx-6 md:-mx-8">
                                                <table class="w-full text-sm min-w-[650px]">
                                                    <thead>
                                                        <tr class="border-y border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950/50">
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Program</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Tipe</th>
                                                            <th class="text-left py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Payer</th>
                                                            <th class="text-right py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Rate %</th>
                                                            <th class="text-right py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Cap</th>
                                                            <th class="text-center py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Aktif</th>
                                                            <th class="text-center py-3 px-4 w-12"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(rate, idx) in bpjsRates" :key="rate.id || 'new-' + idx"
                                                            class="border-b border-gray-50 dark:border-gray-800/50">
                                                            <td class="py-2 px-4"><input v-model="rate.name" type="text" class="w-28 md:w-36 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-3 transition-all" placeholder="Nama" /></td>
                                                            <td class="py-2 px-4">
                                                                <select v-model="rate.type" class="w-24 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-2.5 py-2 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-3">
                                                                    <option v-for="opt in bpjsTypeOptions" :key="opt" :value="opt">{{ opt.replace('_', ' ') }}</option>
                                                                </select>
                                                            </td>
                                                            <td class="py-2 px-4">
                                                                <select v-model="rate.payer" class="w-22 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-2.5 py-2 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-3">
                                                                    <option v-for="opt in bpjsPayerOptions" :key="opt" :value="opt">{{ opt }}</option>
                                                                </select>
                                                            </td>
                                                            <td class="py-2 px-4"><input v-model.number="rate.rate_percentage" type="number" step="0.01" min="0" max="100" class="w-20 text-right rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-3" /></td>
                                                            <td class="py-2 px-4"><input v-model="rate.salary_cap" type="number" step="100000" min="0" class="w-24 text-right rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-3" placeholder="—" /></td>
                                                            <td class="py-2 px-4 text-center">
                                                                <label class="relative inline-flex items-center cursor-pointer">
                                                                    <input type="checkbox" v-model="rate.is_active" class="sr-only peer" />
                                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                                                                </label>
                                                            </td>
                                                            <td class="py-2 px-4 text-center">
                                                                <button @click="removeBpjsRow(idx)" class="text-red-400 hover:text-red-600 transition-colors p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/50"><TrashIcon class="w-4 h-4" /></button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                                                <button @click="addBpjsRow" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200 active:scale-[0.97]">
                                                    <PlusIcon class="w-4 h-4" /> Tambah Baris
                                                </button>
                                                <div class="flex gap-2">
                                                    <button @click="cancelBpjs" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200">Batal</button>
                                                    <button @click="saveBpjs" :disabled="savingBpjs" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50 transition-all duration-200 shadow-md active:scale-[0.97]">
                                                        {{ savingBpjs ? 'Menyimpan...' : 'Simpan Tarif BPJS' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PPh21 Brackets -->
                                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                                    <div class="relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-purple-50/50 to-violet-50/30 dark:from-purple-950/20 dark:to-transparent" />
                                        <div class="relative px-6 py-6 md:px-8 md:py-7 border-b border-gray-100 dark:border-gray-800">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center shadow-lg shadow-purple-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 flex-shrink-0">
                                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                                    </div>
                                                    <div>
                                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Bracket Pajak PPh21</h2>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Lapisan tarif pajak penghasilan progresif</p>
                                                    </div>
                                                </div>
                                                <button v-if="!editingPph21" @click="initPph21Editing"
                                                    class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 dark:text-purple-300 dark:bg-purple-950/60 dark:hover:bg-purple-900/60 transition-all duration-200 shadow-sm ring-1 ring-purple-200/50 dark:ring-purple-800/30 hover:shadow-md active:scale-[0.97]">
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                    Ubah Bracket
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-6 md:p-8">
                                        <div v-if="!editingPph21" class="space-y-4">
                                            <div class="overflow-x-auto -mx-6 md:-mx-8">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-y border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950/50">
                                                            <th class="text-left py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Bracket</th>
                                                            <th class="text-right py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Dari</th>
                                                            <th class="text-right py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Sampai</th>
                                                            <th class="text-right py-3.5 px-6 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[11px]">Tarif</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="bracket in pph21BracketsProp" :key="bracket.id"
                                                            class="border-b border-gray-50 dark:border-gray-800/50 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                                            <td class="py-3.5 px-6 text-gray-900 dark:text-white font-medium">{{ bracket.name || `Rp ${formatNumber(Number(bracket.income_bracket_start))}+` }}</td>
                                                            <td class="py-3.5 px-6 text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(bracket.income_bracket_start) }}</td>
                                                            <td class="py-3.5 px-6 text-right font-medium text-gray-900 dark:text-white">{{ bracket.income_bracket_end ? formatCurrency(bracket.income_bracket_end) : '∞' }}</td>
                                                            <td class="py-3.5 px-6 text-right">
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-400 text-xs font-bold">{{ bracket.rate_percentage }}%</span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <p v-if="!pph21BracketsProp.length" class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">Bracket PPh21 belum dikonfigurasi. Klik "Ubah Bracket" untuk menambahkan.</p>
                                            <div class="sm:hidden">
                                                <button @click="initPph21Editing" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 dark:text-purple-300 dark:bg-purple-950/60 transition-colors ring-1 ring-purple-200/50 dark:ring-purple-800/30">
                                                    <PencilSquareIcon class="w-4 h-4" /> Ubah Bracket PPh21
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Edit PPh21 -->
                                        <div v-else class="space-y-4">
                                            <div class="overflow-x-auto -mx-6 md:-mx-8">
                                                <table class="w-full text-sm min-w-[550px]">
                                                    <thead>
                                                        <tr class="border-y border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950/50">
                                                            <th class="text-right py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Dari (Rp)</th>
                                                            <th class="text-right py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Sampai (Rp)</th>
                                                            <th class="text-right py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Rate %</th>
                                                            <th class="text-right py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Tahun</th>
                                                            <th class="text-center py-3 px-4 font-semibold text-gray-500 dark:text-gray-400 text-[11px] uppercase tracking-wider">Aktif</th>
                                                            <th class="text-center py-3 px-4 w-12"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(bracket, idx) in pph21Brackets" :key="bracket.id || 'new-' + idx"
                                                            class="border-b border-gray-50 dark:border-gray-800/50">
                                                            <td class="py-2 px-4"><input v-model.number="bracket.income_bracket_start" type="number" step="1000000" min="0" class="w-24 text-right rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-purple-500 focus:ring-purple-500/20 focus:ring-3" /></td>
                                                            <td class="py-2 px-4"><input v-model="bracket.income_bracket_end" type="number" step="1000000" min="0" class="w-24 text-right rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-purple-500 focus:ring-purple-500/20 focus:ring-3" placeholder="∞" /></td>
                                                            <td class="py-2 px-4"><input v-model.number="bracket.rate_percentage" type="number" step="0.1" min="0" max="100" class="w-16 text-right rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-purple-500 focus:ring-purple-500/20 focus:ring-3" /></td>
                                                            <td class="py-2 px-4"><input v-model.number="bracket.applicable_year" type="number" min="2024" max="2035" class="w-20 text-right rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm px-3 py-2 focus:border-purple-500 focus:ring-purple-500/20 focus:ring-3" /></td>
                                                            <td class="py-2 px-4 text-center">
                                                                <label class="relative inline-flex items-center cursor-pointer">
                                                                    <input type="checkbox" v-model="bracket.is_active" class="sr-only peer" />
                                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                                                </label>
                                                            </td>
                                                            <td class="py-2 px-4 text-center">
                                                                <button @click="removePph21Row(idx)" class="text-red-400 hover:text-red-600 transition-colors p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/50"><TrashIcon class="w-4 h-4" /></button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                                                <button @click="addPph21Row" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200 active:scale-[0.97]">
                                                    <PlusIcon class="w-4 h-4" /> Tambah Baris
                                                </button>
                                                <div class="flex gap-2">
                                                    <button @click="cancelPph21" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200">Batal</button>
                                                    <button @click="savePph21" :disabled="savingPph21" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 disabled:opacity-50 transition-all duration-200 shadow-md active:scale-[0.97]">
                                                        {{ savingPph21 ? 'Menyimpan...' : 'Simpan Bracket PPh21' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ════════════════════════════════════════ -->
                            <!-- NOTIFICATIONS -->
                            <!-- ════════════════════════════════════════ -->
                            <div v-if="activeSection === 'notifications'">
                                <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
                                    <div class="relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-sky-50/50 to-blue-50/30 dark:from-sky-950/20 dark:to-transparent" />
                                        <div class="relative px-6 py-6 md:px-8 md:py-7 border-b border-gray-100 dark:border-gray-800">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-lg shadow-sky-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 flex-shrink-0">
                                                    <BellIcon class="w-6 h-6 text-white" />
                                                </div>
                                                <div>
                                                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Preferensi Notifikasi</h2>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Atur bagaimana notifikasi dikirimkan kepada Anda</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <form @submit.prevent="submitNotifications" class="px-6 py-6 md:px-8 md:py-7 space-y-5">
                                        <div class="flex items-center justify-between p-5 rounded-2xl bg-gray-50 dark:bg-gray-950/80 border border-gray-200/80 dark:border-gray-800/80 hover:border-gray-300 dark:hover:border-gray-700 transition-colors">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/50 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Notifikasi Email</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Terima notifikasi melalui email</p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                                <input type="checkbox" v-model="notificationForm.email_notifications" class="sr-only peer" />
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sky-300 dark:peer-focus:ring-sky-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-sky-600"></div>
                                            </label>
                                        </div>

                                        <div class="flex items-center justify-between p-5 rounded-2xl bg-gray-50 dark:bg-gray-950/80 border border-gray-200/80 dark:border-gray-800/80 hover:border-gray-300 dark:hover:border-gray-700 transition-colors">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Notifikasi In-App</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tampilkan notifikasi di dalam aplikasi</p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                                <input type="checkbox" v-model="notificationForm.in_app_notifications" class="sr-only peer" />
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>

                                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                                            <button type="submit" :disabled="notificationForm.processing"
                                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-sky-500/20 active:scale-[0.98]">
                                                <svg v-if="notificationForm.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>
                                                {{ notificationForm.processing ? 'Menyimpan...' : 'Simpan Preferensi Notifikasi' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- ── Empty State ─────────────────────── -->
                            <div v-if="activeSection && !sections.find(s => s.id === activeSection)"
                                class="text-center py-16">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                    <Cog6ToothIcon class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                </div>
                                <p class="text-gray-400 dark:text-gray-500">Pilih menu pengaturan di samping untuk memulai.</p>
                            </div>

                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Unscoped: Teleport moves toast to body */
.toast-slide-enter-active { transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
.toast-slide-leave-active { transition: all 0.2s ease-in; }
.toast-slide-enter-from { transform: translateX(100%) translateY(-10px); opacity: 0; }
.toast-slide-leave-to { transform: translateX(100%); opacity: 0; }

/* Section transitions */
.section-fade-enter-active { transition: all 0.25s ease-out; }
.section-fade-leave-active { transition: all 0.15s ease-in; }
.section-fade-enter-from { opacity: 0; transform: translateY(12px); }
.section-fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
