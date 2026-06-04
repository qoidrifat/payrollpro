<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ChevronDownIcon, ChevronRightIcon, ClipboardDocumentIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const expandedEndpoint = ref(null);
const copiedIndex = ref(null);

const toggleEndpoint = (index) => {
    expandedEndpoint.value = expandedEndpoint.value === index ? null : index;
};

const copyToClipboard = async (text, index) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedIndex.value = index;
        setTimeout(() => { copiedIndex.value = null; }, 2000);
    } catch {
        // fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        copiedIndex.value = index;
        setTimeout(() => { copiedIndex.value = null; }, 2000);
    }
};

const endpoints = [
    {
        method: 'GET',
        path: '/api/mobile/status',
        title: 'Today\'s Attendance Status',
        description: 'Mendapatkan status absensi karyawan untuk hari ini. Menampilkan data clock-in/clock-out terkini.',
        auth: 'Bearer Token',
        rateLimit: '60 req/menit',
        requestExample: null,
        responseExample: `{
  "date": "2026-06-02",
  "attendance": {
    "id": 42,
    "date": "2026-06-02",
    "clock_in": "07:45:00",
    "clock_out": null,
    "status": "present",
    "type": "wfo",
    "latitude": -7.0456,
    "longitude": 112.7654
  },
  "has_clocked_in": true,
  "has_clocked_out": false
}`,
        parameters: [],
        responses: [
            { code: 200, description: 'Status absensi hari ini berhasil diambil' },
            { code: 403, description: 'Profil karyawan tidak ditemukan' },
        ],
    },
    {
        method: 'POST',
        path: '/api/mobile/clock-in',
        title: 'Clock In',
        description: 'Melakukan absensi masuk (clock-in) dengan data GPS dan foto selfie opsional. Posisi GPS harus berada dalam area geofence kantor. Hanya satu clock-in per hari. Mendeteksi keterlambatan berdasarkan shift yang dijadwalkan.',
        auth: 'Bearer Token',
        rateLimit: '60 req/menit',
        requestExample: `Content-Type: multipart/form-data

{
  "latitude": -7.0456,
  "longitude": 112.7654,
  "gps_accuracy": 5.0,
  "device_info": "Samsung Galaxy S24 / Android 14",
  "selfie_image": <file>  // optional, max 5MB
}`,
        responseExample: `{
  "message": "Absen masuk berhasil.",
  "attendance": {
    "id": 42,
    "date": "2026-06-02",
    "clock_in": "07:45:00",
    "clock_out": null,
    "status": "present",
    "type": "wfo",
    "latitude": -7.0456,
    "longitude": 112.7654
  }
}`,
        parameters: [
            { name: 'latitude', type: 'number', required: true, description: 'Latitude GPS (-90 s.d. 90)' },
            { name: 'longitude', type: 'number', required: true, description: 'Longitude GPS (-180 s.d. 180)' },
            { name: 'gps_accuracy', type: 'number', required: false, description: 'Akurasi GPS dalam meter (opsional)' },
            { name: 'device_info', type: 'string', required: false, description: 'Informasi perangkat (opsional)' },
            { name: 'selfie_image', type: 'file', required: false, description: 'Foto selfie, max 5MB, format JPG/PNG (opsional)' },
        ],
        responses: [
            { code: 201, description: 'Clock-in berhasil' },
            { code: 200, description: 'Sudah clock-in hari ini' },
            { code: 403, description: 'Profil karyawan tidak ditemukan' },
            { code: 422, description: 'Di luar area kantor / validasi gagal' },
            { code: 500, description: 'Internal server error' },
        ],
    },
    {
        method: 'POST',
        path: '/api/mobile/clock-out',
        title: 'Clock Out',
        description: 'Melakukan absensi pulang (clock-out) dengan data GPS. Posisi GPS harus berada dalam area geofence kantor. Harus sudah clock-in hari ini. Hanya satu clock-out per hari.',
        auth: 'Bearer Token',
        rateLimit: '60 req/menit',
        requestExample: `{
  "latitude": -7.0456,
  "longitude": 112.7654,
  "gps_accuracy": 5.0,
  "device_info": "Samsung Galaxy S24 / Android 14"
}`,
        responseExample: `{
  "message": "Absen pulang berhasil.",
  "attendance": {
    "id": 42,
    "date": "2026-06-02",
    "clock_in": "07:45:00",
    "clock_out": "16:30:00",
    "status": "present",
    "type": "wfo",
    "latitude": -7.0456,
    "longitude": 112.7654
  }
}`,
        parameters: [
            { name: 'latitude', type: 'number', required: true, description: 'Latitude GPS (-90 s.d. 90)' },
            { name: 'longitude', type: 'number', required: true, description: 'Longitude GPS (-180 s.d. 180)' },
            { name: 'gps_accuracy', type: 'number', required: false, description: 'Akurasi GPS dalam meter (opsional)' },
            { name: 'device_info', type: 'string', required: false, description: 'Informasi perangkat (opsional)' },
        ],
        responses: [
            { code: 200, description: 'Clock-out berhasil' },
            { code: 400, description: 'Belum clock-in hari ini' },
            { code: 403, description: 'Profil karyawan tidak ditemukan' },
            { code: 422, description: 'Di luar area kantor / validasi gagal' },
            { code: 500, description: 'Internal server error' },
        ],
    },
    {
        method: 'POST',
        path: '/api/mobile/sync-offline',
        title: 'Sync Offline Records',
        description: 'Mengirim batch catatan absensi offline yang direkam saat tidak ada koneksi internet. Maksimal 30 records per request. Records akan di- upsert berdasarkan employee_id + date.',
        auth: 'Bearer Token',
        rateLimit: '60 req/menit',
        requestExample: `{
  "records": [
    {
      "date": "2026-06-01",
      "clock_in": "07:30",
      "clock_out": "16:45",
      "latitude": -7.0456,
      "longitude": 112.7654,
      "type": "wfo",
      "status": "present"
    }
  ]
}`,
        responseExample: `{
  "message": "15 catatan absensi berhasil disinkronkan.",
  "synced": 15
}`,
        parameters: [
            { name: 'records', type: 'array', required: true, description: 'Array catatan absensi offline (max 30 items)' },
            { name: 'records[].date', type: 'string (date)', required: true, description: 'Tanggal absensi (YYYY-MM-DD)' },
            { name: 'records[].clock_in', type: 'string', required: false, description: 'Waktu clock-in (HH:mm)' },
            { name: 'records[].clock_out', type: 'string', required: false, description: 'Waktu clock-out (HH:mm)' },
            { name: 'records[].latitude', type: 'number', required: false, description: 'Latitude GPS' },
            { name: 'records[].longitude', type: 'number', required: false, description: 'Longitude GPS' },
            { name: 'records[].type', type: 'string (enum)', required: false, description: 'wfo | wfh | remote' },
            { name: 'records[].status', type: 'string (enum)', required: false, description: 'present | absent | late | half_day | sick | leave' },
        ],
        responses: [
            { code: 200, description: 'Sinkronisasi berhasil' },
            { code: 403, description: 'Profil karyawan tidak ditemukan' },
        ],
    },
];

const methodClass = (method) => {
    const map = {
        GET: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
        POST: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
        PUT: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
        DELETE: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
    };
    return map[method] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
};
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Mobile API Documentation" description="Dokumentasi lengkap API Mobile untuk aplikasi absensi karyawan." />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Info Card -->
            <div class="glass-card p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary-100 dark:bg-primary-900 flex items-center justify-center flex-shrink-0">
                        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">API</span>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-lg font-display font-bold text-gray-900 dark:text-white">PayrollPro Mobile API v1</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                            RESTful API untuk integrasi aplikasi mobile (Android/iOS) dengan sistem absensi PayrollPro.
                            Seluruh endpoint menggunakan <strong>Sanctum Token Authentication</strong> dan menerapkan
                            <strong>rate limiting</strong> 60 request per menit per pengguna.
                        </p>
                        <div class="flex flex-wrap gap-3 pt-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400">
                                Bearer Auth
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                                60 req/menit
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                JSON
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication Section -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-display font-bold text-gray-900 dark:text-white mb-4">Authentication</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Semua endpoint memerlukan <strong>Bearer Token</strong> (Sanctum API Token).
                    Token diperoleh setelah login, dikirim via header HTTP:
                </p>
                <div class="relative bg-gray-900 dark:bg-gray-950 rounded-xl p-4 font-mono text-sm text-gray-100 overflow-x-auto">
                    <code>Authorization: Bearer {api_token}</code>
                    <button
                        class="absolute top-2 right-2 p-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white transition-colors"
                        @click="copyToClipboard('Authorization: Bearer {api_token}', 'auth')"
                    >
                        <ClipboardDocumentIcon v-if="copiedIndex !== 'auth'" class="w-4 h-4" />
                        <CheckIcon v-else class="w-4 h-4 text-emerald-400" />
                    </button>
                </div>
            </div>

            <!-- Endpoints -->
            <div class="space-y-3">
                <h3 class="text-lg font-display font-bold text-gray-900 dark:text-white px-1">Endpoints</h3>

                <div
                    v-for="(ep, idx) in endpoints"
                    :key="idx"
                    class="glass-card overflow-hidden transition-all duration-200"
                >
                    <!-- Endpoint Header -->
                    <button
                        class="w-full flex items-center gap-4 p-5 text-left hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                        @click="toggleEndpoint(idx)"
                    >
                        <span :class="['flex-shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold tracking-wider uppercase', methodClass(ep.method)]">
                            {{ ep.method }}
                        </span>
                        <code class="flex-1 text-sm font-mono font-medium text-gray-700 dark:text-gray-300 truncate">
                            {{ ep.path }}
                        </code>
                        <ChevronDownIcon
                            v-if="expandedEndpoint === idx"
                            class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform"
                        />
                        <ChevronRightIcon
                            v-else
                            class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform"
                        />
                    </button>

                    <!-- Expanded Content -->
                    <div v-if="expandedEndpoint === idx" class="border-t border-gray-100 dark:border-gray-800">
                        <div class="p-5 space-y-5">
                            <!-- Description -->
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">{{ ep.title }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ ep.description }}</p>
                            </div>

                            <!-- Metadata Tags -->
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                    {{ ep.auth }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    {{ ep.rateLimit }}
                                </span>
                            </div>

                            <!-- Parameters -->
                            <div v-if="ep.parameters.length > 0">
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Parameters</h5>
                                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                                <th class="px-4 py-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Name</th>
                                                <th class="px-4 py-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Type</th>
                                                <th class="px-4 py-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Required</th>
                                                <th class="px-4 py-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            <tr v-for="(param, pidx) in ep.parameters" :key="pidx" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                                <td class="px-4 py-2.5 font-mono text-xs text-primary-600 dark:text-primary-400">{{ param.name }}</td>
                                                <td class="px-4 py-2.5 text-xs text-gray-500">{{ param.type }}</td>
                                                <td class="px-4 py-2.5">
                                                    <span v-if="param.required" class="text-xs font-medium text-red-500 dark:text-red-400">Required</span>
                                                    <span v-else class="text-xs text-gray-400">Optional</span>
                                                </td>
                                                <td class="px-4 py-2.5 text-xs text-gray-500">{{ param.description }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Request Example -->
                            <div v-if="ep.requestExample">
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Request Example</h5>
                                    <button
                                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        @click="copyToClipboard(ep.requestExample, `req-${idx}`)"
                                    >
                                        <ClipboardDocumentIcon v-if="copiedIndex !== `req-${idx}`" class="w-3.5 h-3.5" />
                                        <CheckIcon v-else class="w-3.5 h-3.5 text-emerald-400" />
                                        {{ copiedIndex === `req-${idx}` ? 'Copied!' : 'Copy' }}
                                    </button>
                                </div>
                                <pre class="bg-gray-900 dark:bg-gray-950 rounded-xl p-4 overflow-x-auto text-xs text-gray-100 font-mono leading-relaxed"><code>{{ ep.requestExample }}</code></pre>
                            </div>

                            <!-- Response Example -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="text-sm font-semibold text-gray-900 dark:text-white">Response Example</h5>
                                    <button
                                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        @click="copyToClipboard(ep.responseExample, `res-${idx}`)"
                                    >
                                        <ClipboardDocumentIcon v-if="copiedIndex !== `res-${idx}`" class="w-3.5 h-3.5" />
                                        <CheckIcon v-else class="w-3.5 h-3.5 text-emerald-400" />
                                        {{ copiedIndex === `res-${idx}` ? 'Copied!' : 'Copy' }}
                                    </button>
                                </div>
                                <pre class="bg-gray-900 dark:bg-gray-950 rounded-xl p-4 overflow-x-auto text-xs text-gray-100 font-mono leading-relaxed"><code>{{ ep.responseExample }}</code></pre>
                            </div>

                            <!-- Response Codes -->
                            <div>
                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Response Codes</h5>
                                <div class="space-y-1.5">
                                    <div v-for="(res, ridx) in ep.responses" :key="ridx" class="flex items-center gap-3 text-sm">
                                        <span :class="[
                                            'flex-shrink-0 w-10 text-center px-2 py-0.5 rounded-md text-xs font-bold',
                                            res.code < 300
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                                : res.code < 500
                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
                                                    : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
                                        ]">{{ res.code }}</span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ res.description }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Reference -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-display font-bold text-gray-900 dark:text-white mb-4">Error Reference</h3>
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <th class="px-4 py-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Code</th>
                                <th class="px-4 py-2.5 text-left font-medium text-gray-600 dark:text-gray-400">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">200</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Success</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">201</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Created (clock-in sukses)</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">400</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Bad Request (data tidak valid / belum clock-in)</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">403</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Forbidden (profil karyawan tidak ditemukan)</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">422</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Unprocessable Entity (di luar area kantor / validasi gagal)</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">429</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Too Many Requests (rate limit exceeded)</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400">500</span></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500">Internal Server Error</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- OpenAPI Spec Link -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-display font-bold text-gray-900 dark:text-white mb-2">OpenAPI Specification</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    File spesifikasi OpenAPI 3.0 tersedia untuk integrasi dengan tools seperti Swagger UI, Postman, atau Insomnia.
                </p>
                <a
                    href="/docs/mobile-api.yaml"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800 hover:bg-primary-100 dark:hover:bg-primary-900 transition-colors text-sm font-medium"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download openapi.yaml
                </a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
