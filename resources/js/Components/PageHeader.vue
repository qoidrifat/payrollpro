<script setup>
defineProps({
    title: String,
    description: { type: String, default: '' },
})
</script>

<template>
    <div class="page-header">
        <h1>{{ title }}</h1>
        <p v-if="description">{{ description }}</p>
        <div v-if="$slots.actions" class="mt-4 flex items-center gap-3">
            <slot name="actions" />
        </div>
    </div>
</template>
