<script setup>
defineProps({
    type: { type: String, default: 'card' },
    rows: { type: Number, default: 3 },
})
</script>

<template>
    <div v-if="type === 'card'" class="glass-card p-6 space-y-4">
        <div class="skeleton h-4 w-1/3" />
        <div class="skeleton h-8 w-1/2" />
        <div class="skeleton h-4 w-2/3" />
    </div>

    <div v-else-if="type === 'table'" class="table-container p-6 space-y-3">
        <div class="skeleton h-10 w-full" />
        <div v-for="i in rows" :key="i" class="skeleton h-12 w-full" />
    </div>

    <div v-else-if="type === 'text'" class="space-y-3">
        <div v-for="i in rows" :key="i" class="skeleton h-4" :class="`w-${i % 2 === 0 ? 'full' : '3/4'}`" />
    </div>
</template>
