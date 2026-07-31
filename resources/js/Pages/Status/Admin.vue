<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Badge from '@/Components/Badge.vue';
import {
    PlusIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    WrenchScrewdriverIcon,
    ServerIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const props = defineProps({
    services: Array,
    activeIncidents: Array,
    resolvedIncidents: Array,
    maintenance: Array,
    uptimeStats: Object,
    statusOptions: Array,
    severityOptions: Array,
    incidentStatusOptions: Array,
});

const showIncidentForm = ref(false);
const showMaintenanceForm = ref(false);
const updatingServiceId = ref(null);

const incidentForm = useForm({
    title: '',
    severity: 'minor',
    initial_message: '',
    service_ids: [],
    affected_services: [],
});

const maintenanceForm = useForm({
    title: '',
    description: '',
    scheduled_start: '',
    scheduled_end: '',
    affected_services: [],
});

const updateServiceForm = useForm({
    status: '',
    description: '',
});

const resolveForm = useForm({
    resolution_notes: '',
});

const updateIncidentForm = useForm({
    message: '',
    status: '',
});

const submitIncident = () => {
    incidentForm.post(route('admin.status.incidents.create'), {
        onSuccess: () => {
            showIncidentForm.value = false;
            incidentForm.reset();
        },
    });
};

const submitMaintenance = () => {
    maintenanceForm.post(route('admin.status.maintenance.create'), {
        onSuccess: () => {
            showMaintenanceForm.value = false;
            maintenanceForm.reset();
        },
    });
};

const updateService = (service) => {
    updatingServiceId.value = service.id;
    updateServiceForm.status = service.status;
    updateServiceForm.description = service.description;
    updateServiceForm.post(route('admin.status.services.update', service.id), {
        onSuccess: () => { updatingServiceId.value = null; },
    });
};

const totalServices = computed(() => props.services?.length || 0);
const operationalCount = computed(() => props.services?.filter(s => s.status === 'operational').length || 0);
const outageCount = computed(() => props.services?.filter(s => ['partial_outage', 'major_outage'].includes(s.status)).length || 0);

const toggleService = (id) => {
    const idx = maintenanceForm.affected_services.indexOf(id);
    if (idx === -1) {
        maintenanceForm.affected_services.push(id);
    } else {
        maintenanceForm.affected_services.splice(idx, 1);
    }
};

const completeMaintenance = (maintenance) => {
    if (confirm('Selesaikan pemeliharaan ini? Semua layanan akan dikembalikan ke status operasional.')) {
        useForm().post(route('admin.status.maintenance.complete', maintenance.id));
    }
};

const cancelMaintenance = (maintenance) => {
    if (confirm('Batalkan pemeliharaan ini? Layanan akan dikembalikan ke status sebelumnya.')) {
        useForm().post(route('admin.status.maintenance.cancel', maintenance.id));
    }
};

const severityAlertClass = (sev) => {
    const map = {
        critical: 'border-l-red-500 bg-gradient-to-r from-red-50/50 to-transparent dark:from-red-950/10',
        major: 'border-l-orange-500 bg-gradient-to-r from-orange-50/50 to-transparent dark:from-orange-950/10',
        minor: 'border-l-amber-500 bg-gradient-to-r from-amber-50/50 to-transparent dark:from-amber-950/10',
    };
    return map[sev] || 'border-l-gray-300';
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Manajemen Status Sistem" description="Pantau dan kelola layanan sistem, insiden, dan pemeliharaan">
            <template #actions>
                <button @click="showIncidentForm = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-500 to-rose-600 text-white text-sm font-semibold shadow-lg shadow-red-500/25 hover:shadow-red-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200">
                    <ExclamationTriangleIcon class="w-5 h-5" />
                    Buat Insiden
                </button>
                <button @click="showMaintenanceForm = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-sm font-semibold shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200">
                    <WrenchScrewdriverIcon class="w-5 h-5" />
                    Jadwalkan Pemeliharaan
                </button>
                <button @click="$inertia.post(route('admin.status.seed-defaults'))" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-md active:scale-[0.98] transition-all duration-200">
                    <ServerIcon class="w-5 h-5" />
                    Inisialisasi Layanan
                </button>
            </template>
        </PageHeader>

        <div class="space-y-8 animate-fade-in">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-lg shadow-emerald-500/20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-white/80">Operasional</p>
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                                <CheckCircleIcon class="w-5 h-5" />
                            </div>
                        </div>
                        <p class="mt-3 text-3xl font-display font-bold tracking-tight">
                            {{ operationalCount }}<span class="text-lg text-white/60">/{{ totalServices }}</span>
                        </p>
                        <p class="mt-1 text-xs text-white/60">Layanan berjalan normal</p>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white shadow-lg shadow-amber-500/20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-white/80">Insiden Aktif</p>
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                                <ExclamationTriangleIcon class="w-5 h-5" />
                            </div>
                        </div>
                        <p class="mt-3 text-3xl font-display font-bold tracking-tight">
                            {{ activeIncidents?.length || 0 }}
                        </p>
                        <p class="mt-1 text-xs text-white/60">Memerlukan perhatian</p>
                    </div>
                </div>

                <div :class="[
                    'relative overflow-hidden rounded-2xl p-6 shadow-lg',
                    outageCount > 0
                        ? 'bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-red-500/20'
                        : 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-indigo-500/20'
                ]">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4" />
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/3 -translate-x-1/4" />
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-white/80">Layanan Terganggu</p>
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                                <XCircleIcon class="w-5 h-5" />
                            </div>
                        </div>
                        <p class="mt-3 text-3xl font-display font-bold tracking-tight">
                            {{ outageCount }}
                        </p>
                        <p class="mt-1 text-xs text-white/60">{{ outageCount > 0 ? 'Perlu tindakan segera' : 'Semua layanan baik' }}</p>
                    </div>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-sm">
                        <ServerIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Layanan Sistem</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ totalServices }} layanan terdaftar</p>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="service in services"
                        :key="service.id"
                        class="group relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 transition-all duration-300 hover:shadow-card-hover hover:-translate-y-0.5"
                    >
                        <div class="absolute inset-0 bg-gradient-to-br from-gray-50/50 to-transparent dark:from-gray-800/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ service.name }}</h4>
                                <StatusBadge :status="service.status" />
                            </div>
                            <p class="text-xs text-gray-400 mb-3 font-medium">{{ service.category }}</p>

                            <!-- Quick update -->
                            <select
                                v-model="updateServiceForm.status"
                                @change="updateService(service)"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs py-2 px-3 text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all"
                            >
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>

                            <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                                <span class="font-medium tabular-nums">{{ service.uptime_percentage }}% uptime</span>
                                <span v-if="service.response_time_ms" class="tabular-nums">{{ service.response_time_ms }}ms</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Incidents -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-sm">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Insiden Aktif
                            <span v-if="activeIncidents?.length" class="text-sm text-gray-400 ml-2 font-normal">({{ activeIncidents.length }})</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pantau dan kelola insiden aktif</p>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="activeIncidents?.length" class="space-y-4">
                        <div
                            v-for="incident in activeIncidents"
                            :key="incident.id"
                            :class="[
                                'relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 transition-all duration-300 hover:shadow-card-hover',
                                'border-l-4',
                                severityAlertClass(incident.severity),
                            ]"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <Badge :variant="incident.severity === 'critical' ? 'danger' : incident.severity === 'major' ? 'warning' : 'default'">
                                        {{ incident.severity }}
                                    </Badge>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ incident.title }}</h4>
                                </div>
                                <Badge>{{ incident.status }}</Badge>
                            </div>

                            <!-- Add update -->
                            <form
                                @submit.prevent="updateIncidentForm.post(route('admin.status.incidents.update', incident.id), { preserveScroll: true })"
                                class="flex gap-2 mb-3"
                            >
                                <input v-model="updateIncidentForm.message" placeholder="Pesan pembaruan..." class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all" required />
                                <select v-model="updateIncidentForm.status" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all w-36">
                                    <option v-for="opt in incidentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200">Kirim</button>
                            </form>

                            <!-- Resolve -->
                            <form
                                @submit.prevent="resolveForm.post(route('admin.status.incidents.resolve', incident.id), { preserveScroll: true })"
                                class="flex gap-2"
                            >
                                <input v-model="resolveForm.resolution_notes" placeholder="Catatan penyelesaian..." class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 transition-all" />
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 dark:border-emerald-600 text-xs font-semibold hover:bg-emerald-50 dark:hover:bg-emerald-950/30 hover:shadow-md active:scale-[0.98] transition-all duration-200">
                                    <CheckCircleIcon class="w-4 h-4" />
                                    Selesaikan
                                </button>
                            </form>

                            <!-- Timeline -->
                            <div v-if="incident.updates?.length" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 space-y-2">
                                <div v-for="u in incident.updates" :key="u.id" class="flex gap-3 text-xs">
                                    <span class="text-gray-400 w-16 flex-shrink-0 font-mono">{{ u.created_at?.slice(11, 16) }}</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ u.message }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-400 text-sm text-center py-12">
                        <CheckCircleIcon class="w-10 h-10 mx-auto mb-3 text-emerald-400" />
                        Tidak ada insiden aktif. Semua sistem berjalan normal.
                    </p>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
                        <WrenchScrewdriverIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pemeliharaan Terjadwal</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jadwalkan waktu perawatan sistem</p>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="maintenance?.length" class="space-y-3">
                        <div
                            v-for="m in maintenance"
                            :key="m.id"
                            class="group relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 transition-all duration-300 hover:shadow-card-hover"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ m.title }}</h4>
                                        <Badge :variant="m.status === 'completed' ? 'success' : m.status === 'cancelled' ? 'default' : 'warning'">
                                            {{ m.status === 'scheduled' ? 'Terjadwal' : m.status === 'completed' ? 'Selesai' : m.status === 'cancelled' ? 'Dibatalkan' : 'Aktif' }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400" v-if="m.description">{{ m.description }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400" />
                                            {{ m.scheduled_start }} → {{ m.scheduled_end }}
                                        </span>
                                        <span v-if="m.affected_services?.length" class="text-indigo-500 font-medium">
                                            {{ m.affected_services.length }} layanan terdampak
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                                    <button
                                        v-if="m.status === 'scheduled'"
                                        @click="completeMaintenance(m)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-xs font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200"
                                        title="Selesaikan Pemeliharaan"
                                    >
                                        <CheckCircleIcon class="w-4 h-4" />
                                        Selesai
                                    </button>
                                    <button
                                        v-if="m.status === 'scheduled'"
                                        @click="cancelMaintenance(m)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 text-xs font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-md active:scale-[0.98] transition-all duration-200"
                                        title="Batalkan Pemeliharaan"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-400 text-sm text-center py-12">
                        <WrenchScrewdriverIcon class="w-10 h-10 mx-auto mb-3 text-blue-400" />
                        Tidak ada pemeliharaan terjadwal.
                    </p>
                </div>
            </div>
        </div>

        <!-- Incident Creation Modal -->
        <div v-if="showIncidentForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showIncidentForm = false">
            <div class="w-full max-w-lg rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl shadow-black/10 animate-scale-in overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                            <ExclamationTriangleIcon class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Buat Insiden</h3>
                            <p class="text-xs text-white/70">Laporkan gangguan pada layanan sistem</p>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="submitIncident" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Judul</label>
                        <input v-model="incidentForm.title" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all" placeholder="contoh: Gangguan Server Database" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tingkat Keparahan</label>
                            <select v-model="incidentForm.severity" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all">
                                <option v-for="opt in severityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Layanan Terdampak</label>
                            <select v-model="incidentForm.service_ids" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all" multiple>
                                <option v-for="svc in services" :key="svc.id" :value="svc.id">{{ svc.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Pesan Awal</label>
                        <textarea v-model="incidentForm.initial_message" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-red-500 focus:ring-4 focus:ring-red-500/20 transition-all" rows="3" required />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showIncidentForm = false" class="px-5 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-500 to-rose-600 text-white text-sm font-semibold shadow-lg shadow-red-500/25 hover:shadow-red-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 disabled:opacity-60" :disabled="incidentForm.processing">
                            {{ incidentForm.processing ? 'Membuat...' : 'Buat Insiden' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Maintenance Modal -->
        <div v-if="showMaintenanceForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showMaintenanceForm = false">
            <div class="w-full max-w-lg rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl shadow-black/10 animate-scale-in overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm">
                            <WrenchScrewdriverIcon class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Jadwalkan Pemeliharaan</h3>
                            <p class="text-xs text-white/70">Rencanakan waktu perawatan sistem</p>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="submitMaintenance" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Judul</label>
                        <input v-model="maintenanceForm.title" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all" placeholder="contoh: Upgrade Server Database" required />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi</label>
                        <textarea v-model="maintenanceForm.description" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all" rows="2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Mulai</label>
                            <input v-model="maintenanceForm.scheduled_start" type="datetime-local" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all" required />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Selesai</label>
                            <input v-model="maintenanceForm.scheduled_end" type="datetime-local" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 transition-all" required />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Layanan Terdampak (kosongkan untuk semua layanan)</label>
                        <div class="grid grid-cols-2 gap-2 mt-2 max-h-40 overflow-y-auto">
                            <label
                                v-for="svc in services"
                                :key="svc.id"
                                class="flex items-center gap-2 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200"
                                :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 shadow-sm': maintenanceForm.affected_services.includes(svc.id) }"
                            >
                                <input
                                    type="checkbox"
                                    :checked="maintenanceForm.affected_services.includes(svc.id)"
                                    @change="toggleService(svc.id)"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ svc.name }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showMaintenanceForm = false" class="px-5 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-[0.98] transition-all">Batal</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-sm font-semibold shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 disabled:opacity-60" :disabled="maintenanceForm.processing">
                            {{ maintenanceForm.processing ? 'Menjadwalkan...' : 'Jadwalkan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
