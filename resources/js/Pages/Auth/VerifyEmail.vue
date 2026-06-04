<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="glass-card p-8 text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-primary-100 dark:bg-primary-950 flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>

            <h2 class="text-2xl font-display font-bold text-gray-900 dark:text-white">Verifikasi email Anda</h2>

            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                Tautan verifikasi telah dikirim ke email Anda. Klik tautan untuk memverifikasi alamat email Anda. Jika belum menerima, kami dapat mengirim ulang.
            </p>

            <div
                v-if="verificationLinkSent"
                class="mt-4 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-800 text-sm font-medium text-emerald-700 dark:text-emerald-300"
            >
                Tautan verifikasi baru telah dikirim.
            </div>

            <div class="mt-6 space-y-3">
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="btn-primary w-full text-base"
                >
                    {{ form.processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi' }}
                </button>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="btn-ghost w-full text-base"
                >
                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                    Keluar
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
