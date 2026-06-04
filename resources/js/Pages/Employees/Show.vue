<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Badge from '@/Components/Badge.vue';
import { PencilIcon, ArrowLeftIcon, QrCodeIcon } from '@heroicons/vue/24/outline';
import QrCode from '@/Components/QrCode.vue';

const page = usePage();
const employee = page.props.employee;

const statusVariant = (status) => {
    const map = {
        permanent: 'success',
        contract: 'info',
        probation: 'warning',
        intern: 'default',
    };
    return map[status] || 'default';
};

const formatCurrency = (val) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader :title="employee.full_name" :description="employee.position">
            <template #actions>
                <Link href="/employees" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali
                </Link>
                <Link :href="`/employees/${employee.id}/edit`" class="btn-primary">
                    <PencilIcon class="w-5 h-5" />
                    Ubah
                </Link>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="glass-card p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Data Pribadi</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">NIK</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.nik }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">NPWP</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.npwp || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Jenis Kelamin</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ employee.gender }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Telepon</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.phone || '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Alamat</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ [employee.address, employee.city, employee.province, employee.postal_code].filter(Boolean).join(', ') || '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="glass-card p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Kepegawaian</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Jabatan</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.position }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Departemen</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.department || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Status</dt>
                            <dd class="mt-1"><Badge :variant="statusVariant(employee.employment_status)">{{ employee.employment_status }}</Badge></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Tanggal Masuk</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.join_date }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Gaji Pokok</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(employee.base_salary) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="glass-card p-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Bank & BPJS</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Bank</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.bank_name || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">Nomor Rekening</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.bank_account_number || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">BPJS Kesehatan</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.bpjs_kesehatan || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase tracking-wider">BPJS Ketenagakerjaan</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ employee.bpjs_ketenagakerjaan || '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="glass-card p-6 text-center">
                    <div class="w-20 h-20 mx-auto rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center mb-4">
                        <span class="text-2xl font-display font-bold text-primary-700 dark:text-primary-300">
                            {{ employee.first_name?.charAt(0) }}{{ employee.last_name?.charAt(0) || '' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ employee.full_name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ employee.position }}</p>
                    <Badge :variant="employee.is_active ? 'success' : 'danger'" class="mt-2">
                        {{ employee.is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </Badge>
                </div>

                <div class="glass-card p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Kontak Darurat</h3>
                    <p class="text-sm text-gray-900 dark:text-white">{{ employee.emergency_contact_name || '-' }}</p>
                    <p class="text-xs text-gray-400">{{ employee.emergency_contact_phone || '-' }}</p>
                </div>

                <div class="glass-card p-6 text-center">
                    <div class="flex items-center justify-center gap-2 mb-4">
                        <QrCodeIcon class="w-5 h-5 text-primary-600" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">QR Absensi</h3>
                    </div>
                    <div class="flex justify-center gap-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-2">Absen Masuk</p>
                            <QrCode :text="`${$page.props.appUrl || window.location.origin}/scan/in/${employee.id}`" :size="120" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-2">Absen Pulang</p>
                            <QrCode :text="`${$page.props.appUrl || window.location.origin}/scan/out/${employee.id}`" :size="120" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">Scan QR untuk clock in/out</p>
                </div>

                <div v-if="employee.notes" class="glass-card p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Catatan</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ employee.notes }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
