<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, ArrowRightIcon, EnvelopeIcon, LockClosedIcon, UserIcon } from '@heroicons/vue/24/outline';

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

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
            <!-- Premium header -->
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-50/60 to-teal-50/30 dark:from-emerald-950/20 dark:to-transparent" />
                <div class="relative px-7 pt-8 pb-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Buat akun Anda</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan akun gratis</p>
                </div>
            </div>

            <div class="px-7 pb-8">
                <form @submit.prevent="submit" class="space-y-4.5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <UserIcon class="w-5 h-5" />
                            </div>
                            <input id="name" v-model="form.name" type="text"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-4 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.name }"
                                required autofocus autocomplete="name" placeholder="Budi Santoso" />
                        </div>
                        <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <EnvelopeIcon class="w-5 h-5" />
                            </div>
                            <input id="email" v-model="form.email" type="email"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-4 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.email }"
                                required autocomplete="username" placeholder="anda@perusahaan.com" />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <LockClosedIcon class="w-5 h-5" />
                            </div>
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-11 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.password }"
                                required autocomplete="new-password" placeholder="Min. 8 karakter" />
                            <button type="button"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                @click="showPassword = !showPassword">
                                <EyeSlashIcon v-if="showPassword" class="w-4.5 h-4.5" />
                                <EyeIcon v-else class="w-4.5 h-4.5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <LockClosedIcon class="w-5 h-5" />
                            </div>
                            <input id="password_confirmation" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-emerald-500 focus:ring-emerald-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-11 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.password_confirmation }"
                                required autocomplete="new-password" placeholder="Ulangi kata sandi" />
                            <button type="button"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                @click="showConfirm = !showConfirm">
                                <EyeSlashIcon v-if="showConfirm" class="w-4.5 h-4.5" />
                                <EyeIcon v-else class="w-4.5 h-4.5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-emerald-500/20 active:scale-[0.98]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span v-else class="flex items-center gap-2">
                            Buat Akun <ArrowRightIcon class="w-4.5 h-4.5" />
                        </span>
                        <span v-if="form.processing">Membuat akun...</span>
                    </button>
                </form>

                <!-- Login link -->
                <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        Sudah punya akun?
                        <Link :href="route('login')" class="font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">Masuk</Link>
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
