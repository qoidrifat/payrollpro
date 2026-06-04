<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const employee = page.props.employee;

const isEditing = !!employee;

const form = useForm({
    first_name: employee?.first_name || '',
    last_name: employee?.last_name || '',
    nik: employee?.nik || '',
    npwp: employee?.npwp || '',
    gender: employee?.gender || 'male',
    position: employee?.position || '',
    department: employee?.department || '',
    join_date: employee?.join_date || '',
    employment_status: employee?.employment_status || 'permanent',
    base_salary: employee?.base_salary || '',
    phone: employee?.phone || '',
    address: employee?.address || '',
    city: employee?.city || '',
    province: employee?.province || '',
    postal_code: employee?.postal_code || '',
    bank_name: employee?.bank_name || '',
    bank_account_number: employee?.bank_account_number || '',
    bank_account_name: employee?.bank_account_name || '',
    bpjs_kesehatan: employee?.bpjs_kesehatan || '',
    bpjs_ketenagakerjaan: employee?.bpjs_ketenagakerjaan || '',
    emergency_contact_name: employee?.emergency_contact_name || '',
    emergency_contact_phone: employee?.emergency_contact_phone || '',
    notes: employee?.notes || '',
    is_active: employee?.is_active ?? true,
    resign_date: employee?.resign_date || '',
});

const submit = () => {
    if (isEditing) {
        form.put(`/employees/${employee.id}`);
    } else {
        form.post('/employees');
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader :title="isEditing ? 'Ubah Karyawan' : 'Tambah Karyawan'" :description="isEditing ? `Mengubah ${employee.full_name}` : 'Tambah anggota tim baru.'">
            <template #actions>
                <Link href="/employees" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali ke Daftar
                </Link>
            </template>
        </PageHeader>

        <form @submit.prevent="submit" class="max-w-4xl">
            <!-- Basic Info -->
            <div class="glass-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Data Pribadi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nama Depan *</label>
                        <input v-model="form.first_name" type="text" class="form-input" :class="{ 'border-red-500': form.errors.first_name }" />
                        <p v-if="form.errors.first_name" class="form-error">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="form-label">Nama Belakang</label>
                        <input v-model="form.last_name" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">NIK *</label>
                        <input v-model="form.nik" type="text" maxlength="16" class="form-input" :class="{ 'border-red-500': form.errors.nik }" placeholder="16 digit" />
                        <p v-if="form.errors.nik" class="form-error">{{ form.errors.nik }}</p>
                    </div>
                    <div>
                        <label class="form-label">NPWP</label>
                        <input v-model="form.npwp" type="text" maxlength="16" class="form-input" placeholder="16 digit" />
                    </div>
                    <div>
                        <label class="form-label">Jenis Kelamin *</label>
                        <select v-model="form.gender" class="form-input">
                            <option value="male">Laki-laki</option>
                            <option value="female">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Telepon</label>
                        <input v-model="form.phone" type="text" class="form-input" />
                    </div>
                </div>
                <div class="mt-4">
                    <label class="form-label">Alamat</label>
                    <textarea v-model="form.address" class="form-input" rows="2"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label class="form-label">Kota</label>
                        <input v-model="form.city" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Provinsi</label>
                        <input v-model="form.province" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Kode Pos</label>
                        <input v-model="form.postal_code" type="text" class="form-input" />
                    </div>
                </div>
            </div>

            <!-- Employment Info -->
            <div class="glass-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detail Kepegawaian</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Jabatan *</label>
                        <input v-model="form.position" type="text" class="form-input" :class="{ 'border-red-500': form.errors.position }" />
                        <p v-if="form.errors.position" class="form-error">{{ form.errors.position }}</p>
                    </div>
                    <div>
                        <label class="form-label">Departemen</label>
                        <input v-model="form.department" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Tanggal Masuk *</label>
                        <input v-model="form.join_date" type="date" class="form-input" :class="{ 'border-red-500': form.errors.join_date }" />
                        <p v-if="form.errors.join_date" class="form-error">{{ form.errors.join_date }}</p>
                    </div>
                    <div>
                        <label class="form-label">Status Kepegawaian *</label>
                        <select v-model="form.employment_status" class="form-input">
                            <option value="permanent">Tetap</option>
                            <option value="contract">Kontrak</option>
                            <option value="probation">Percobaan</option>
                            <option value="intern">Magang</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Gaji Pokok (Rp) *</label>
                        <input v-model="form.base_salary" type="number" class="form-input" :class="{ 'border-red-500': form.errors.base_salary }" />
                        <p v-if="form.errors.base_salary" class="form-error">{{ form.errors.base_salary }}</p>
                    </div>
                </div>
            </div>

            <!-- Bank & BPJS Info -->
            <div class="glass-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bank & BPJS</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nama Bank</label>
                        <input v-model="form.bank_name" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Nomor Rekening</label>
                        <input v-model="form.bank_account_number" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Nama Pemilik Rekening</label>
                        <input v-model="form.bank_account_name" type="text" class="form-input" />
                    </div>
                    <div></div>
                    <div>
                        <label class="form-label">No. BPJS Kesehatan</label>
                        <input v-model="form.bpjs_kesehatan" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">No. BPJS Ketenagakerjaan</label>
                        <input v-model="form.bpjs_ketenagakerjaan" type="text" class="form-input" />
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="glass-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Kontak Darurat</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nama Kontak Darurat</label>
                        <input v-model="form.emergency_contact_name" type="text" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Telepon Kontak Darurat</label>
                        <input v-model="form.emergency_contact_phone" type="text" class="form-input" />
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="glass-card p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Catatan</h3>
                <textarea v-model="form.notes" class="form-input" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Karyawan') }}
                </button>
                <Link href="/employees" class="btn-secondary">Batal</Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
