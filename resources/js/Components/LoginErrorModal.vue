<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { XCircleIcon, ExclamationTriangleIcon, XMarkIcon, ArrowPathIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    errorType: { type: String, default: '' }, // 'email_not_found' | 'wrong_password' | 'too_many_attempts'
});

const emit = defineEmits(['close']);

const visible = ref(false);

// Keep visible in sync with show, with a slight delay for enter animation
const stopShowWatcher = null;
if (props.show) {
    setTimeout(() => { visible.value = true; }, 50);
}

import { watch } from 'vue';

watch(() => props.show, (val) => {
    if (val) {
        setTimeout(() => { visible.value = true; }, 50);
    } else {
        visible.value = false;
    }
});

// Computed error messages based on errorType
const errorMessage = computed(() => {
    switch (props.errorType) {
        case 'email_not_found': return 'Email Tidak Terdaftar';
        case 'wrong_password': return 'Kata Sandi Salah';
        case 'too_many_attempts': return 'Terlalu Banyak Percobaan';
        default: return 'Login Gagal';
    }
});

const errorIcon = computed(() => {
    if (props.errorType === 'too_many_attempts') return ClockIcon;
    if (props.errorType === 'wrong_password') return XCircleIcon;
    return ExclamationTriangleIcon;
});

const errorDetail = computed(() => {
    switch (props.errorType) {
        case 'email_not_found':
            return 'Alamat email yang Anda masukkan belum terdaftar di sistem kami. Periksa kembali email Anda atau hubungi administrator.';
        case 'wrong_password':
            return 'Kata sandi yang Anda masukkan tidak cocok dengan akun ini. Silakan coba kembali atau gunakan fitur "Lupa kata sandi".';
        case 'too_many_attempts':
            return 'Terlalu banyak percobaan login yang gagal. Akun Anda telah dikunci sementara demi keamanan. Silakan tunggu beberapa saat sebelum mencoba lagi.';
        default:
            return 'Terjadi kesalahan saat mencoba masuk. Silakan periksa kembali data Anda.';
    }
});

const handleClose = () => {
    visible.value = false;
    setTimeout(() => emit('close'), 200);
};

// Handle Escape key globally
const handleGlobalKeydown = (e) => {
    if (e.key === 'Escape' && visible.value && props.show) {
        handleClose();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-backdrop">
            <div
                v-if="visible && show"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm dark:bg-gray-950/60"
                    @click="handleClose"
                />

                <!-- Modal -->
                <div class="relative w-full max-w-md">
                    <Transition name="modal-content">
                        <div
                            v-if="visible && show"
                            class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-2xl shadow-gray-900/30 dark:shadow-black/50 border border-gray-100 dark:border-gray-800"
                        >
                            <!-- Gradient Top Bar -->
                            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-red-500 via-rose-500 to-pink-500" />

                            <!-- Close Button -->
                            <button
                                type="button"
                                class="absolute top-4 right-4 p-1.5 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
                                @click="handleClose"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>

                            <div class="p-6 sm:p-8 pt-8 sm:pt-10">
                                <!-- Icon -->
                                <div class="flex justify-center mb-6">
                                    <div class="relative">
                                        <div class="absolute inset-0 bg-red-500/20 dark:bg-red-500/10 rounded-full animate-ping-slow" />
                                        <div class="relative w-16 h-16 rounded-full bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950 dark:to-red-900 flex items-center justify-center ring-2 ring-red-200 dark:ring-red-800">
                                            <component :is="errorIcon" class="w-9 h-9 text-red-500" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Error Message -->
                                <div class="text-center mb-6">
                                    <h3 class="text-xl font-display font-bold text-gray-900 dark:text-white mb-2">
                                        {{ errorMessage }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                        {{ errorDetail }}
                                    </p>
                                </div>

                                <!-- Field Indicator (only for email/password errors) -->
                                <div
                                    v-if="errorType === 'email_not_found' || errorType === 'wrong_password'"
                                    class="mb-6 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            :class="[
                                                'flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold',
                                                errorType === 'email_not_found'
                                                    ? 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500',
                                            ]"
                                        >
                                            @
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</p>
                                            <p
                                                :class="[
                                                    'text-sm font-semibold truncate',
                                                    errorType === 'email_not_found'
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-emerald-600 dark:text-emerald-400',
                                                ]"
                                            >
                                                {{ errorType === 'email_not_found' ? '✗ Tidak ditemukan' : '✓ Terdaftar' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                                        <div
                                            :class="[
                                                'flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold',
                                                errorType === 'wrong_password'
                                                    ? 'bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500',
                                            ]"
                                        >
                                            <span class="text-base leading-none">🔒</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kata Sandi</p>
                                            <p
                                                :class="[
                                                    'text-sm font-semibold truncate',
                                                    errorType === 'wrong_password'
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-emerald-600 dark:text-emerald-400',
                                                ]"
                                            >
                                                {{ errorType === 'wrong_password' ? '✗ Salah' : '✓ Sesuai' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action -->
                                <div class="flex flex-col gap-3">
                                    <button
                                        type="button"
                                        class="btn-primary w-full text-base flex items-center justify-center gap-2"
                                        @click="handleClose"
                                    >
                                        <ArrowPathIcon class="w-4 h-4" />
                                        Coba Lagi
                                    </button>
                                    <button
                                        type="button"
                                        class="btn-secondary w-full text-sm"
                                        @click="handleClose"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-backdrop-enter-active {
    transition: all 0.25s ease-out;
}
.modal-backdrop-leave-active {
    transition: all 0.2s ease-in;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
    opacity: 0;
}

.modal-content-enter-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-content-leave-active {
    transition: all 0.15s ease-in;
}
.modal-content-enter-from {
    opacity: 0;
    transform: scale(0.92) translateY(12px);
}
.modal-content-leave-to {
    opacity: 0;
    transform: scale(0.96) translateY(8px);
}
</style>
