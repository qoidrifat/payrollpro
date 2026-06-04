<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: 'lg' },
    closeable: { type: Boolean, default: true },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(() => props.show, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
        showSlot.value = true;
    } else {
        document.body.style.overflow = '';
        setTimeout(() => { showSlot.value = false; }, 200);
    }
});

const close = () => {
    if (props.closeable) emit('close');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => ({
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
}[props.maxWidth]));
</script>

<template>
    <Transition name="modal-overlay">
        <div
            v-show="show"
            class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
            @click="close"
        />
    </Transition>

    <Transition name="modal-content">
        <div
            v-show="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="close"
        >
            <div :class="['relative w-full bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-h-[90vh] flex flex-col', maxWidthClass]">
                <div v-if="title" class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
                    <button
                        v-if="closeable"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        @click="close"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <slot v-if="showSlot" />
                </div>
                <div v-if="$slots.footer && showSlot" class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-overlay-enter-active { transition: opacity 0.2s ease-out; }
.modal-overlay-leave-active { transition: opacity 0.15s ease-in; }
.modal-overlay-enter-from, .modal-overlay-leave-to { opacity: 0; }

.modal-content-enter-active { transition: all 0.2s ease-out; }
.modal-content-leave-active { transition: all 0.15s ease-in; }
.modal-content-enter-from, .modal-content-leave-to { opacity: 0; transform: scale(0.95) translateY(10px); }
</style>
