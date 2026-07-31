<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

defineProps({ status: String });

const form = useForm({ email: '' });
const submit = () => { form.post(route('password.email')); };
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Kata Sandi" />

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-amber-50/60 to-orange-50/30 dark:from-amber-950/20 dark:to-transparent" />
                <div class="relative px-7 pt-8 pb-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Lupa kata sandi?</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masukkan email Anda untuk menerima tautan reset</p>
                </div>
            </div>

            <div class="px-7 pb-8">
                <div v-if="status" class="mb-5 p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200/70 dark:border-emerald-800/60 text-sm font-medium text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <EnvelopeIcon class="w-5 h-5" />
                            </div>
                            <input id="email" v-model="form.email" type="email"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-amber-500 focus:ring-amber-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-4 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.email }"
                                required autofocus autocomplete="username" placeholder="anda@perusahaan.com" />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-amber-500/20 active:scale-[0.98]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ form.processing ? 'Mengirim...' : 'Kirim Tautan Reset' }}
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 text-center">
                    <Link :href="route('login')" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <ArrowLeftIcon class="w-4 h-4" /> Kembali ke halaman masuk
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
