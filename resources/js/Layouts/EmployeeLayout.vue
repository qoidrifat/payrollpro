<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logoFull from '/public/logoo.png';
import {
    QrCodeIcon,
    UserCircleIcon,
    ArrowRightOnRectangleIcon,
    ArrowLeftIcon,
    MoonIcon,
    SunIcon,
    HomeIcon,
    ClockIcon,
    BanknotesIcon,
    CalendarDaysIcon,
    ScaleIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const mobileMenuOpen = ref(false);
const darkMode = ref(localStorage.getItem('darkMode') === 'true');

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    document.documentElement.classList.toggle('dark', darkMode.value);
};

onMounted(() => {
    document.documentElement.classList.toggle('dark', darkMode.value);
});

const user = computed(() => page.props.auth?.user);
const isOnDashboard = computed(() => page.url.startsWith(route('dashboard')));
const isOnMyQr = computed(() => page.url.startsWith(route('attendance.my-qr')));

const navItems = computed(() => [
    { name: 'Dashboard', href: route('dashboard'), icon: HomeIcon, description: 'Ringkasan personal' },
    { name: 'Absensi Saya', href: route('portal.attendance'), icon: ClockIcon, description: 'Riwayat kehadiran' },
    { name: 'Slip Gaji', href: route('portal.payroll'), icon: BanknotesIcon, description: 'Riwayat penggajian' },
    { name: 'Cuti & Izin', href: route('portal.leaves'), icon: CalendarDaysIcon, description: 'Ajukan dan pantau' },
    { name: 'Informasi Pajak', href: route('portal.tax'), icon: ScaleIcon, description: 'Ringkasan PPh21' },
]);
</script>

<template>
    <div :class="['min-h-screen bg-surface-50 dark:bg-surface-dark', { 'dark': darkMode }]">
        <!-- Premium Header with glass effect -->
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200/70 dark:border-gray-800/70 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Left: Logo + Desktop Nav -->
                    <div class="flex items-center gap-6">
                        <Link :href="route('dashboard')" class="flex-shrink-0 group">
                            <img :src="logoFull" alt="PayrollPro" class="h-8 w-auto transition-transform duration-200 group-hover:scale-[1.02]" />
                        </Link>

                        <!-- Desktop Navigation -->
                        <nav class="hidden md:flex items-center gap-1">
                            <Link
                                v-for="item in navItems"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    'px-3 py-2 rounded-xl text-sm font-medium transition-all duration-200',
                                    page.url.startsWith(item.href)
                                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800',
                                ]"
                            >
                                <div class="flex items-center gap-2">
                                    <component :is="item.icon" class="w-4 h-4" />
                                    <span>{{ item.name }}</span>
                                </div>
                            </Link>
                        </nav>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-2">
                        <!-- QR Absensi Button -->
                        <Link
                            :href="route('attendance.my-qr')"
                            class="relative inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-semibold shadow-sm hover:shadow-md hover:from-amber-600 hover:to-orange-600 active:scale-[0.97] transition-all duration-200 overflow-hidden group"
                        >
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
                            class="md:hidden p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- User dropdown -->
                        <div class="relative group hidden md:block">
                            <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-sm ring-2 ring-white dark:ring-gray-900">
                                    <span class="text-xs font-bold text-white">
                                        {{ user?.name?.charAt(0) || 'U' }}
                                    </span>
                                </div>
                            </button>
                            <div class="absolute right-0 mt-2 w-56 p-1.5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200/70 dark:border-gray-800/70 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 translate-y-1 group-hover:translate-y-0">
                                <div class="px-3 py-2.5 mb-1 border-b border-gray-100 dark:border-gray-800">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.roles?.[0] || 'Pengguna' }}</p>
                                </div>
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors"
                                >
                                    <UserCircleIcon class="w-4 h-4" />
                                    Profil
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 rounded-xl transition-colors w-full mt-0.5"
                                >
                                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                    Keluar
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg">
                <div class="px-3 py-3 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors',
                            page.url.startsWith(item.href)
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800',
                        ]"
                        @click="mobileMenuOpen = false"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        <div>
                            <p>{{ item.name }}</p>
                            <p class="text-[11px] text-gray-400">{{ item.description }}</p>
                        </div>
                    </Link>

                    <div class="border-t border-gray-100 dark:border-gray-800 pt-2 mt-2">
                        <Link
                            :href="route('profile.edit')"
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
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 w-full"
                            @click="mobileMenuOpen = false"
                        >
                            <ArrowRightOnRectangleIcon class="w-5 h-5" />
                            Keluar
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main
            :class="[
                'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
                isOnMyQr ? 'py-3 lg:py-3' : 'py-6 lg:py-8',
            ]"
        >
            <!-- Back link (when not on dashboard) -->
            <div v-if="!isOnDashboard" :class="isOnMyQr ? 'mb-3' : 'mb-5'">
                <Link
                    :href="route('dashboard')"
                    :class="[
                        'inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white/70 text-sm font-medium text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-white hover:text-gray-900 active:scale-[0.98] dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-900 dark:hover:text-white',
                        isOnMyQr ? 'px-3 py-1.5' : 'px-3.5 py-2',
                    ]"
                >
                    <ArrowLeftIcon class="w-4 h-4" />
                    Kembali ke Dashboard
                </Link>
            </div>

            <div :key="page.url" class="animate-fade-in">
                <slot />
            </div>
        </main>
    </div>
</template>
