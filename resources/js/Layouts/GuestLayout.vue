<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex bg-gradient-to-br from-gray-50 via-white to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">
        <!-- Left Panel: Branding -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-indigo-600 via-primary-600 to-purple-700">
            <!-- Decorative circles -->
            <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-white/10" />
            <div class="absolute -bottom-20 -left-20 w-60 h-60 rounded-full bg-purple-400/20" />
            <div class="absolute top-1/3 left-1/4 w-40 h-40 rounded-full bg-indigo-300/20" />

            <div class="relative flex flex-col justify-center px-16 py-12 w-full">
                <Link href="/" class="mb-12 block">
                    <img src="/logoo.png" alt="PayrollPro" class="h-12 w-auto" />
                </Link>

                <h1 class="text-4xl font-display font-extrabold text-white leading-tight">
                    Penggajian Modern
                    <br />
                    <span class="text-indigo-200">untuk Tim Indonesia</span>
                </h1>

                <p class="mt-6 text-lg text-indigo-100/80 leading-relaxed max-w-md">
                    Kelola gaji, BPJS, pajak PPh21, dan buat slip gaji profesional — semua dalam satu tempat.
                </p>

                <div class="mt-12 grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-white/10 backdrop-blur-sm">
                        <div class="text-2xl font-display font-bold text-white">BPJS</div>
                        <p class="text-sm text-indigo-200 mt-1">Kesehatan & Ketenagakerjaan</p>
                    </div>
                    <div class="p-4 rounded-xl bg-white/10 backdrop-blur-sm">
                        <div class="text-2xl font-display font-bold text-white">PPh21</div>
                        <p class="text-sm text-indigo-200 mt-1">Perhitungan pajak progresif</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Form -->
        <div class="flex-1 flex items-center justify-center p-6">
            <!-- Mobile logo -->
            <div class="lg:hidden absolute top-6 left-6">
                <Link href="/">
                    <img src="/iconn.ico" alt="PayrollPro" class="h-9 w-auto" />
                </Link>
            </div>

            <div class="w-full max-w-md">
                <slot />
            </div>
        </div>
    </div>
</template>
