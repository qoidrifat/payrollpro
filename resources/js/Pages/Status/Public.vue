<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import axios from 'axios';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ClockIcon,
    ChevronDownIcon,
    ArrowPathIcon,
    WrenchScrewdriverIcon,
    ServerIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    overview: Object,
});

const liveData = ref(props.overview);
const expandedIncidents = ref(new Set());
const refreshing = ref(false);
const lastUpdated = ref(new Date());
let pollTimer = null;

const toggleIncident = (id) => {
    if (expandedIncidents.value.has(id)) {
        expandedIncidents.value.delete(id);
    } else {
        expandedIncidents.value.add(id);
    }
};

const refreshStatus = async () => {
    refreshing.value = true;
    try {
        const { data } = await axios.get('/api/status');
        liveData.value = data;
        lastUpdated.value = new Date();
    } catch { /* silently fail */ }
    refreshing.value = false;
};

onMounted(() => {
    pollTimer = setInterval(refreshStatus, 30000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const overallBadge = computed(() => {
    const m = liveData.value?.overall_status?.status;
    if (m === 'operational') return { icon: CheckCircleIcon, color: 'emerald', bg: 'from-emerald-500 to-teal-500' };
    if (m === 'degraded') return { icon: ExclamationTriangleIcon, color: 'amber', bg: 'from-amber-500 to-yellow-500' };
    return { icon: XCircleIcon, color: 'red', bg: 'from-red-500 to-rose-500' };
});

const lastUpdatedText = computed(() => {
    const s = Math.floor((new Date() - lastUpdated.value) / 1000);
    if (s < 60) return 'Baru saja';
    if (s < 120) return '1 menit yang lalu';
    return `${Math.floor(s / 60)} menit yang lalu`;
});

const categoryIcons = {
    'Core Services': ServerIcon,
    'Infrastructure': WrenchScrewdriverIcon,
};
</script>

<template>
    <Head title="Status Sistem" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950">
        <!-- Header -->
        <header class="border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md">
            <div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="/" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            ← PayrollPro
                        </a>
                        <span class="text-gray-300 dark:text-gray-600">/</span>
                        <h1 class="text-xl font-display font-bold text-gray-900 dark:text-white">Status Sistem</h1>
                    </div>
                    <button
                        @click="refreshStatus"
                        :class="['p-2 rounded-xl transition-colors', refreshing ? 'animate-spin' : '']"
                    >
                        <ArrowPathIcon class="w-5 h-5 text-gray-400" />
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            <!-- Overall Status Banner -->
            <div :class="[
                'relative overflow-hidden rounded-3xl p-8 text-white',
                `bg-gradient-to-br ${overallBadge.bg}`
            ]">
                <div class="absolute inset-0 bg-white/10 backdrop-blur-[1px]" />
                <div class="relative z-10">
                    <div class="flex items-center gap-3">
                        <component :is="overallBadge.icon" class="w-8 h-8" />
                        <div>
                            <p class="text-2xl font-display font-bold">
                                {{ liveData?.overall_status?.label || 'Checking...' }}
                            </p>
                            <p class="text-sm text-white/80 mt-1">
                                Updated {{ lastUpdatedText }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Grid -->
            <div v-for="(services, category) in liveData?.service_categories" :key="category" class="space-y-4">
                <div class="flex items-center gap-2">
                    <component
                        :is="categoryIcons[category] || ServerIcon"
                        class="w-5 h-5 text-gray-400 dark:text-gray-500"
                    />
                    <h2 class="text-base font-semibold text-gray-700 dark:text-gray-300">{{ category }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="service in services"
                        :key="service.id"
                        class="glass-card p-5 transition-all duration-300 hover:shadow-card-hover group animate-fade-in"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ service.name }}</h3>
                            <StatusBadge :status="service.status" />
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 line-clamp-2 mb-3">
                            {{ service.description }}
                        </p>
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span>{{ service.uptime_percentage }}% uptime</span>
                            <span v-if="service.response_time_ms">{{ service.response_time_ms }}ms</span>
                        </div>
                        <!-- Uptime bar -->
                        <div class="mt-3 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div
                                :class="[
                                    'h-full rounded-full transition-all duration-700',
                                    service.uptime_percentage >= 99.9 ? 'bg-emerald-500'
                                    : service.uptime_percentage >= 99 ? 'bg-amber-500'
                                    : 'bg-red-500'
                                ]"
                                :style="{ width: `${service.uptime_percentage}%` }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Incidents -->
            <div v-if="liveData?.active_incidents?.length" class="space-y-4">
                <div class="flex items-center gap-2">
                    <ExclamationTriangleIcon class="w-5 h-5 text-red-500" />
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Insiden Aktif</h2>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="incident in liveData.active_incidents"
                        :key="incident.id"
                        class="glass-card border-l-4 p-5 cursor-pointer transition-all duration-300 hover:shadow-card-hover"
                        :class="{
                            'border-l-red-500': incident.severity === 'critical',
                            'border-l-orange-500': incident.severity === 'major',
                            'border-l-amber-500': incident.severity === 'minor',
                        }"
                        @click="toggleIncident(incident.id)"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-xs font-semibold uppercase',
                                            incident.severity === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400'
                                            : incident.severity === 'major' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-400'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
                                        ]"
                                    >
                                        {{ incident.severity }}
                                    </span>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ incident.title }}</h3>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ incident.status }} — Dimulai {{ incident.started_at }}
                                </p>
                            </div>
                            <ChevronDownIcon
                                :class="[
                                    'w-5 h-5 text-gray-400 transition-transform duration-300',
                                    expandedIncidents.has(incident.id) ? 'rotate-180' : '',
                                ]"
                            />
                        </div>
                        <div v-if="expandedIncidents.has(incident.id) && incident.updates?.length" class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                            <div
                                v-for="update in incident.updates"
                                :key="update.id"
                                class="flex gap-3 text-sm"
                            >
                                <div class="flex-shrink-0 mt-0.5">
                                    <div class="w-2 h-2 rounded-full bg-gray-400" />
                                </div>
                                <div>
                                    <p class="text-gray-700 dark:text-gray-300">{{ update.message }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ update.created_at }} · {{ update.status }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance -->
            <div v-if="liveData?.upcoming_maintenance?.length || liveData?.active_maintenance?.length" class="space-y-4">
                <div class="flex items-center gap-2">
                    <WrenchScrewdriverIcon class="w-5 h-5 text-blue-500" />
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Pemeliharaan Terjadwal</h2>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="m in [...(liveData.active_maintenance || []), ...(liveData.upcoming_maintenance || [])]"
                        :key="m.id"
                        class="glass-card p-5 border-l-4 border-l-blue-500"
                    >
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ m.title }}</h3>
                        <p class="text-sm text-gray-500 mt-1" v-if="m.description">{{ m.description }}</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            <span>{{ m.scheduled_start }} → {{ m.scheduled_end }}</span>
                            <span v-if="m.status === 'active'" class="text-blue-600 font-medium">Sedang Berlangsung</span>
                            <span v-else class="text-gray-400">Terjadwal</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Past Incidents -->
            <div v-if="liveData?.resolved_incidents?.length" class="space-y-4">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-5 h-5 text-emerald-500" />
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Insiden Terselesaikan Terbaru</h2>
                </div>
                <div class="glass-card overflow-hidden">
                    <div
                        v-for="incident in liveData.resolved_incidents"
                        :key="incident.id"
                        class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ incident.title }}</span>
                                <span class="text-xs text-gray-400 ml-2">{{ incident.duration_in_minutes }}m</span>
                            </div>
                            <span class="text-xs text-gray-400">Terselesaikan {{ incident.resolved_at }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="text-center py-8 border-t border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-400">
                    Status PayrollPro · Didukung oleh PayrollPro Monitoring · Terakhir diperiksa {{ lastUpdatedText }}
                </p>
            </footer>
        </main>
    </div>
</template>
