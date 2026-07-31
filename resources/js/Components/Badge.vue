<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'success', 'warning', 'danger', 'info', 'primary'].includes(v),
    },
    dot: { type: Boolean, default: false },
    size: { type: String, default: 'sm' }, // sm | lg
})

const classes = computed(() => {
    const variants = {
        default: 'bg-gray-100 text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700',
        success: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:ring-emerald-900',
        warning: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-900',
        danger: 'bg-red-50 text-red-700 ring-1 ring-red-200 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-900',
        info: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:ring-blue-900',
        primary: 'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-950/60 dark:text-primary-300 dark:ring-primary-900',
    }
    return variants[props.variant]
})

const dotColors = {
    default: 'bg-gray-400',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-red-500',
    info: 'bg-blue-500',
    primary: 'bg-primary-500',
}
</script>

<template>
    <span
        :class="[
            'badge font-semibold',
            size === 'lg' ? 'px-3 py-1 text-xs' : 'px-2.5 py-0.5 text-[11px]',
            classes,
        ]"
    >
        <span v-if="dot" :class="['w-1.5 h-1.5 rounded-full mr-1.5', dotColors[variant]]" />
        <slot />
    </span>
</template>
