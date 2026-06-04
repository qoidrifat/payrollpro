<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const attendance = page.props.attendance;
const employees = page.props.employees;
const isEditing = !!attendance;

const form = useForm({
    employee_id: attendance?.employee_id || '',
    date: attendance?.date || new Date().toISOString().slice(0, 10),
    status: attendance?.status || 'present',
    type: attendance?.type || 'wfo',
    clock_in: attendance?.clock_in || '08:00',
    clock_out: attendance?.clock_out || '17:00',
    notes: attendance?.notes || '',
});

const submit = () => {
    if (isEditing) {
        form.put(`/attendances/${attendance.id}`);
    } else {
        form.post('/attendances');
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader :title="isEditing ? 'Ubah Absensi' : 'Catat Absensi'" :description="isEditing ? 'Perbarui catatan absensi.' : 'Catat absensi untuk karyawan.'">
            <template #actions>
                <Link href="/attendances" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali
                </Link>
            </template>
        </PageHeader>

        <form @submit.prevent="submit" class="max-w-lg">
            <div class="glass-card p-6">
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Karyawan</label>
                        <select v-model="form.employee_id" class="form-input" :class="{ 'border-red-500': form.errors.employee_id }" :disabled="isEditing">
                            <option value="">Pilih karyawan...</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.full_name }} - {{ emp.position }}</option>
                        </select>
                        <p v-if="form.errors.employee_id" class="form-error">{{ form.errors.employee_id }}</p>
                    </div>
                    <div>
                        <label class="form-label">Tanggal</label>
                        <input v-model="form.date" type="date" class="form-input" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Absen Masuk</label>
                            <input v-model="form.clock_in" type="time" class="form-input" />
                        </div>
                        <div>
                            <label class="form-label">Absen Pulang</label>
                            <input v-model="form.clock_out" type="time" class="form-input" />
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-input">
                            <option value="present">Hadir</option>
                            <option value="absent">Tidak Hadir</option>
                            <option value="late">Terlambat</option>
                            <option value="half_day">Setengah Hari</option>
                            <option value="sick">Sakit</option>
                            <option value="leave">Cuti</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tipe</label>
                        <select v-model="form.type" class="form-input">
                            <option value="wfo">WFO</option>
                            <option value="wfh">WFH</option>
                            <option value="remote">Remote</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Catatan</label>
                        <textarea v-model="form.notes" class="form-input" rows="3" placeholder="Catatan opsional..."></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan' : 'Catat Absensi') }}
                </button>
                <Link href="/attendances" class="btn-secondary">Batal</Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
