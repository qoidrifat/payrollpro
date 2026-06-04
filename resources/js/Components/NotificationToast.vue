<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');

watchEffect(() => {
    if (page.props.flash?.success) {
        message.value = page.props.flash.success;
        type.value = 'success';
        visible.value = true;
        setTimeout(() => { visible.value = false; }, 5000);
    }
    if (page.props.flash?.error) {
        message.value = page.props.flash.error;
        type.value = 'error';
        visible.value = true;
        setTimeout(() => { visible.value = false; }, 5000);
    }
});
</script>

<template>
    <Transition name="toast">
        <div
            v-if="visible"
            :class="[
                'fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg',
                type === 'success'
                    ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800'
                    : 'bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800',
            ]"
        >
            <CheckCircleIcon v-if="type === 'success'" class="w-5 h-5 flex-shrink-0" />
            <XCircleIcon v-else class="w-5 h-5 flex-shrink-0" />
            <p class="text-sm font-medium">{{ message }}</p>
            <button class="ml-2 p-1 rounded-lg hover:bg-black/10" @click="visible = false">
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>

<style scoped>
.toast-enter-active { transition: all 0.3s ease-out; }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateY(20px); }
.toast-leave-to { opacity: 0; transform: translateY(20px); }
</style>
