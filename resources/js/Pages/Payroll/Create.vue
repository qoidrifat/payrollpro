<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { ArrowLeftIcon, UsersIcon } from '@heroicons/vue/24/outline'

const page = usePage()
const activeEmployeeCount = page.props.activeEmployeeCount ?? 0

const form = useForm({
    name: '',
    period_start: '',
    period_end: '',
})

const submit = () => {
    form.post(route('payroll.store'), {
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Buat Penggajian" description="Mulai batch pemrosesan penggajian baru">
                <template #actions>
                    <Link :href="route('payroll.index')" class="btn-secondary">
                        <ArrowLeftIcon class="w-5 h-5" />
                        Kembali ke Penggajian
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto space-y-6">
            <!-- Active Employees Info -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950 flex items-center justify-center">
                        <UsersIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Karyawan Aktif yang Disertakan</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ activeEmployeeCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Create Form -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Detail Penggajian</h3>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="form-label">Nama</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="form-input"
                            placeholder="contoh: Penggajian Januari 2026"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Period Start -->
                    <div>
                        <label for="period_start" class="form-label">Awal Periode</label>
                        <input
                            id="period_start"
                            v-model="form.period_start"
                            type="date"
                            class="form-input"
                            required
                        />
                        <p v-if="form.errors.period_start" class="mt-1 text-sm text-red-600">{{ form.errors.period_start }}</p>
                    </div>

                    <!-- Period End -->
                    <div>
                        <label for="period_end" class="form-label">Akhir Periode</label>
                        <input
                            id="period_end"
                            v-model="form.period_end"
                            type="date"
                            class="form-input"
                            required
                        />
                        <p v-if="form.errors.period_end" class="mt-1 text-sm text-red-600">{{ form.errors.period_end }}</p>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            {{ form.processing ? 'Membuat...' : 'Buat Penggajian' }}
                        </button>
                        <Link :href="route('payroll.index')" class="btn-secondary">Batal</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
