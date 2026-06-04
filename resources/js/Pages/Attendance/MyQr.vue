<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import QrCode from '@/Components/QrCode.vue'
import {
    QrCodeIcon,
    ArrowDownIcon,
    ArrowUpIcon,
    ClockIcon,
    MoonIcon,
    SunIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const employee = computed(() => page.props.employee)
const baseUrl = window.location.origin
const roles = computed(() => page.props.auth?.user?.roles || [])
const isEmployee = computed(() => roles.value.includes('Employee'))
const Layout = computed(() => isEmployee.value ? EmployeeLayout : AuthenticatedLayout)

const now = ref(new Date())
let timer = null

onMounted(() => {
    timer = setInterval(() => { now.value = new Date() }, 1000)
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const timeString = computed(() =>
    now.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
)

const secondsString = computed(() =>
    now.value.toLocaleTimeString('id-ID', { second: '2-digit' })
)

const dateString = computed(() =>
    now.value.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

const isWorkingHours = computed(() => {
    const h = now.value.getHours()
    const m = now.value.getMinutes()
    const total = h * 60 + m
    return total >= 390 && total < 1020 // 06:30–16:59
})

const isAfterHours = computed(() => {
    const h = now.value.getHours()
    const m = now.value.getMinutes()
    const total = h * 60 + m
    return total >= 1020
})

const showClockOut = computed(() => {
    const h = now.value.getHours()
    const m = now.value.getMinutes()
    return h > 16 || (h === 16 && m >= 55)
})

const statusLabel = computed(() => {
    if (isWorkingHours.value) return null
    return isAfterHours.value ? 'Sesi telah berakhir' : 'Di luar jam operasional'
})

const statusDescription = computed(() => {
    if (isWorkingHours.value) return null
    return isAfterHours.value
        ? 'QR Attendance akan tersedia kembali besok pukul 06.30 WIB.'
        : 'QR Attendance tersedia mulai pukul 06.30 hingga 17.00 WIB.'
})

const progressPercent = computed(() => {
    const h = now.value.getHours()
    const m = now.value.getMinutes()
    const total = h * 60 + m
    const start = 390 // 06:30
    const end = 1020  // 17:00
    if (total < start) return 0
    if (total > end) return 100
    return Math.round(((total - start) / (end - start)) * 100)
})
</script>

<template>
    <component :is="Layout">
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
                        <QrCodeIcon class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Absensi QR</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ employee?.first_name }} {{ employee?.last_name }} · {{ employee?.position }}</p>
                    </div>
                </div>
            </div>

            <!-- Live Clock Card -->
            <div class="glass-card p-6 lg:p-8">
                <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-primary-500/20">
                            <ClockIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-5xl font-display font-bold text-gray-900 dark:text-white tabular-nums tracking-tight leading-none">
                                    {{ timeString }}
                                </span>
                                <span class="text-lg font-display font-medium text-gray-400 dark:text-gray-500 tabular-nums leading-none">
                                    {{ secondsString }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ dateString }}</p>
                        </div>
                    </div>

                    <!-- Working hours progress -->
                    <div v-if="isWorkingHours" class="w-full sm:w-48">
                        <div class="flex items-center justify-between text-xs text-gray-400 mb-1.5">
                            <span>06:30</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">{{ progressPercent }}%</span>
                            <span>17:00</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-amber-500 to-rose-500 transition-all duration-1000"
                                :style="{ width: `${progressPercent}%` }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outside Working Hours -->
            <div v-if="!isWorkingHours" class="glass-card p-8 lg:p-10 text-center">
                <div :class="[
                    'w-24 h-24 mx-auto rounded-3xl flex items-center justify-center mb-6',
                    isAfterHours
                        ? 'bg-indigo-50 dark:bg-indigo-950'
                        : 'bg-amber-50 dark:bg-amber-950',
                ]">
                    <MoonIcon v-if="isAfterHours" class="w-12 h-12 text-indigo-500 dark:text-indigo-400" />
                    <SunIcon v-else class="w-12 h-12 text-amber-500 dark:text-amber-400" />
                </div>
                <h3 class="text-2xl font-display font-bold text-gray-900 dark:text-white mb-3">{{ statusLabel }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed max-w-md mx-auto mb-8">{{ statusDescription }}</p>
                <div class="inline-flex items-center gap-4 px-6 py-3 rounded-2xl bg-gray-50 dark:bg-gray-800/50">
                    <div class="text-left">
                        <p class="text-xs text-gray-400">Jam operasional</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">06.30 – 17.00 WIB</p>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-700" />
                    <div class="text-left">
                        <p class="text-xs text-gray-400">Tersedia kembali</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">Besok pukul 06.30</p>
                    </div>
                </div>
            </div>

            <!-- QR Codes (during working hours) -->
            <div v-else class="space-y-8">
                <div :class="[
                    'grid gap-6 lg:gap-8',
                    showClockOut ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 max-w-lg mx-auto',
                ]">
                    <!-- Clock In -->
                    <div class="glass-card p-6 lg:p-8 flex flex-col items-center text-center hover:shadow-lg transition-all duration-300">
                        <!-- Gradient accent bar -->
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-emerald-500 to-teal-600 opacity-70" />

                        <div class="relative mt-2">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500 to-teal-600 opacity-20 blur-xl rounded-full" />
                            <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 mb-5">
                                <ArrowDownIcon class="w-8 h-8 text-white" />
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-5">Clock In — Absen Masuk</h3>

                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-inner ring-1 ring-gray-200 dark:ring-gray-700">
                            <QrCode :text="`${baseUrl}/scan/in/${employee.id}`" :size="200" />
                        </div>

                        <div class="flex items-center gap-2 mt-5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-medium shadow-sm">
                                <ArrowDownIcon class="w-3 h-3" />
                                Scan untuk masuk
                            </span>
                        </div>
                    </div>

                    <!-- Clock Out -->
                    <div v-if="showClockOut" class="glass-card p-6 lg:p-8 flex flex-col items-center text-center hover:shadow-lg transition-all duration-300">
                        <!-- Gradient accent bar -->
                        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-rose-500 to-red-600 opacity-70" />

                        <div class="relative mt-2">
                            <div class="absolute inset-0 bg-gradient-to-br from-rose-500 to-red-600 opacity-20 blur-xl rounded-full" />
                            <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-lg shadow-rose-500/20 mb-5">
                                <ArrowUpIcon class="w-8 h-8 text-white" />
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-5">Clock Out — Absen Pulang</h3>

                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-inner ring-1 ring-gray-200 dark:ring-gray-700">
                            <QrCode :text="`${baseUrl}/scan/out/${employee.id}`" :size="200" />
                        </div>

                        <div class="flex items-center gap-2 mt-5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-rose-500 to-red-600 text-white text-xs font-medium shadow-sm">
                                <ArrowUpIcon class="w-3 h-3" />
                                Scan untuk pulang
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Clock Out countdown info -->
                <div v-if="!showClockOut" class="flex justify-center">
                    <div class="glass-card p-5 inline-flex items-center gap-4">
                        <div class="relative flex-shrink-0">
                            <div class="w-3 h-3 rounded-full bg-amber-400 animate-ping absolute inset-0 opacity-75" />
                            <div class="w-3 h-3 rounded-full bg-amber-500 relative" />
                        </div>
                        <div class="text-left">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                QR Clock Out tersedia mulai pukul
                                <span class="font-semibold text-gray-700 dark:text-gray-300">16:55 WIB</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">Silakan scan QR Clock In terlebih dahulu</p>
                        </div>
                        <ChevronRightIcon class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
