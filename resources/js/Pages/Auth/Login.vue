<script setup>
import { ref, computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, ArrowRightIcon, PlayIcon } from '@heroicons/vue/24/outline';
import LoginErrorModal from '@/Components/LoginErrorModal.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const showErrorModal = ref(false);
const errorType = ref('');

const submit = () => {
    showErrorModal.value = false;

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            // Determine error type from the custom login_error field
            if (errors.login_error) {
                errorType.value = errors.login_error;
                showErrorModal.value = true;
            } else if (errors.email) {
                // Fallback: generic credential error (no enumeration)
                errorType.value = 'invalid_credentials';
                showErrorModal.value = true;
            }
        },
    });
};

const closeErrorModal = () => {
    showErrorModal.value = false;
    // Focus back on the email field for credential errors
    if (errorType.value === 'invalid_credentials') {
        document.getElementById('email')?.focus();
    }
    errorType.value = '';
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="glass-card p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Selamat datang kembali</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Masuk ke akun Anda</p>
            </div>

            <!-- Status -->
            <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-800 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
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
                        autofocus
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
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi Anda"
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

                <!-- Remember + Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded-md border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <!-- Demo
                <Link :href="route('demo.login')" class="btn w-full text-base bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:from-emerald-600 hover:to-teal-700 focus:ring-emerald-500 shadow-sm">
                    <PlayIcon class="w-4 h-4" />
                    Coba Demo
                </Link> -->

                <!-- Submit -->
                <button type="submit" class="btn-primary w-full text-base" :disabled="form.processing">
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else class="flex items-center justify-center gap-2">
                        Masuk
                        <ArrowRightIcon class="w-4 h-4" />
                    </span>
                </button>
            </form>

            <!-- Register & Demo links -->
            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum punya akun?
                <Link :href="route('register')" class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Buat akun
                </Link>
                <span class="mx-2">·</span>
                <Link :href="route('demo.login')" class="font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">
                    Coba Demo
                </Link>
            </p>
        </div>

        <!-- Login Error Modal -->
        <LoginErrorModal
            :show="showErrorModal"
            :error-type="errorType"
            @close="closeErrorModal"
        />
    </GuestLayout>
</template>
