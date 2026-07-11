<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

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
    <div class="min-h-screen bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center p-6">
        <div class="glass-card max-w-sm w-full p-8 text-center bg-white/95 backdrop-blur-xl">
            <!-- Employee Info -->
            <div class="mb-6">
                <div class="w-16 h-16 mx-auto rounded-full bg-primary-100 flex items-center justify-center mb-4">
                    <span class="text-xl font-bold text-primary-700">{{ employee?.first_name?.charAt(0) }}</span>
                </div>
                <h2 class="text-xl font-display font-bold text-gray-900">{{ employee?.full_name }}</h2>
                <p class="text-sm text-gray-500">{{ employee?.position }}</p>
            </div>

            <!-- Clock -->
            <div class="text-4xl font-display font-bold text-gray-900 mb-6">{{ time }}</div>

            <!-- Status -->
            <div v-if="status === 'loading'" class="p-4 rounded-xl bg-primary-50 border border-primary-100">
                <ArrowPathIcon class="w-8 h-8 text-primary-600 mx-auto animate-spin mb-2" />
                <p class="text-sm text-primary-700">Merekam absensi...</p>
            </div>

            <div v-else-if="status === 'success'" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                <CheckCircleIcon class="w-10 h-10 text-emerald-600 mx-auto mb-2" />
                <p class="text-sm font-medium text-emerald-700">{{ message }}</p>
            </div>

            <div v-else-if="status === 'error'" class="p-4 rounded-xl bg-red-50 border border-red-200">
                <p class="text-sm text-red-600">{{ message }}</p>
            </div>

            <p class="mt-6 text-xs text-gray-400">
                {{ action === 'in' ? 'Clock In' : 'Clock Out' }} — PayrollPro Attendance
            </p>
        </div>
    </div>
</template>
