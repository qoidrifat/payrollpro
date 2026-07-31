<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: Object,
    trend: { type: Number, default: null },
    trendLabel: { type: String, default: '' },
    color: { type: String, default: 'indigo' },
    subtitle: { type: String, default: '' },
})
</script>

<template>
    <div :class="['stat-card group', `card-accent-${color}`]">
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ title }}</p>
                <p class="mt-1.5 text-2xl lg:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight truncate">
                    {{ value }}
                </p>
                <div v-if="subtitle" class="mt-0.5">
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ subtitle }}</p>
                </div>
                <div v-if="trend !== null" class="mt-2 flex items-center gap-1.5">
                    <span
                        :class="[
                            'inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-md',
                            trend >= 0
                                ? 'text-emerald-700 bg-emerald-50 dark:text-emerald-300 dark:bg-emerald-950/60'
                                : 'text-red-700 bg-red-50 dark:text-red-300 dark:bg-red-950/60',
                        ]"
                    >
                        <svg v-if="trend >= 0" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                        <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                        {{ trend >= 0 ? '+' : '' }}{{ trend }}%
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ trendLabel }}</span>
                </div>
            </div>
            <div
                v-if="icon"
                :class="[
                    'flex-shrink-0 w-11 h-11 lg:w-12 lg:h-12 rounded-2xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg',
                    `bg-${color}-50 dark:bg-${color}-950`,
                ]"
            >
                <component
                    :is="icon"
                    :class="['w-5 h-5 lg:w-6 lg:h-6', `text-${color}-600 dark:text-${color}-400`]"
                />
            </div>
        </div>
    </div>
</template>
