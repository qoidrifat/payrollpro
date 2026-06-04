<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logoFull from '/public/logoo.png';
import {
    QrCodeIcon,
    UserCircleIcon,
    ArrowRightOnRectangleIcon,
    ArrowLeftIcon,
    MoonIcon,
    SunIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const mobileMenuOpen = ref(false);
const darkMode = ref(localStorage.getItem('darkMode') === 'true');

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    document.documentElement.classList.toggle('dark', darkMode.value);
};

const user = computed(() => page.props.auth?.user);
const isOnDashboard = computed(() => page.url.startsWith('/dashboard'));
</script>

<template>
    <div :class="['min-h-screen bg-gray-50 dark:bg-gray-950', { 'dark': darkMode }]">
        <!-- Minimal Header -->
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Left: Logo / Back Button (mutually exclusive) -->
                    <div class="flex items-center">
                        <!-- Logo (dashboard only) -->
                        <Link v-if="isOnDashboard" href="/dashboard" class="flex-shrink-0 group">
                            <img :src="logoFull" alt="PayrollPro" class="h-9 w-auto transition-transform duration-200 group-hover:scale-[1.02]" />
                        </Link>

                        <!-- Back button (sub-pages only) -->
                        <Link
                            v-else
                            href="/dashboard"
                            class="inline-flex items-center gap-2.5 px-3 py-2 -ml-2 rounded-xl text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 group"
                        >
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 group-hover:bg-white dark:group-hover:bg-gray-700 transition-colors duration-200">
                                <ArrowLeftIcon class="w-4 h-4" />
                            </span>
                            <span class="hidden sm:inline font-medium">Kembali</span>
                        </Link>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-2.5">
                        <!-- QR Absensi Button (prominent) -->
                        <Link
                            href="/my-qr"
                            class="relative inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-semibold shadow-sm hover:shadow-md hover:from-amber-600 hover:to-orange-600 active:scale-[0.97] transition-all duration-200 overflow-hidden group"
                        >
                            <!-- Shimmer effect on hover -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700" />
                            <QrCodeIcon class="w-4 h-4 relative" />
                            <span class="hidden sm:inline relative">Absensi QR</span>
                        </Link>

                        <!-- Dark mode toggle -->
                        <button
                            class="p-2.5 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-95"
                            @click="toggleDarkMode"
                            :title="darkMode ? 'Mode Terang' : 'Mode Gelap'"
                        >
                            <SunIcon v-if="darkMode" class="w-5 h-5" />
                            <MoonIcon v-else class="w-5 h-5" />
                        </button>

                        <!-- Mobile menu toggle -->
                        <button
                            class="sm:hidden p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- User dropdown -->
                        <div class="relative group hidden sm:block">
                            <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-sm ring-2 ring-white dark:ring-gray-900">
                                    <span class="text-xs font-bold text-white">
                                        {{ user?.name?.charAt(0) || 'U' }}
                                    </span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 p-1.5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <div class="px-3 py-2 mb-1 border-b border-gray-100 dark:border-gray-800">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-gray-400">{{ user?.roles?.[0] || 'Pengguna' }}</p>
                                </div>
                                <Link
                                    href="/profile"
                                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors"
                                >
                                    <UserCircleIcon class="w-4 h-4" />
                                    Profil
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-xl transition-colors w-full mt-0.5"
                                >
                                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                    Keluar
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu (QR only) -->
            <div v-if="mobileMenuOpen" class="sm:hidden border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <div class="px-3 py-3 space-y-1">
                    <Link
                        href="/my-qr"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950 dark:to-orange-950 text-amber-700 dark:text-amber-300"
                        @click="mobileMenuOpen = false"
                    >
                        <QrCodeIcon class="w-5 h-5" />
                        Absensi QR
                    </Link>

                    <!-- Mobile Profile & Logout -->
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-2 mt-2">
                        <Link
                            href="/profile"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                            @click="mobileMenuOpen = false"
                        >
                            <UserCircleIcon class="w-5 h-5" />
                            Profil
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 w-full"
                            @click="mobileMenuOpen = false"
                        >
                            <ArrowRightOnRectangleIcon class="w-5 h-5" />
                            Keluar
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content with consistent max-width -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <slot />
        </main>
    </div>
</template>
