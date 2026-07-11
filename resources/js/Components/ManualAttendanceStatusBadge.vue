<script setup>
import { computed } from 'vue'

const props = defineProps({
    status: { type: String, default: 'pending' },
})

const label = computed(() => ({
    pending: 'Menunggu Verifikasi',
    approved: 'Disetujui',
    rejected: 'Ditolak',
})[props.status] || props.status)

const classes = computed(() => ({
    pending: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200',
    rejected: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200',
})[props.status] || 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200')
</script>

<template>
    <span :class="['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold', classes]">
        {{ label }}
    </span>
</template>
