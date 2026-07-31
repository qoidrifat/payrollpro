<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ status: String });
const form = useForm({});
const submit = () => { form.post(route('verification.send')); };
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-rose-50/60 to-pink-50/30 dark:from-rose-950/20 dark:to-transparent" />
                <div class="relative px-7 pt-8 pb-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center shadow-lg shadow-rose-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Periksa email Anda</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Verifikasi alamat email untuk mengaktifkan akun</p>
                </div>
            </div>

            <div class="px-7 pb-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                    Tautan verifikasi telah dikirim ke email Anda.
                    <br />Klik tautan tersebut untuk memverifikasi alamat email Anda.
                </p>

                <div v-if="verificationLinkSent" class="mt-4 p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200/70 dark:border-emerald-800/60 text-sm font-medium text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Tautan verifikasi baru telah dikirim.
                </div>

                <div class="mt-6 space-y-3">
                    <button @click="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-rose-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-rose-500/20 active:scale-[0.98]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ form.processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi' }}
                    </button>

                    <Link :href="route('logout')" method="post" as="button"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-all duration-200 active:scale-[0.98]">
                        <ArrowRightOnRectangleIcon class="w-4 h-4" />
                        Keluar
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
