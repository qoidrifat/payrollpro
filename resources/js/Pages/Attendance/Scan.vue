<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon, ArrowPathIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const employee = page.props.employee;
const todayRecord = page.props.todayRecord;
const action = page.props.action; // 'in' or 'out'
const attendanceToken = page.props.attendance_token;

const status = ref('ready'); // ready | success | error
const message = ref('');
// Attendance is WIB-based; render the clock in Asia/Jakarta regardless of the
// device timezone so the "WIB" label is accurate.
const wibTime = () => new Date().toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta' });
const time = ref(wibTime());
let timer = null;

const clockInOut = () => {
    if (!employee?.id) {
        status.value = 'error';
        message.value = 'Data karyawan tidak ditemukan. Silakan hubungi admin.';
        return;
    }
    status.value = 'loading';
    router.post(`/scan/clock-${action}/${employee.id}`, {
        attendance_token: attendanceToken,
    }, {
        onSuccess: () => {
            status.value = 'success';
            message.value = action === 'in'
                ? `Clock In berhasil — ${wibTime()} WIB`
                : `Clock Out berhasil — ${wibTime()} WIB`;
        },
        onError: (errors) => {
            status.value = 'error';
            message.value = 'Gagal merekam absensi. Silakan coba lagi.';
        },
    });
};

onMounted(() => {
    timer = setInterval(() => {
        time.value = wibTime();
    }, 1000);
    // Auto clock in/out on scan
    if (todayRecord && action === 'in' && todayRecord.clock_in) {
        status.value = 'success';
        message.value = `Sudah Clock In — ${todayRecord.clock_in} WIB`;
    } else if (todayRecord && action === 'out' && todayRecord.clock_out) {
        status.value = 'success';
        message.value = `Sudah Clock Out — ${todayRecord.clock_out} WIB`;
    } else {
        clockInOut();
    }
});

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-indigo-600 via-purple-700 to-violet-800 flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Decorative elements -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-white/5 rounded-full -translate-y-1/2 -translate-x-1/4" />
        <div class="absolute bottom-0 right-0 w-80 h-80 bg-white/5 rounded-full translate-y-1/2 translate-x-1/4" />
        <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-white/[0.03] rounded-full -translate-x-1/2 -translate-y-1/2" />

        <div class="relative w-full max-w-sm animate-fade-in-up">
            <div class="rounded-3xl border border-white/20 bg-white/95 dark:bg-gray-900/95 backdrop-blur-2xl shadow-2xl shadow-indigo-900/30 p-8 text-center">
                <!-- Employee Info -->
                <div class="mb-6">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mb-4 shadow-lg shadow-indigo-500/30">
                        <span class="text-xl font-bold text-white">{{ employee?.first_name?.charAt(0) }}</span>
                    </div>
                    <h2 class="text-xl font-display font-bold text-gray-900 dark:text-white">{{ employee?.full_name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ employee?.position }}</p>
                </div>

                <!-- Clock -->
                <div class="text-4xl font-display font-bold text-gray-900 dark:text-white mb-6 tabular-nums tracking-tight">
                    {{ time }}
                    <span class="block text-xs font-medium text-gray-400 mt-1">Waktu Indonesia Barat</span>
                </div>

                <!-- Status -->
                <div v-if="status === 'loading'" class="p-6 rounded-2xl bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/50 dark:to-purple-950/50 border border-indigo-200 dark:border-indigo-800">
                    <ArrowPathIcon class="w-10 h-10 text-indigo-600 dark:text-indigo-400 mx-auto animate-spin mb-3" />
                    <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Merekam absensi...</p>
                </div>

                <div v-else-if="status === 'success'" class="p-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/50 dark:to-teal-950/50 border border-emerald-200 dark:border-emerald-800">
                    <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mb-3 shadow-lg shadow-emerald-500/30">
                        <CheckCircleIcon class="w-8 h-8 text-white" />
                    </div>
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ message }}</p>
                </div>

                <div v-else-if="status === 'error'" class="p-6 rounded-2xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/50 dark:to-rose-950/50 border border-red-200 dark:border-red-800">
                    <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center mb-3 shadow-lg shadow-red-500/30">
                        <XCircleIcon class="w-8 h-8 text-white" />
                    </div>
                    <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ message }}</p>
                </div>

                <p class="mt-6 text-xs text-gray-400 dark:text-gray-500 font-medium">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400" />
                        {{ action === 'in' ? 'Clock In' : 'Clock Out' }} — PayrollPro Attendance
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>
