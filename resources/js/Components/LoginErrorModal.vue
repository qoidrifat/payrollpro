<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { XCircleIcon, ExclamationTriangleIcon, XMarkIcon, ArrowPathIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    errorType: { type: String, default: '' }, // 'invalid_credentials' | 'account_suspended' | 'account_pending' | 'too_many_attempts'
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
        case 'invalid_credentials': return 'Login Gagal';
        case 'account_suspended': return 'Akun Dinonaktifkan';
        case 'account_pending': return 'Menunggu Persetujuan';
        case 'too_many_attempts': return 'Terlalu Banyak Percobaan';
        default: return 'Login Gagal';
    }
});

const errorIcon = computed(() => {
    if (props.errorType === 'too_many_attempts') return ClockIcon;
    if (props.errorType === 'invalid_credentials') return XCircleIcon;
    return ExclamationTriangleIcon;
});

const errorDetail = computed(() => {
    switch (props.errorType) {
        case 'invalid_credentials':
            // Generic message — do NOT reveal whether the email or the password
            // was wrong, to prevent account enumeration.
            return 'Email atau kata sandi salah. Silakan periksa kembali data Anda atau gunakan fitur "Lupa kata sandi".';
        case 'account_suspended':
            return 'Akun ini sedang dinonaktifkan. Hubungi administrator untuk mengaktifkan kembali.';
        case 'account_pending':
            return 'Akun Anda masih menunggu persetujuan admin sebelum dapat masuk. Silakan coba lagi setelah disetujui.';
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
