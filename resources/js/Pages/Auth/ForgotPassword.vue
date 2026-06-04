<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Kata Sandi" />

        <div class="glass-card p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Lupa kata sandi Anda?</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Masukkan email Anda dan kami akan mengirimkan tautan reset.
                </p>
            </div>

            <div
                v-if="status"
                class="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-800 text-sm font-medium text-emerald-700 dark:text-emerald-300"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="form-input"
                        :class="{ 'border-red-500 dark:border-red-500': form.errors.email }"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="anda@perusahaan.com"
                    />
                    <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
                </div>

                <button type="submit" class="btn-primary w-full text-base" :disabled="form.processing">
                    {{ form.processing ? 'Mengirim...' : 'Kirim Tautan Reset' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <Link :href="route('login')" class="inline-flex items-center gap-2 font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    <ArrowLeftIcon class="w-4 h-4" />
                    Kembali ke halaman masuk
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
