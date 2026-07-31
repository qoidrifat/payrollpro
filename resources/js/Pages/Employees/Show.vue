<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { PencilIcon, ArrowLeftIcon, QrCodeIcon, PhoneIcon, MapPinIcon, BuildingOfficeIcon, BanknotesIcon, IdentificationIcon } from '@heroicons/vue/24/outline';
import QrCode from '@/Components/QrCode.vue';

const page = usePage();
const employee = page.props.employee;

const statusVariant = (status) => {
    const map = { permanent: 'success', contract: 'info', probation: 'warning', intern: 'default' };
    return map[status] || 'default';
};

const formatCurrency = (val) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader :title="employee.full_name" :description="employee.position">
            <template #badge>
                <Badge :variant="employee.is_active ? 'success' : 'danger'">
                    {{ employee.is_active ? 'Aktif' : 'Tidak Aktif' }}
                </Badge>
            </template>
            <template #actions>
                <Link :href="route('employees.index')" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" /> Kembali
                </Link>
                <Link :href="route('employees.edit', employee.id)" class="btn-primary">
                    <PencilIcon class="w-5 h-5" /> Ubah
                </Link>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Card -->
                <div class="glass-card overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-primary-500 via-purple-500 to-indigo-500" />
                    <div class="p-6">
                        <div class="flex items-center gap-5 mb-6">
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg ring-4 ring-white dark:ring-gray-900">
                                <span class="text-3xl font-extrabold text-white">
                                    {{ employee.first_name?.charAt(0) }}{{ employee.last_name?.charAt(0) || '' }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">{{ employee.full_name }}</h3>
                                <div class="flex items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <BuildingOfficeIcon class="w-4 h-4" />
                                    {{ employee.position }}
                                </div>
                                <div class="mt-2">
                                    <Badge :variant="statusVariant(employee.employment_status)">{{ employee.employment_status }}</Badge>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="p-4 rounded-xl bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <IdentificationIcon class="w-4 h-4 text-gray-400" />
                                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">NIK</span>
                                </div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ employee.nik }}</p>
                            </div>
                            <div class="p-4 rounded-xl bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <PhoneIcon class="w-4 h-4 text-gray-400" />
                                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Telepon</span>
                                </div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ employee.phone || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Details -->
                <div class="glass-card p-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5">Detail Kepegawaian</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                            <dt class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Jabatan</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.position }}</dd>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                            <dt class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Departemen</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.department || '-' }}</dd>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                            <dt class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Tanggal Masuk</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.join_date }}</dd>
                        </div>
                        <div class="p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/40 dark:to-green-950/40 border border-emerald-100 dark:border-emerald-900/30">
                            <dt class="text-[11px] font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-wider">Gaji Pokok</dt>
                            <dd class="mt-1.5 text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(employee.base_salary) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Bank & BPJS -->
                <div class="glass-card p-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5">Bank & BPJS</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                            <dt class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Bank</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.bank_name || '-' }}</dd>
                        </div>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50">
                            <dt class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">No. Rekening</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.bank_account_number || '-' }}</dd>
                        </div>
                        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/40">
                            <dt class="text-[11px] font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wider">BPJS Kesehatan</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.bpjs_kesehatan || '-' }}</dd>
                        </div>
                        <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-950/40">
                            <dt class="text-[11px] font-bold text-purple-500 dark:text-purple-400 uppercase tracking-wider">BPJS Ketenagakerjaan</dt>
                            <dd class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-white">{{ employee.bpjs_ketenagakerjaan || '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-5">
                <!-- Avatar Card -->
                <div class="glass-card p-6 text-center">
                    <div class="w-24 h-24 mx-auto rounded-2xl bg-gradient-to-br from-primary-500 via-purple-500 to-indigo-600 flex items-center justify-center shadow-xl ring-4 ring-white dark:ring-gray-900 mb-4">
                        <span class="text-3xl font-extrabold text-white">
                            {{ employee.first_name?.charAt(0) }}{{ employee.last_name?.charAt(0) || '' }}
                        </span>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white">{{ employee.full_name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ employee.position }}</p>
                    <div class="mt-3">
                        <Badge :variant="employee.is_active ? 'success' : 'danger'" size="lg">
                            {{ employee.is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </Badge>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="glass-card p-6">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <PhoneIcon class="w-4 h-4 text-gray-400" />
                        Kontak Darurat
                    </h3>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ employee.emergency_contact_name || '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ employee.emergency_contact_phone || '-' }}</p>
                </div>

                <!-- Address -->
                <div v-if="employee.address" class="glass-card p-6">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <MapPinIcon class="w-4 h-4 text-gray-400" />
                        Alamat
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ [employee.address, employee.city, employee.province, employee.postal_code].filter(Boolean).join(', ') }}
                    </p>
                </div>

                <!-- Notes -->
                <div v-if="employee.notes" class="glass-card p-6">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Catatan</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ employee.notes }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
