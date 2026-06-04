<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Kata Sandi" />

        <div class="glass-card p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Konfirmasi kata sandi Anda</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Ini adalah area aman. Konfirmasi kata sandi Anda untuk melanjutkan.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="form-input"
                        :class="{ 'border-red-500 dark:border-red-500': form.errors.password }"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi Anda"
                    />
                    <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
                </div>

                <button type="submit" class="btn-primary w-full text-base" :disabled="form.processing">
                    {{ form.processing ? 'Mengonfirmasi...' : 'Konfirmasi' }}
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
