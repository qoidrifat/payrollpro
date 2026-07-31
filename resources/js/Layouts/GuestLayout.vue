<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex bg-gradient-to-br from-gray-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">
        <!-- Left Panel: Premium Branding -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-indigo-600 via-primary-600 to-purple-700">
            <!-- Decorative orbs -->
            <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-white/[0.08] animate-pulse-subtle" />
            <div class="absolute -bottom-32 -left-32 w-80 h-80 rounded-full bg-purple-400/15" />
            <div class="absolute top-1/4 left-1/3 w-48 h-48 rounded-full bg-indigo-300/15" />
            <div class="absolute bottom-1/3 right-1/4 w-32 h-32 rounded-full bg-white/[0.05]" />

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 opacity-[0.04]"
                style="background-image: linear-gradient(to right, #fff 1px, transparent 1px), linear-gradient(to bottom, #fff 1px, transparent 1px); background-size: 40px 40px;" />

            <div class="relative flex flex-col justify-center px-16 py-12 w-full z-10">
                <!-- Logo -->
                <Link href="/" class="mb-14 block group">
                    <img src="/logoo.png" alt="PayrollPro" class="h-11 w-auto brightness-0 invert transition-transform duration-300 group-hover:scale-[1.02]" />
                </Link>

                <!-- Tagline -->
                <h1 class="text-4xl font-display font-extrabold text-white leading-tight tracking-tight">
                    Penggajian Modern
                    <br />
                    <span class="text-indigo-200">untuk Tim Indonesia</span>
                </h1>

                <p class="mt-6 text-lg text-indigo-100/80 leading-relaxed max-w-md">
                    Kelola gaji, BPJS, pajak PPh21, dan buat slip gaji profesional — semua dalam satu tempat yang elegan.
                </p>

                <!-- Feature highlights -->
                <div class="mt-14 grid grid-cols-2 gap-4">
                    <div class="p-4.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 hover:bg-white/15 transition-colors">
                        <div class="text-2xl font-display font-bold text-white">BPJS</div>
                        <p class="text-sm text-indigo-200 mt-1">Kesehatan &amp; Ketenagakerjaan</p>
                    </div>
                    <div class="p-4.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 hover:bg-white/15 transition-colors">
                        <div class="text-2xl font-display font-bold text-white">PPh21</div>
                        <p class="text-sm text-indigo-200 mt-1">Perhitungan pajak progresif</p>
                    </div>
                </div>

                <!-- Trust badges -->
                <div class="mt-10 flex items-center gap-6 text-indigo-200/60 text-xs">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Terenkripsi SSL
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        Data Aman
                    </span>
                </div>
            </div>
        </div>

        <!-- Right Panel: Form -->
        <div class="flex-1 flex items-center justify-center p-6 relative">
            <!-- Mobile logo -->
            <div class="lg:hidden absolute top-6 left-6">
                <Link href="/">
                    <img src="/iconn.ico" alt="PayrollPro" class="h-9 w-auto" />
                </Link>
            </div>

            <div class="w-full max-w-md">
                <slot />
            </div>

            <!-- Footer text -->
            <p class="absolute bottom-6 text-xs text-gray-400 dark:text-gray-600">
                &copy; {{ new Date().getFullYear() }} PayrollPro. Hak cipta dilindungi.
            </p>
        </div>
    </div>
</template>


