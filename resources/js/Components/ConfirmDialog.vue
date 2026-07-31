<script setup>
import Modal from './Modal.vue';

defineProps({
    show: Boolean,
    title: { type: String, default: 'Konfirmasi Tindakan' },
    message: { type: String, default: 'Apakah Anda yakin ingin melanjutkan?' },
    confirmText: { type: String, default: 'Konfirmasi' },
    confirmVariant: { type: String, default: 'danger' },
    loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'close'])
</script>

<template>
    <Modal :show="show" :title="title" @close="emit('close')" max-width="md">
        <div class="flex items-start gap-3">
            <div
                :class="[
                    'flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
                    confirmVariant === 'danger' ? 'bg-red-50 dark:bg-red-950' : 'bg-primary-50 dark:bg-primary-950',
                ]"
            >
                <svg
                    v-if="confirmVariant === 'danger'"
                    class="w-5 h-5 text-red-600 dark:text-red-400"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <svg
                    v-else
                    class="w-5 h-5 text-primary-600 dark:text-primary-400"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ message }}</p>
            </div>
        </div>
        <template #footer>
            <button class="btn-ghost" @click="emit('close')" :disabled="loading">Batal</button>
            <button
                :class="[confirmVariant === 'danger' ? 'btn-danger' : 'btn-primary']"
                :disabled="loading"
                @click="emit('confirm')"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ loading ? 'Memproses...' : confirmText }}
            </button>
        </template>
    </Modal>
</template>
