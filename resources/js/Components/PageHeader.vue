<script setup>
defineProps({
    title: String,
    description: { type: String, default: '' },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <div class="page-header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3">
                    <h1 class="gradient-text">{{ title }}</h1>
                    <slot name="badge" />
                </div>
                <p v-if="description" class="!mt-2">{{ description }}</p>
                <p v-if="subtitle" class="!mt-1 text-xs text-gray-400 dark:text-gray-500">{{ subtitle }}</p>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
