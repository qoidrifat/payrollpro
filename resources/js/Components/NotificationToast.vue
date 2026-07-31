<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, XCircleIcon, XMarkIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timeoutId = null;

const showToast = (msg, t) => {
    message.value = msg;
    type.value = t;
    visible.value = true;
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => { visible.value = false; }, 5000);
};

watchEffect(() => {
    if (page.props.flash?.success) showToast(page.props.flash.success, 'success');
    if (page.props.flash?.error) showToast(page.props.flash.error, 'error');
});
</script>

<template>
    <Transition name="toast">
        <div
            v-if="visible"
            :class="[
                'fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3.5 rounded-xl shadow-2xl backdrop-blur-xl border max-w-sm animate-slide-up',
                type === 'success'
                    ? 'bg-emerald-50/90 dark:bg-emerald-950/90 text-emerald-800 dark:text-emerald-200 border-emerald-200/50 dark:border-emerald-800/50'
                    : type === 'error'
                        ? 'bg-red-50/90 dark:bg-red-950/90 text-red-800 dark:text-red-200 border-red-200/50 dark:border-red-800/50'
                        : 'bg-blue-50/90 dark:bg-blue-950/90 text-blue-800 dark:text-blue-200 border-blue-200/50 dark:border-blue-800/50',
            ]"
        >
            <CheckCircleIcon v-if="type === 'success'" class="w-5 h-5 flex-shrink-0" />
            <XCircleIcon v-else-if="type === 'error'" class="w-5 h-5 flex-shrink-0" />
            <InformationCircleIcon v-else class="w-5 h-5 flex-shrink-0" />
            <p class="text-sm font-medium flex-1">{{ message }}</p>
            <button class="p-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 transition-colors" @click="visible = false">
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>

<style scoped>
.toast-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateX(24px) scale(0.95); }
.toast-leave-to { opacity: 0; transform: translateX(24px) scale(0.95); }
</style>
