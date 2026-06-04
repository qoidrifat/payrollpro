<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: Object,
    trend: { type: Number, default: null },
    trendLabel: { type: String, default: '' },
    color: { type: String, default: 'indigo' },
})
</script>

<template>
    <div :class="['stat-card', `card-accent-${color}`]">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ title }}</p>
                <p class="mt-2 text-3xl font-display font-bold text-gray-900 dark:text-white">{{ value }}</p>
                <div v-if="trend !== null" class="mt-2 flex items-center gap-1">
                    <span
                        :class="[
                            'text-xs font-medium',
                            trend >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400',
                        ]"
                    >
                        {{ trend >= 0 ? '+' : '' }}{{ trend }}%
                    </span>
                    <span class="text-xs text-gray-400">{{ trendLabel }}</span>
                </div>
            </div>
            <div :class="[
                'flex-shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center',
                `bg-${color}-50 dark:bg-${color}-950`,
            ]">
                <component
                    v-if="icon"
                    :is="icon"
                    :class="[
                        'w-6 h-6',
                        `text-${color}-600 dark:text-${color}-400`,
                    ]"
                />
            </div>
        </div>
    </div>
</template>
