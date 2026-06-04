<script setup>
import { ref } from 'vue';
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

import { computed } from 'vue';
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Manajemen Status Sistem" description="Pantau dan kelola layanan sistem, insiden, dan pemeliharaan">
            <template #actions>
                <button @click="showIncidentForm = true" class="btn-primary">
                    <ExclamationTriangleIcon class="w-5 h-5" />
                    Buat Insiden
                </button>
                <button @click="showMaintenanceForm = true" class="btn-secondary">
                    <WrenchScrewdriverIcon class="w-5 h-5" />
                    Jadwalkan Pemeliharaan
                </button>
                <button @click="$inertia.post(route('admin.status.seed-defaults'))" class="btn-secondary">
                    <ServerIcon class="w-5 h-5" />
                    Inisialisasi Layanan
                </button>
            </template>
        </PageHeader>

        <div class="space-y-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="stat-card card-accent-emerald">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Operasional</p>
                    <p class="mt-2 text-3xl font-display font-bold text-gray-900 dark:text-white">
                        {{ operationalCount }}<span class="text-lg text-gray-400">/{{ totalServices }}</span>
                    </p>
                </div>
                <div class="stat-card card-accent-amber">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Insiden Aktif</p>
                    <p class="mt-2 text-3xl font-display font-bold text-gray-900 dark:text-white">
                        {{ activeIncidents?.length || 0 }}
                    </p>
                </div>
                <div class="stat-card" :class="outageCount > 0 ? 'card-accent-indigo' : ''">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Layanan Terganggu</p>
                    <p class="mt-2 text-3xl font-display font-bold" :class="outageCount > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white'">
                        {{ outageCount }}
                    </p>
                </div>
            </div>

            <!-- Services Grid -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Layanan Sistem</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="service in services"
                        :key="service.id"
                        class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 transition-all hover:shadow-card-hover"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ service.name }}</h4>
                            <StatusBadge :status="service.status" />
                        </div>
                        <p class="text-xs text-gray-400 mb-3">{{ service.category }}</p>

                        <!-- Quick update -->
                        <select
                            v-model="updateServiceForm.status"
                            @change="updateService(service)"
                            class="form-input text-xs py-1.5"
                        >
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>

                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            <span>{{ service.uptime_percentage }}% uptime</span>
                            <span v-if="service.response_time_ms">{{ service.response_time_ms }}ms</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insiden Aktif -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Insiden Aktif
                    <span v-if="activeIncidents?.length" class="text-sm text-gray-400 ml-2">{{ activeIncidents.length }}</span>
                </h3>
                <div v-if="activeIncidents?.length" class="space-y-4">
                    <div
                        v-for="incident in activeIncidents"
                        :key="incident.id"
                        class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
                    >
                        <div class="flex items-center justify-between mb-3">
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
                            class="flex gap-2 mb-2"
                        >
                            <input v-model="updateIncidentForm.message" placeholder="Pesan pembaruan..." class="form-input flex-1 text-xs" required />
                            <select v-model="updateIncidentForm.status" class="form-input w-36 text-xs">
                                <option v-for="opt in incidentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <button type="submit" class="btn-primary text-xs py-1.5 px-3">Kirim</button>
                        </form>

                        <!-- Selesaikan -->
                        <form
                            @submit.prevent="resolveForm.post(route('admin.status.incidents.resolve', incident.id), { preserveScroll: true })"
                            class="flex gap-2"
                        >
                            <input v-model="resolveForm.resolution_notes" placeholder="Catatan penyelesaian..." class="form-input flex-1 text-xs" />
                            <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                                <CheckCircleIcon class="w-4 h-4" />
                                Selesaikan
                            </button>
                        </form>

                        <!-- Timeline -->
                        <div v-if="incident.updates?.length" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                            <div v-for="u in incident.updates" :key="u.id" class="flex gap-2 text-xs">
                                <span class="text-gray-400 w-16 flex-shrink-0">{{ u.created_at?.slice(11, 16) }}</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ u.message }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-gray-400 text-sm text-center py-8">Tidak ada insiden aktif. Semua sistem berjalan normal.</p>
            </div>

            <!-- Maintenance -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pemeliharaan Terjadwal</h3>
                <div v-if="maintenance?.length" class="space-y-3">
                    <div
                        v-for="m in maintenance"
                        :key="m.id"
                        class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ m.title }}</h4>
                                    <Badge :variant="m.status === 'completed' ? 'success' : m.status === 'cancelled' ? 'default' : 'warning'">
                                        {{ m.status === 'scheduled' ? 'Terjadwal' : m.status === 'completed' ? 'Selesai' : m.status === 'cancelled' ? 'Dibatalkan' : 'Aktif' }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ m.description }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                    <span>{{ m.scheduled_start }} → {{ m.scheduled_end }}</span>
                                    <span v-if="m.affected_services?.length" class="text-indigo-400">
                                        {{ m.affected_services.length }} layanan terdampak
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <button
                                    v-if="m.status === 'scheduled'"
                                    @click="completeMaintenance(m)"
                                    class="btn-primary text-xs py-1.5 px-3"
                                    title="Selesaikan Pemeliharaan"
                                >
                                    <CheckCircleIcon class="w-4 h-4" />
                                    Selesai
                                </button>
                                <button
                                    v-if="m.status === 'scheduled'"
                                    @click="cancelMaintenance(m)"
                                    class="btn-secondary text-xs py-1.5 px-3"
                                    title="Batalkan Pemeliharaan"
                                >
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-gray-400 text-sm text-center py-8">Tidak ada pemeliharaan terjadwal.</p>
            </div>
        </div>

        <!-- Incident Creation Modal -->
        <div v-if="showIncidentForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showIncidentForm = false">
            <div class="glass-card p-6 w-full max-w-lg animate-scale-in">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Buat Insiden</h3>
                <form @submit.prevent="submitIncident" class="space-y-4">
                    <div>
                        <label class="form-label">Judul</label>
                        <input v-model="incidentForm.title" class="form-input" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Tingkat Keparahan</label>
                            <select v-model="incidentForm.severity" class="form-input">
                                <option v-for="opt in severityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Layanan Terdampak</label>
                            <select v-model="incidentForm.service_ids" class="form-input" multiple>
                                <option v-for="svc in services" :key="svc.id" :value="svc.id">{{ svc.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Pesan Awal</label>
                        <textarea v-model="incidentForm.initial_message" class="form-input" rows="3" required />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showIncidentForm = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" :disabled="incidentForm.processing">Buat</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Maintenance Modal -->
        <div v-if="showMaintenanceForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showMaintenanceForm = false">
            <div class="glass-card p-6 w-full max-w-lg animate-scale-in">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Jadwalkan Pemeliharaan</h3>
                <form @submit.prevent="submitMaintenance" class="space-y-4">
                    <div>
                        <label class="form-label">Judul</label>
                        <input v-model="maintenanceForm.title" class="form-input" required />
                    </div>
                    <div>
                        <label class="form-label">Deskripsi</label>
                        <textarea v-model="maintenanceForm.description" class="form-input" rows="2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Mulai</label>
                            <input v-model="maintenanceForm.scheduled_start" type="datetime-local" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Selesai</label>
                            <input v-model="maintenanceForm.scheduled_end" type="datetime-local" class="form-input" required />
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Layanan Terdampak (kosongkan untuk semua layanan)</label>
                        <div class="grid grid-cols-2 gap-2 mt-1 max-h-40 overflow-y-auto">
                            <label
                                v-for="svc in services"
                                :key="svc.id"
                                class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': maintenanceForm.affected_services.includes(svc.id) }"
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
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showMaintenanceForm = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary" :disabled="maintenanceForm.processing">Jadwalkan</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
