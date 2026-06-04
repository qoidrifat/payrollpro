<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRightIcon, PlayIcon } from '@heroicons/vue/24/outline';
import payrollproLogo from '/public/logoo.png';
import payrollproIcon from '/public/iconn.ico';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">
        <!-- Nav -->
        <nav class="flex items-center justify-between px-6 py-5 max-w-7xl mx-auto">
            <div>
                <img :src="payrollproLogo" alt="PayrollPro" class="h-10 w-auto" />
            </div>
            <div class="flex items-center gap-3">
                <Link v-if="canLogin" :href="route('login')" class="btn-secondary">
                    Masuk
                </Link>
                <Link v-if="canRegister" :href="route('register')" class="btn-primary">
                    Mulai
                    <ArrowRightIcon class="w-4 h-4" />
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <main class="max-w-7xl mx-auto px-6 pt-16 pb-32 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 dark:bg-primary-950 border border-primary-100 dark:border-primary-900 text-primary-700 dark:text-primary-300 text-sm font-medium mb-8">
                Manajemen Penggajian Modern
            </div>

            <h1 class="text-5xl md:text-6xl lg:text-7xl font-display font-extrabold text-gray-900 dark:text-white tracking-tight">
                Penggajian Jadi
                <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Mudah</span>
            </h1>

            <p class="mt-6 text-lg md:text-xl text-gray-500 dark:text-gray-400 max-w-2xl mx-auto leading-relaxed">
                Sistem manajemen penggajian modern untuk bisnis di Indonesia. Kelola gaji karyawan, BPJS, pajak PPh21, dan buat slip gaji profesional — semua dalam satu tempat.
            </p>

            <div class="mt-10 flex items-center justify-center gap-4">
                <Link :href="route('demo.login')" class="btn-primary text-base px-8 py-3.5 rounded-2xl shadow-lg shadow-primary-500/25 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 focus:ring-emerald-500">
                    <PlayIcon class="w-5 h-5" />
                    Coba Demo
                </Link>
                <Link v-if="canRegister" :href="route('register')" class="btn-primary text-base px-8 py-3.5 rounded-2xl shadow-lg shadow-primary-500/25">
                    Mulai Uji Coba Gratis
                    <ArrowRightIcon class="w-5 h-5" />
                </Link>
                <Link v-if="canLogin" :href="route('login')" class="btn-secondary text-base px-8 py-3.5 rounded-2xl">
                    Masuk
                </Link>
            </div>

            <!-- Feature Cards -->
            <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                <div class="glass-card p-6 card-accent-indigo">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Otomatisasi Penggajian</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Hitung otomatis gaji kotor, BPJS, PPh21, dan gaji bersih dengan kepatuhan pajak Indonesia bawaan.</p>
                </div>

                <div class="glass-card p-6 card-accent-emerald">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Slip Gaji Profesional</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Buat slip gaji PDF modern dan profesional yang disukai oleh karyawan Anda.</p>
                </div>

                <div class="glass-card p-6 card-accent-amber">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Laporan Cerdas</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ringkasan penggajian bulanan, laporan pajak, dan analisis absensi dalam genggaman Anda.</p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center py-8 text-sm text-gray-400 dark:text-gray-600">
            Kabupaten Bangkalan, Jawa Timur &middot; &copy; {{ new Date().getFullYear() }} PayrollPro
        </footer>
    </div>
</template>
