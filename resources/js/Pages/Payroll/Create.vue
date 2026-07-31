<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { ArrowLeftIcon, UsersIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const activeEmployeeCount = page.props.activeEmployeeCount ?? 0

const form = useForm({
    name: '',
    period_start: '',
    period_end: '',
})

const submit = () => {
    form.post(route('payroll.store'), { onSuccess: () => form.reset() })
}
</script>

<template>
    <AuthenticatedLayout>
        <PageHeader title="Buat Penggajian" description="Mulai batch pemrosesan penggajian baru">
            <template #actions>
                <Link :href="route('payroll.index')" class="btn-secondary">
                    <ArrowLeftIcon class="w-5 h-5" />
                    Kembali
                </Link>
            </template>
        </PageHeader>

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Active Employees Info -->
            <div class="glass-card p-5 lg:p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-sm">
                        <UsersIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Karyawan Aktif</p>
                        <p class="text-2xl lg:text-3xl font-extrabold text-gray-900 dark:text-white">{{ activeEmployeeCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="glass-card p-5 lg:p-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-6">Detail Penggajian</h3>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="name" class="form-label">Nama <span class="text-red-500">*</span></label>
                        <input id="name" v-model="form.name" type="text" class="form-input" placeholder="Contoh: Penggajian Januari 2026" required />
                        <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="period_start" class="form-label">Awal Periode <span class="text-red-500">*</span></label>
                            <input id="period_start" v-model="form.period_start" type="date" class="form-input" required />
                            <p v-if="form.errors.period_start" class="form-error">{{ form.errors.period_start }}</p>
                        </div>
                        <div>
                            <label for="period_end" class="form-label">Akhir Periode <span class="text-red-500">*</span></label>
                            <input id="period_end" v-model="form.period_end" type="date" class="form-input" required />
                            <p v-if="form.errors.period_end" class="form-error">{{ form.errors.period_end }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Membuat...' : 'Buat Penggajian' }}
                        </button>
                        <Link :href="route('payroll.index')" class="btn-ghost">Batal</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
