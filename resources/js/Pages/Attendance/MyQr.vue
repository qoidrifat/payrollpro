<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import ManualAttendanceModal from '@/Components/ManualAttendanceModal.vue'
import ManualAttendanceStatusBadge from '@/Components/ManualAttendanceStatusBadge.vue'
import QrCode from '@/Components/QrCode.vue'
import { useSupabaseRealtime } from '@/composables/useSupabaseRealtime'
import {
    ArrowDownIcon,
    ArrowPathIcon,
    ArrowUpIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    QrCodeIcon,
    UserCircleIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const roles = computed(() => page.props.auth?.user?.roles || [])
const mode = computed(() => page.props.mode || 'employee')
const isSelfServiceEmployee = computed(() => mode.value === 'employee' && roles.value.includes('Employee') && !roles.value.some(role => ['Admin', 'HR'].includes(role)))
const isEmployeeMode = computed(() => mode.value === 'employee')
const Layout = computed(() => isSelfServiceEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const now = ref(page.props.attendanceWindow?.server_time ? new Date(page.props.attendanceWindow.server_time) : new Date())
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
const query = ref('')
const refreshing = ref(false)
const refreshError = ref('')
const nextRefreshAttemptAt = ref(0)
const selectedEmployeeId = ref(page.props.employee?.id ?? page.props.employees?.[0]?.id ?? null)
const showManualModal = ref(false)
const defaultManualType = ref('manual_clock_in')
const manualRequests = ref(page.props.manualRequests || [])
const manualRefreshing = ref(false)
const realtime = useSupabaseRealtime()
let timer = null
let manualPollTimer = null
let realtimeUnsubscribe = null

const attendanceWindow = computed(() => page.props.attendanceWindow || {})
const isOperationalHours = computed(() => attendanceWindow.value.is_operational_hours === true)
const operationalHoursLabel = computed(() => attendanceWindow.value.label || `${attendanceWindow.value.operational_start || '06:30'} - ${attendanceWindow.value.operational_end || '17:00'} WIB`)
const nextOperationalStartLabel = computed(() => {
    if (!attendanceWindow.value.next_operational_start) return null

    return new Date(attendanceWindow.value.next_operational_start).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: attendanceWindow.value.timezone || 'Asia/Jakarta',
    })
})

const employeeOptions = computed(() => {
    if (mode.value === 'employee') {
        return page.props.employee ? [page.props.employee] : []
    }

    return page.props.employees || []
})

const selectedEmployee = computed(() =>
    employeeOptions.value.find(employee => employee.id === selectedEmployeeId.value) || employeeOptions.value[0] || null
)

watch(employeeOptions, (employees) => {
    if (!employees.length) {
        selectedEmployeeId.value = null
        return
    }

    if (!employees.some(employee => employee.id === selectedEmployeeId.value)) {
        selectedEmployeeId.value = employees[0].id
    }
})

watch(() => attendanceWindow.value.server_time, (serverTime) => {
    if (serverTime) {
        now.value = new Date(serverTime)
    }
})

watch(isOperationalHours, (operational) => {
    if (!operational) {
        showManualModal.value = false
    }
})

const filteredEmployees = computed(() => {
    const keyword = query.value.trim().toLowerCase()

    if (!keyword) return employeeOptions.value

    return employeeOptions.value.filter(employee => [
        employee.full_name,
        employee.position,
        employee.department,
    ].filter(Boolean).some(value => String(value).toLowerCase().includes(keyword)))
})

const timeString = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    }).format(now.value).replace(/\./g, ':')
)

const wibTimeString = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    }).format(now.value).replace(/\./g, ':')
)

const dateString = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }).format(now.value)
)

const isWorkingHours = computed(() => isOperationalHours.value)

const showClockOut = computed(() => {
    const parts = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    }).formatToParts(now.value)
    const hour = Number(parts.find(part => part.type === 'hour')?.value ?? 0)
    const minute = Number(parts.find(part => part.type === 'minute')?.value ?? 0)
    const total = hour * 60 + minute
    return total >= 1015 && total < 1020
})

// Clock-out window opens 5 minutes before operational end. Derive the label from
// the same operational_end prop instead of hardcoding "16.55 WIB".
const clockOutWindowLabel = computed(() => {
    const end = attendanceWindow.value.operational_end || '17:00'
    const [h, m] = String(end).split(':').map(Number)
    if (Number.isNaN(h) || Number.isNaN(m)) return '16.55 WIB'
    const openMinutes = h * 60 + m - 5
    const oh = String(Math.floor(openMinutes / 60)).padStart(2, '0')
    const om = String(openMinutes % 60).padStart(2, '0')
    const tzAbbr = /Jakarta|Pontianak/.test(attendanceWindow.value.timezone || 'Asia/Jakarta')
        ? 'WIB'
        : (attendanceWindow.value.timezone === 'Asia/Makassar' ? 'WITA'
            : attendanceWindow.value.timezone === 'Asia/Jayapura' ? 'WIT' : 'WIB')
    return `${oh}.${om} ${tzAbbr}`
})

const canUseManualAttendance = computed(() => mode.value === 'employee' && isOperationalHours.value && Boolean(page.props.employee?.id))

const latestManualRequest = computed(() => manualRequests.value?.[0] || null)

const pendingManualRequest = computed(() =>
    manualRequests.value.find(request => request.status === 'pending') || null
)

const manualStatusMessage = computed(() => {
    const request = pendingManualRequest.value || latestManualRequest.value
    if (!request) return null

    const action = request.request_type === 'manual_clock_in' ? 'Manual Clock-In' : 'Manual Clock-Out'

    if (request.status === 'pending') {
        return `${action} Menunggu Verifikasi`
    }

    if (request.status === 'approved') {
        return `${action} Disetujui`
    }

    return `${action} Ditolak`
})

const manualRealtimeLabel = computed(() => {
    if (!realtime.isConfigured) return 'Fallback polling aktif'
    if (realtime.status.value === 'SUBSCRIBED') return 'Realtime aktif'
    if (['CHANNEL_ERROR', 'TIMED_OUT', 'CLOSED'].includes(realtime.status.value)) return 'Realtime terputus, polling aktif'
    return 'Menghubungkan realtime'
})

const qrCanvasSize = computed(() => {
    if (viewportWidth.value < 380) return 184
    if (viewportWidth.value < 640) return 208
    if (viewportWidth.value < 1024) return isEmployeeMode.value ? 216 : 240
    return isEmployeeMode.value ? 196 : 240
})

const remainingSeconds = computed(() => {
    if (!isOperationalHours.value) return 0

    const expiry = page.props.nextQrRefreshAt || page.props.qrExpiresAt
    if (!expiry) return 0

    return Math.max(0, Math.floor((new Date(expiry).getTime() - now.value.getTime()) / 1000))
})

const expiryLabel = computed(() => {
    const seconds = remainingSeconds.value
    const minutes = Math.floor(seconds / 60)
    const rest = seconds % 60

    return `${String(minutes).padStart(2, '0')}:${String(rest).padStart(2, '0')}`
})

const countdownText = computed(() => `QR akan diperbarui dalam ${expiryLabel.value}`)

const refreshQr = () => {
    if (refreshing.value || !isOperationalHours.value || Date.now() < nextRefreshAttemptAt.value) return

    refreshing.value = true
    refreshError.value = ''
    router.reload({
        only: ['employee', 'employees', 'attendanceWindow', 'qrExpiresAt', 'nextQrRefreshAt', 'qrRefreshIntervalSeconds'],
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            refreshError.value = 'Gagal memperbarui QR. Sistem akan mencoba lagi.'
            nextRefreshAttemptAt.value = Date.now() + 15000
        },
        onSuccess: () => {
            nextRefreshAttemptAt.value = 0
        },
        onFinish: () => {
            refreshing.value = false
        },
    })
}

const openManualModal = (type = null) => {
    defaultManualType.value = type || (showClockOut.value ? 'manual_clock_out' : 'manual_clock_in')
    showManualModal.value = true
}

const refreshManualRequests = async () => {
    if (!canUseManualAttendance.value || manualRefreshing.value) return

    manualRefreshing.value = true
    try {
        const { data } = await window.axios.get(route('manual-attendance-requests.my-latest'))
        manualRequests.value = data.manualRequests || []
    } finally {
        manualRefreshing.value = false
    }
}

const shouldPollManualRequests = () =>
    !realtime.isConfigured || realtime.status.value !== 'SUBSCRIBED'

const syncViewportWidth = () => {
    viewportWidth.value = window.innerWidth
}

onMounted(() => {
    window.addEventListener('resize', syncViewportWidth)

    timer = setInterval(() => {
        now.value = new Date()

        if (isOperationalHours.value && remainingSeconds.value <= 0 && !refreshing.value) {
            refreshQr()
        }
    }, 1000)

    if (canUseManualAttendance.value) {
        manualPollTimer = setInterval(() => {
            if (shouldPollManualRequests()) {
                refreshManualRequests()
            }
        }, 60000)
        realtimeUnsubscribe = realtime.subscribeToNotifications({
            channelName: 'project-kp-manual-attendance-employee',
            topics: ['manual_attendance', 'attendance'],
            onChange: refreshManualRequests,
        })
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', syncViewportWidth)
    if (timer) clearInterval(timer)
    if (manualPollTimer) clearInterval(manualPollTimer)
    if (realtimeUnsubscribe) realtimeUnsubscribe()
})
</script>

<template>
    <component :is="Layout">
        <div
            :class="[
                'mx-auto w-full',
                isEmployeeMode ? 'flex max-w-xl flex-col justify-center space-y-5 lg:h-[calc(100vh-8.75rem)] lg:max-w-5xl lg:space-y-3 lg:overflow-hidden' : 'max-w-7xl space-y-6',
            ]"
        >
            <section
                :class="[
                    'rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900',
                    isEmployeeMode ? 'p-5 sm:p-6 lg:hidden' : 'p-5',
                ]"
            >
                <div
                    :class="[
                        'flex flex-col gap-4',
                        isEmployeeMode ? 'items-center text-center lg:flex-row lg:justify-between lg:gap-5 lg:text-left' : 'lg:flex-row lg:items-center lg:justify-between',
                    ]"
                >
                    <div :class="['flex gap-4', isEmployeeMode ? 'flex-col items-center lg:flex-row' : 'items-center']">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-950 text-white shadow-sm dark:bg-white dark:text-gray-950 lg:h-10 lg:w-10">
                            <QrCodeIcon class="h-6 w-6 lg:h-5 lg:w-5" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                {{ mode === 'admin' ? 'Admin QR Attendance' : 'Employee QR Attendance' }}
                            </p>
                            <h1 class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white lg:text-xl">
                                {{ mode === 'admin' ? 'Absensi QR' : 'QR Absensi Saya' }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 lg:mt-0.5">
                                {{ mode === 'admin' ? 'Pilih karyawan untuk menampilkan QR absen masuk dan pulang.' : 'Gunakan QR ini untuk proses absensi sesuai jadwal.' }}
                            </p>
                        </div>
                    </div>

                    <div :class="['grid grid-cols-2 gap-3', isEmployeeMode ? 'w-full lg:w-auto lg:min-w-72' : 'sm:flex sm:items-center']">
                        <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800 lg:px-3 lg:py-2">
                            <p class="text-xs text-gray-400">Waktu</p>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-gray-950 dark:text-white lg:text-base">{{ timeString }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800 lg:px-3 lg:py-2">
                            <p class="text-xs text-gray-400">{{ isOperationalHours ? 'QR refresh' : 'Jam operasional' }}</p>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-gray-950 dark:text-white lg:text-base">
                                {{ isOperationalHours ? expiryLabel : operationalHoursLabel }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section
                :class="[
                    'grid gap-6',
                    isEmployeeMode ? 'justify-items-center' : 'lg:grid-cols-[360px_minmax(0,1fr)]',
                ]"
            >
                <aside
                    v-if="mode === 'admin'"
                    class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="border-b border-gray-100 p-4 dark:border-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Daftar Karyawan</h2>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ employeeOptions.length }} karyawan aktif</p>
                            </div>
                            <UsersIcon class="h-5 w-5 text-gray-400" />
                        </div>

                        <label class="mt-4 flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-gray-800 dark:bg-gray-950">
                            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" />
                            <input
                                v-model="query"
                                type="search"
                                class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                                placeholder="Cari nama, jabatan, divisi"
                            >
                        </label>
                    </div>

                    <div class="max-h-[620px] overflow-y-auto p-2">
                        <button
                            v-for="employee in filteredEmployees"
                            :key="employee.id"
                            type="button"
                            :class="[
                                'flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition',
                                selectedEmployee?.id === employee.id
                                    ? 'bg-gray-950 text-white shadow-sm dark:bg-white dark:text-gray-950'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                            ]"
                            @click="selectedEmployeeId = employee.id"
                        >
                            <div
                                :class="[
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                                    selectedEmployee?.id === employee.id
                                        ? 'bg-white/15 text-white dark:bg-gray-950/10 dark:text-gray-950'
                                        : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                ]"
                            >
                                <UserCircleIcon class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ employee.full_name }}</p>
                                <p
                                    :class="[
                                        'mt-0.5 truncate text-xs',
                                        selectedEmployee?.id === employee.id ? 'text-white/70 dark:text-gray-600' : 'text-gray-500 dark:text-gray-400',
                                    ]"
                                >
                                    {{ employee.position || 'Karyawan' }}<span v-if="employee.department"> - {{ employee.department }}</span>
                                </p>
                            </div>
                        </button>

                        <div v-if="!filteredEmployees.length" class="px-4 py-10 text-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Tidak ada karyawan</p>
                            <p class="mt-1 text-xs text-gray-400">Coba kata kunci lain.</p>
                        </div>
                    </div>
                </aside>

                <main
                    :class="[
                        'w-full rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900',
                        isEmployeeMode ? 'max-w-xl p-4 sm:p-6 lg:max-w-5xl lg:p-4' : 'p-5 lg:p-6',
                    ]"
                >
                    <div v-if="selectedEmployee" :class="isEmployeeMode ? 'space-y-5 lg:space-y-3' : 'space-y-6'">
                        <div
                            :class="[
                                'flex flex-col gap-4 border-b border-gray-100 pb-5 dark:border-gray-800',
                                isEmployeeMode ? 'items-center text-center lg:flex-row lg:justify-between lg:pb-3 lg:text-left' : 'md:flex-row md:items-center md:justify-between',
                            ]"
                        >
                            <div :class="['flex gap-4', isEmployeeMode ? 'flex-col items-center lg:flex-row' : 'items-center']">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 lg:h-10 lg:w-10 lg:rounded-xl">
                                    <span class="text-lg font-semibold lg:text-base">{{ selectedEmployee.full_name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p v-if="isEmployeeMode" class="hidden text-xs font-semibold uppercase tracking-wide text-gray-400 lg:block">
                                        QR Absensi Saya
                                    </p>
                                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white lg:text-lg">{{ selectedEmployee.full_name }}</h2>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 lg:mt-0.5">
                                        {{ selectedEmployee.position || 'Karyawan' }}<span v-if="selectedEmployee.department"> - {{ selectedEmployee.department }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400 lg:mt-0.5">{{ dateString }}</p>
                                </div>
                            </div>

                            <div :class="['flex flex-col gap-3', isEmployeeMode ? 'w-full items-stretch sm:w-auto sm:items-end' : 'items-start md:items-end']">
                                <div
                                    v-if="isEmployeeMode"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-center dark:border-gray-800 dark:bg-gray-950 sm:w-auto sm:min-w-40 sm:text-right lg:px-3 lg:py-2"
                                >
                                    <p class="text-xs font-medium text-gray-400">Waktu Saat Ini</p>
                                    <p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums tracking-tight text-gray-950 dark:text-white lg:text-base">
                                        {{ wibTimeString }}
                                        <span class="text-xs font-medium text-gray-400">WIB</span>
                                    </p>
                                </div>

                                <button
                                    v-if="isOperationalHours"
                                    type="button"
                                    :class="[
                                        'inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60 dark:border-gray-800 dark:text-gray-200 dark:hover:bg-gray-800',
                                        isEmployeeMode ? 'w-full sm:w-auto lg:px-3 lg:py-2' : '',
                                    ]"
                                    :disabled="refreshing"
                                    @click="refreshQr"
                                >
                                    <ArrowPathIcon :class="['h-4 w-4', refreshing ? 'animate-spin' : '']" />
                                    Refresh QR
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!isOperationalHours"
                            :class="[
                                'rounded-2xl border border-gray-200 bg-gray-50 px-6 py-8 text-center dark:border-gray-800 dark:bg-gray-950',
                                isEmployeeMode ? 'mx-auto max-w-2xl lg:px-6 lg:py-8' : '',
                            ]"
                        >
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                <ClockIcon class="h-6 w-6" />
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-950 dark:text-white">Di luar jam operasional absensi</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500 dark:text-gray-400">
                                QR absensi dan pengajuan kendala absen hanya tersedia pada jam operasional.
                            </p>
                            <div class="mt-5 inline-flex flex-col gap-1 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs font-medium text-gray-400">Jam operasional</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ operationalHoursLabel }}</span>
                                <span v-if="nextOperationalStartLabel" class="text-xs text-gray-500 dark:text-gray-400">
                                    QR tersedia kembali pukul {{ nextOperationalStartLabel }} WIB
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="isOperationalHours && canUseManualAttendance && manualStatusMessage"
                            :class="[
                                'rounded-2xl border border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-800 dark:bg-gray-950',
                                isEmployeeMode ? 'mx-auto max-w-md lg:max-w-none lg:px-4 lg:py-2.5' : '',
                            ]"
                        >
                            <div :class="['flex flex-col gap-3', isEmployeeMode ? 'items-center text-center' : 'sm:flex-row sm:items-center sm:justify-between']">
                                <div>
                                    <div :class="['flex flex-wrap items-center gap-2', isEmployeeMode ? 'justify-center' : '']">
                                        <ManualAttendanceStatusBadge :status="(pendingManualRequest || latestManualRequest)?.status" />
                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ manualStatusMessage }}</span>
                                    </div>
                                    <p v-if="latestManualRequest?.rejection_reason" class="mt-2 text-sm text-red-600 dark:text-red-300">
                                        {{ latestManualRequest.rejection_reason }}
                                    </p>
                                </div>
                                <p class="text-xs font-medium text-gray-400">{{ manualRealtimeLabel }}</p>
                            </div>
                        </div>

                        <div
                            v-if="isOperationalHours"
                            class="flex flex-col gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200 sm:flex-row sm:items-center sm:justify-between sm:text-left"
                        >
                            <span class="font-semibold">QR aktif selama jam operasional</span>
                            <span class="font-medium tabular-nums">{{ countdownText }}</span>
                        </div>

                        <p v-if="refreshError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                            {{ refreshError }}
                        </p>

                        <div v-if="isOperationalHours" :class="['grid gap-4', isEmployeeMode ? 'justify-items-center lg:grid-cols-2 lg:items-stretch lg:gap-4' : 'gap-5 xl:grid-cols-2']">
                            <article
                                :class="[
                                    'w-full rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-950/20 sm:p-5',
                                    isEmployeeMode ? 'max-w-md lg:max-w-none lg:p-4' : '',
                                ]"
                            >
                                <div class="mb-4 flex items-center justify-between lg:mb-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Clock In</p>
                                        <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Absen Masuk</h3>
                                    </div>
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                        <ArrowDownIcon class="h-5 w-5" />
                                    </div>
                                </div>

                                <div class="flex justify-center rounded-2xl bg-white p-4 shadow-inner ring-1 ring-emerald-100 dark:bg-gray-950 dark:ring-emerald-900 sm:p-5 lg:p-3">
                                    <div class="max-w-full overflow-hidden">
                                        <QrCode :text="selectedEmployee.qr_in_url" :size="qrCanvasSize" />
                                    </div>
                                </div>
                            </article>

                            <article
                                :class="[
                                    'w-full rounded-2xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900 dark:bg-rose-950/20 sm:p-5',
                                    isEmployeeMode ? 'max-w-md lg:max-w-none lg:p-4' : '',
                                ]"
                            >
                                <div class="mb-4 flex items-center justify-between lg:mb-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-300">Clock Out</p>
                                        <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Absen Pulang</h3>
                                    </div>
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-600 text-white">
                                        <ArrowUpIcon class="h-5 w-5" />
                                    </div>
                                </div>

                                <div v-if="showClockOut" class="flex justify-center rounded-2xl bg-white p-4 shadow-inner ring-1 ring-rose-100 dark:bg-gray-950 dark:ring-rose-900 sm:p-5 lg:p-3">
                                    <div class="max-w-full overflow-hidden">
                                        <QrCode :text="selectedEmployee.qr_out_url" :size="qrCanvasSize" />
                                    </div>
                                </div>

                                <div
                                    v-else
                                    :class="[
                                        'flex flex-col items-center justify-center rounded-2xl border border-dashed border-rose-200 bg-white/70 p-6 text-center dark:border-rose-900 dark:bg-gray-950/70',
                                        isEmployeeMode ? 'min-h-[250px] lg:min-h-[220px] lg:p-4' : 'min-h-[290px]',
                                    ]"
                                >
                                    <ClockIcon class="h-9 w-9 text-rose-400 lg:h-8 lg:w-8" />
                                    <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white lg:mt-2">QR pulang tersedia pukul {{ clockOutWindowLabel }}</p>
                                    <p class="mt-1 max-w-xs text-xs leading-5 text-gray-500 dark:text-gray-400">
                                        Jadwal ini menjaga alur absensi pulang tetap sesuai jam operasional.
                                    </p>
                                </div>
                            </article>
                        </div>

                        <section
                            v-if="canUseManualAttendance"
                            :class="[
                                'w-full rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950 sm:p-5',
                                isEmployeeMode ? 'mx-auto max-w-md lg:max-w-none lg:p-4' : '',
                            ]"
                        >
                            <div
                                :class="[
                                    'flex flex-col gap-4',
                                    isEmployeeMode ? 'items-center text-center lg:flex-row lg:justify-between lg:text-left' : 'md:flex-row md:items-center md:justify-between',
                                ]"
                            >
                                <div :class="['flex gap-4', isEmployeeMode ? 'flex-col items-center lg:flex-row' : '']">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 lg:h-10 lg:w-10">
                                        <ExclamationTriangleIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Mengalami kendala absen?</h3>
                                        <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                                            Ajukan absensi manual jika QR, kamera, lokasi, atau koneksi bermasalah.
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    :class="[
                                        'inline-flex items-center justify-center rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100',
                                        isEmployeeMode ? 'w-full sm:w-auto lg:px-3 lg:py-2' : '',
                                    ]"
                                    :disabled="Boolean(pendingManualRequest)"
                                    @click="openManualModal()"
                                >
                                    {{ pendingManualRequest ? 'Menunggu verifikasi' : 'Kendala absen? Klik manual di sini!' }}
                                </button>
                            </div>
                        </section>
                    </div>

                    <div v-else class="flex min-h-[420px] flex-col items-center justify-center text-center">
                        <UsersIcon class="h-10 w-10 text-gray-300" />
                        <p class="mt-4 text-sm font-semibold text-gray-800 dark:text-gray-100">Belum ada karyawan aktif</p>
                        <p class="mt-1 text-sm text-gray-400">Tambahkan data karyawan aktif untuk membuat QR absensi.</p>
                    </div>
                </main>
            </section>
        </div>

        <ManualAttendanceModal
            v-if="canUseManualAttendance"
            :show="showManualModal"
            :default-type="defaultManualType"
            @close="showManualModal = false"
            @submitted="refreshManualRequests"
        />
    </component>
</template>
