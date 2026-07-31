<script setup>
defineProps({
    title: String,
    subtitle: { type: String, default: '' },
    action: { type: Object, default: null }, // { label, href, icon }
})
</script>

<template>
    <div class="glass-card p-5 lg:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 v-if="title" class="text-base font-bold text-gray-900 dark:text-white">{{ title }}</h3>
                <p v-if="subtitle" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ subtitle }}</p>
            </div>
            <slot name="action" />
        </div>
        <slot />
    </div>
</template>
