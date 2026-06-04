<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Buat Akun" />

        <div class="glass-card p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Buat akun Anda</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mulai dengan akun gratis</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Name -->
                <div>
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :class="{ 'border-red-500 dark:border-red-500': form.errors.name }"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Budi Santoso"
                    />
                    <p v-if="form.errors.name" class="form-error">{{ form.errors.name }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="form-input"
                        :class="{ 'border-red-500 dark:border-red-500': form.errors.email }"
                        required
                        autocomplete="username"
                        placeholder="anda@perusahaan.com"
                    />
                    <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="form-label">Kata Sandi</label>
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
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            class="form-input pr-10"
                            :class="{ 'border-red-500 dark:border-red-500': form.errors.password_confirmation }"
                            required
                            autocomplete="new-password"
                            placeholder="Masukkan ulang kata sandi Anda"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            @click="showConfirm = !showConfirm"
                        >
                            <EyeSlashIcon v-if="showConfirm" class="w-4 h-4" />
                            <EyeIcon v-else class="w-4 h-4" />
                        </button>
                    </div>
                    <p v-if="form.errors.password_confirmation" class="form-error">{{ form.errors.password_confirmation }}</p>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-primary w-full text-base" :disabled="form.processing">
                    <span v-if="form.processing">Membuat akun...</span>
                    <span v-else class="flex items-center justify-center gap-2">
                        Buat Akun
                        <ArrowRightIcon class="w-4 h-4" />
                    </span>
                </button>
            </form>

            <!-- Login link -->
            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Sudah punya akun?
                <Link :href="route('login')" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Masuk
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
