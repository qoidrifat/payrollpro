<script setup>
defineProps({
    title: String,
})
</script>

<template>
    <div class="glass-card p-6">
        <h3 v-if="title" class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ title }}</h3>
        <slot />
    </div>
</template>
