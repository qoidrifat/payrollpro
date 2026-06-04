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
    <Modal :show="show" :title="title" @close="emit('close')">
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ message }}</p>
        <template #footer>
            <button class="btn-secondary" @click="emit('close')" :disabled="loading">Batal</button>
            <button
                :class="[
                    confirmVariant === 'danger' ? 'btn-danger' : 'btn-primary',
                ]"
                :disabled="loading"
                @click="emit('confirm')"
            >
                {{ loading ? 'Memproses...' : confirmText }}
            </button>
        </template>
    </Modal>
</template>
