<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Atur Ulang Kata Sandi" />

        <div class="glass-card p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Atur ulang kata sandi Anda</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Buat kata sandi baru untuk {{ email }}</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Password -->
                <div>
                    <label for="password" class="form-label">Kata Sandi Baru</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-input pr-10"
                            :class="{ 'border-red-500 dark:border-red-500': form.errors.password }"
                            required
                            autocomplete="new-password"
                            placeholder="Min. 8 karakter"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            @click="showPassword = !showPassword"
                        >
                            <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                            <EyeIcon v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="form-input"
                        :class="{ 'border-red-500 dark:border-red-500': form.errors.password_confirmation }"
                        required
                        autocomplete="new-password"
                        placeholder="Masukkan ulang kata sandi Anda"
                    />
                    <p v-if="form.errors.password_confirmation" class="form-error">{{ form.errors.password_confirmation }}</p>
                </div>

                <button type="submit" class="btn-primary w-full text-base" :disabled="form.processing">
                    {{ form.processing ? 'Mengatur ulang...' : 'Atur Ulang Kata Sandi' }}
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
