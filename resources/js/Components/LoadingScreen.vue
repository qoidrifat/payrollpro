<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import logoIcon from '/public/iconn.png';

const visible = ref(false);
const transitioning = ref(false);
let timeoutId = null;

const show = () => {
    if (timeoutId) clearTimeout(timeoutId);
    transitioning.value = true;
    requestAnimationFrame(() => {
        visible.value = true;
    });
};

const hide = () => {
    transitioning.value = false;
    timeoutId = setTimeout(() => {
        visible.value = false;
    }, 400);
};

router.on('start', show);
router.on('finish', hide);
router.on('invalid', hide);
router.on('exception', hide);

onUnmounted(() => {
    if (timeoutId) clearTimeout(timeoutId);
});
</script>

<template>
    <Transition name="loading-fade">
        <div
            v-if="visible"
            class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white/80 dark:bg-gray-950/80 backdrop-blur-md"
        >
            <div class="relative flex flex-col items-center gap-6">
                <!-- Animated Logo with refined multi-ring effect -->
                <div class="relative w-20 h-20 sm:w-24 sm:h-24">
                    <div class="absolute inset-0 rounded-full bg-gradient-to-br from-primary-400/30 via-purple-400/20 to-cyan-400/30 animate-ping-slow" />
                    <div class="absolute inset-3 rounded-full bg-gradient-to-br from-primary-500/40 via-purple-500/30 to-cyan-500/40 animate-pulse-scale" />
                    <div class="absolute inset-0 flex items-center justify-center">
                        <img
                            :src="logoIcon"
                            alt="Loading"
                            class="w-12 h-12 sm:w-16 sm:h-16 object-contain animate-logo-breathe"
                        />
                    </div>
                </div>

                <!-- Refined loading bar -->
                <div class="w-40 sm:w-48 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full bg-gradient-to-r from-primary-500 via-purple-500 to-cyan-500 rounded-full animate-loading-bar" />
                </div>

                <p class="text-xs text-gray-400 dark:text-gray-500 font-medium tracking-wider uppercase animate-pulse-slow">
                    Memuat...
                </p>
            </div>
        </div>
    </Transition>
</template>

<style>
.loading-fade-enter-active {
    transition: opacity 0.25s ease-out, backdrop-filter 0.25s ease-out;
}
.loading-fade-leave-active {
    transition: opacity 0.4s ease-in, backdrop-filter 0.4s ease-in;
}
.loading-fade-enter-from,
.loading-fade-leave-to {
    opacity: 0;
    backdrop-filter: blur(0px);
}
</style>
