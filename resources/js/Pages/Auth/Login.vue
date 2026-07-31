<script setup>
import { ref, computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, ArrowRightIcon, PlayIcon, EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
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
            if (errors.login_error) {
                errorType.value = errors.login_error;
                showErrorModal.value = true;
            } else if (errors.email) {
                errorType.value = 'invalid_credentials';
                showErrorModal.value = true;
            }
        },
    });
};

const closeErrorModal = () => {
    showErrorModal.value = false;
    if (errorType.value === 'invalid_credentials') {
        document.getElementById('email')?.focus();
    }
    errorType.value = '';
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
            <!-- Premium header -->
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/60 to-purple-50/30 dark:from-indigo-950/20 dark:to-transparent" />
                <div class="relative px-7 pt-8 pb-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Selamat datang kembali</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masuk ke akun PayrollPro Anda</p>
                </div>
            </div>

            <div class="px-7 pb-8">
                <!-- Status -->
                <div v-if="status" class="mb-5 p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200/70 dark:border-emerald-800/60 text-sm font-medium text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <EnvelopeIcon class="w-5 h-5" />
                            </div>
                            <input id="email" v-model="form.email" type="email"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-indigo-500 focus:ring-indigo-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-4 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.email }"
                                required autofocus autocomplete="username" placeholder="anda@perusahaan.com" />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600 flex items-center gap-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <LockClosedIcon class="w-5 h-5" />
                            </div>
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-indigo-500 focus:ring-indigo-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-11 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.password }"
                                required autocomplete="current-password" placeholder="Masukkan kata sandi" />
                            <button type="button"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                @click="showPassword = !showPassword">
                                <EyeSlashIcon v-if="showPassword" class="w-4.5 h-4.5" />
                                <EyeIcon v-else class="w-4.5 h-4.5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember + Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input v-model="form.remember" type="checkbox"
                                class="w-4.5 h-4.5 rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500/30 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-800 transition-shadow" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
                        </label>
                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">
                            Lupa kata sandi?
                        </Link>
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-indigo-500/20 active:scale-[0.98]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span v-else class="flex items-center gap-2">
                            Masuk <ArrowRightIcon class="w-4.5 h-4.5" />
                        </span>
                        <span v-if="form.processing">Memproses...</span>
                    </button>
                </form>

                <!-- Links -->
                <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum punya akun?
                        <Link :href="route('register')" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors">Buat akun</Link>
                    </p>
                    <div class="mt-3 text-center">
                        <Link :href="route('demo.login')"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                            <PlayIcon class="w-4 h-4" />
                            Coba Demo Gratis
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <LoginErrorModal :show="showErrorModal" :error-type="errorType" @close="closeErrorModal" />
    </GuestLayout>
</template>
