<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const config = computed(() => {
    const map = {
        operational:           { label: 'Operational',           color: 'emerald', pulse: false },
        degraded_performance:  { label: 'Degraded Performance',  color: 'amber',  pulse: true },
        partial_outage:        { label: 'Partial Outage',        color: 'orange', pulse: true },
        major_outage:          { label: 'Major Outage',          color: 'red',    pulse: true },
        maintenance:           { label: 'Under Maintenance',     color: 'blue',   pulse: false },
    };
    return map[props.status] || { label: 'Unknown', color: 'gray', pulse: false };
});
</script>

<template>
    <div :class="['flex items-center gap-2', size === 'lg' ? 'text-base' : 'text-sm']">
        <span class="relative flex items-center justify-center">
            <span
                v-if="config.pulse"
                :class="[
                    'absolute inline-flex rounded-full opacity-75 animate-ping',
                    size === 'lg' ? 'h-3 w-3' : 'h-2.5 w-2.5',
                    `bg-${config.color}-400`
                ]"
            />
            <span
                :class="[
                    'relative inline-flex rounded-full',
                    size === 'lg' ? 'h-3 w-3' : 'h-2.5 w-2.5',
                    `bg-${config.color}-500`
                ]"
            />
        </span>
        <span
            :class="[
                'font-medium',
                `text-${config.color}-700 dark:text-${config.color}-400`
            ]"
        >
            {{ config.label }}
        </span>
    </div>
</template>
