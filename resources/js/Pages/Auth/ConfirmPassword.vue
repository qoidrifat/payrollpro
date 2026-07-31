<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LockClosedIcon } from '@heroicons/vue/24/outline';

const form = useForm({ password: '' });
const submit = () => { form.post(route('password.confirm'), { onFinish: () => form.reset() }); };
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Kata Sandi" />

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-sky-50/60 to-blue-50/30 dark:from-sky-950/20 dark:to-transparent" />
                <div class="relative px-7 pt-8 pb-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center shadow-lg shadow-sky-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Konfirmasi keamanan</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ini adalah area aman. Konfirmasi kata sandi Anda untuk melanjutkan.</p>
                </div>
            </div>

            <div class="px-7 pb-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <LockClosedIcon class="w-5 h-5" />
                            </div>
                            <input id="password" v-model="form.password" type="password"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-sky-500 focus:ring-sky-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-4 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.password }"
                                required autofocus autocomplete="current-password" placeholder="Masukkan kata sandi Anda" />
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-sky-500/20 active:scale-[0.98]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ form.processing ? 'Mengonfirmasi...' : 'Konfirmasi Kata Sandi' }}
                    </button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
