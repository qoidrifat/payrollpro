<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'success', 'warning', 'danger', 'info', 'primary'].includes(v),
    },
})

const classes = computed(() => {
    const variants = {
        default: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        warning: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        danger: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        info: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        primary: 'bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300',
    }
    return variants[props.variant]
})
</script>

<template>
    <span :class="['badge', classes]">
        <slot />
    </span>
</template>
