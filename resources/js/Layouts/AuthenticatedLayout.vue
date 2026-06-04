<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logoFull from '/public/logoo.png';
import logoIcon from '/public/iconn.png';
import {
    HomeIcon,
    UsersIcon,
    ClockIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    ChartBarIcon,
    CalendarDaysIcon,
    Cog6ToothIcon,
    SignalIcon,
    CodeBracketIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowRightOnRectangleIcon,
    UserCircleIcon,
    MoonIcon,
    SunIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const darkMode = ref(localStorage.getItem('darkMode') === 'true');

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    document.documentElement.classList.toggle('dark', darkMode.value);
};

const sidebarLogo = computed(() => sidebarCollapsed.value ? logoIcon : logoFull);

const user = computed(() => page.props.auth?.user);

const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: HomeIcon, permission: 'view-dashboard' },
    { name: 'Karyawan', href: '/employees', icon: UsersIcon, permission: 'manage-employees' },
    { name: 'Absensi', href: '/attendances', icon: ClockIcon, permission: 'view-attendance' },
    { name: 'Pengajuan Cuti', href: '/leave-requests', icon: CalendarDaysIcon, permission: 'manage-attendance' },
    { name: 'Penggajian', href: '/payroll', icon: CurrencyDollarIcon, permission: 'view-payroll' },
    { name: 'Laporan', href: '/reports/payroll', icon: ChartBarIcon, permission: 'view-reports' },
    { name: 'Status Sistem', href: '/admin/status', icon: SignalIcon, permission: 'manage-settings' },
    { name: 'API Docs', href: '/developer/api-docs', icon: CodeBracketIcon, permission: 'manage-settings' },
    { name: 'Pengaturan', href: '/settings', icon: Cog6ToothIcon, permission: 'manage-settings' },
];

const filteredNav = computed(() =>
    navigation.filter(item => {
        if (!item.permission) return true;
        return user.value?.permissions?.includes(item.permission);
    })
);
</script>

<template>
    <div :class="['flex h-screen overflow-hidden', { 'dark': darkMode }]">
        <!-- Sidebar Overlay (mobile) -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="mobileMenuOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-all duration-300',
                sidebarCollapsed ? 'w-[72px]' : 'w-64',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-800">
                <Link href="/dashboard" class="flex items-center">
                    <img :src="sidebarLogo" alt="PayrollPro" :class="['flex-shrink-0 w-auto transition-all duration-300', sidebarCollapsed ? 'h-7' : 'h-10 py-0.5']" />
                </Link>
                <button
                    class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    @click="toggleSidebar"
                >
                    <ChevronLeftIcon v-if="!sidebarCollapsed" class="w-4 h-4" />
                    <ChevronRightIcon v-else class="w-4 h-4" />
                </button>
                <button
                    class="lg:hidden text-gray-400 hover:text-gray-600"
                    @click="mobileMenuOpen = false"
                >
                    <XMarkIcon class="w-6 h-6" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto scrollbar-thin">
                <Link
                    v-for="item in filteredNav"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group',
                        page.url.startsWith(item.href)
                            ? 'bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white',
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                    <span v-show="!sidebarCollapsed" class="whitespace-nowrap">{{ item.name }}</span>
                </Link>
            </nav>

            <!-- User Footer -->
            <div class="border-t border-gray-200 dark:border-gray-800 p-3">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-700 dark:text-primary-300">
                            {{ user?.name?.charAt(0) || 'U' }}
                        </span>
                    </div>
                    <div v-show="!sidebarCollapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user?.roles?.[0] || 'Pengguna' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="['flex-1 flex flex-col transition-all duration-300', sidebarCollapsed ? 'lg:ml-[72px]' : 'lg:ml-64']">
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-950/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                    <button
                        class="lg:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                        @click="mobileMenuOpen = true"
                    >
                        <Bars3Icon class="w-6 h-6" />
                    </button>

                    <div class="flex items-center gap-4 ml-auto">
                        <!-- Dark mode toggle -->
                        <button
                            class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            @click="toggleDarkMode"
                        >
                            <SunIcon v-if="darkMode" class="w-5 h-5" />
                            <MoonIcon v-else class="w-5 h-5" />
                        </button>

                        <!-- User dropdown -->
                        <div class="relative group">
                            <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                                        {{ user?.name?.charAt(0) || 'U' }}
                                    </span>
                                </div>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 glass-card p-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 shadow-lg">
                                <Link
                                    href="/profile"
                                    class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg"
                                >
                                    <UserCircleIcon class="w-4 h-4" />
                                    Profil
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg w-full"
                                >
                                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                    Keluar
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Header Slot -->
            <slot name="header" />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
