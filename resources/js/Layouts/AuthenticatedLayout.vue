<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import logoFull from '/public/logoo.png';
import logoIcon from '/public/iconn.png';
import {
    HomeIcon,
    UsersIcon,
    UserGroupIcon,
    ClockIcon,
    QrCodeIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    CalendarDaysIcon,
    Cog6ToothIcon,
    SignalIcon,
    CodeBracketIcon,
    BuildingOffice2Icon,
    ClipboardDocumentCheckIcon,
    Squares2X2Icon,
    WrenchScrewdriverIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowRightOnRectangleIcon,
    UserCircleIcon,
    MoonIcon,
    SunIcon,
    BellIcon,
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

onMounted(() => {
    document.documentElement.classList.toggle('dark', darkMode.value);
});

const sidebarLogo = computed(() => sidebarCollapsed.value ? logoIcon : logoFull);

const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.roles?.includes('Admin'));

const navigationGroups = computed(() => [
    {
        key: 'overview',
        label: 'Overview',
        description: 'Ringkasan operasional',
        icon: Squares2X2Icon,
        items: [
            { name: 'Dashboard', href: route('dashboard'), icon: HomeIcon, permission: 'view-dashboard' },
        ],
    },
    {
        key: 'people',
        label: 'People Operations',
        description: 'Akun, karyawan, absensi',
        icon: BuildingOffice2Icon,
        items: [
            { name: 'Kelola Akun', href: route('admin.accounts.index'), icon: UserGroupIcon, permission: 'manage-settings', adminOnly: true },
            { name: 'Karyawan', href: route('employees.index'), icon: UsersIcon, permission: 'manage-employees' },
            { name: 'Absensi', href: route('attendances.index'), icon: ClockIcon, permission: 'view-attendance' },
            { name: 'Absensi QR', href: route('attendance.my-qr'), icon: QrCodeIcon, permission: 'view-attendance' },
            { name: 'Pengajuan Manual', href: route('manual-attendance-requests.index'), icon: ClipboardDocumentCheckIcon, permission: 'manage-attendance' },
            { name: 'Pengajuan Cuti', href: route('leave-requests.index'), icon: CalendarDaysIcon, permission: 'manage-attendance' },
        ],
    },
    {
        key: 'payroll',
        label: 'Payroll & Reports',
        description: 'Penggajian dan insight',
        icon: ClipboardDocumentCheckIcon,
        items: [
            { name: 'Penggajian', href: route('payroll.index'), icon: CurrencyDollarIcon, permission: 'view-payroll' },
            { name: 'Laporan', href: route('reports.payroll'), icon: ChartBarIcon, permission: 'view-reports' },
        ],
    },
    {
        key: 'system',
        label: 'Admin Console',
        description: 'Konfigurasi dan sistem',
        icon: WrenchScrewdriverIcon,
        adminOnly: true,
        items: [
            { name: 'Status Sistem', href: route('admin.status.index'), icon: SignalIcon, permission: 'manage-settings' },
            { name: 'API Docs', href: route('developer.api-docs'), icon: CodeBracketIcon, permission: 'manage-settings' },
            { name: 'Pengaturan', href: route('settings.index'), icon: Cog6ToothIcon, permission: 'manage-settings' },
        ],
    },
]);

const canSeeItem = (item) => {
    if (item.adminOnly && !isAdmin.value) return false;
    if (!item.permission) return true;
    return user.value?.permissions?.includes(item.permission);
};

const filteredGroups = computed(() =>
    navigationGroups.value
        .filter(group => !group.adminOnly || isAdmin.value)
        .map(group => ({
            ...group,
            items: group.items.filter(canSeeItem),
        }))
        .filter(group => group.items.length)
);
</script>

<template>
    <div :class="['flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-950', { 'dark': darkMode }]">
        <!-- Sidebar Overlay (mobile) -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:hidden"
            @click="mobileMenuOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl border-r border-gray-200 dark:border-gray-800 transition-all duration-300 ease-out',
                sidebarCollapsed ? 'w-[72px]' : 'w-64',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Logo Area -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-100 dark:border-gray-800">
                <Link :href="route('dashboard')" class="flex items-center">
                    <img :src="sidebarLogo" alt="PayrollPro" :class="['flex-shrink-0 w-auto transition-all duration-300', sidebarCollapsed ? 'h-7' : 'h-8 lg:h-9']" />
                </Link>
                <button
                    class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-90"
                    @click="toggleSidebar"
                >
                    <ChevronLeftIcon v-if="!sidebarCollapsed" class="w-3.5 h-3.5" />
                    <ChevronRightIcon v-else class="w-3.5 h-3.5" />
                </button>
                <button
                    class="lg:hidden text-gray-400 hover:text-gray-600"
                    @click="mobileMenuOpen = false"
                >
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-4 px-3 overflow-y-auto scrollbar-thin space-y-2">
                <section
                    v-for="group in filteredGroups"
                    :key="group.key"
                    :class="[
                        'admin-sidebar-group',
                        sidebarCollapsed ? 'px-0' : 'py-3 rounded-2xl bg-gray-50/50 dark:bg-gray-950/20',
                    ]"
                >
                    <!-- Group Header -->
                    <div
                        :class="[
                            'flex items-center gap-2.5',
                            sidebarCollapsed ? 'justify-center mb-2 px-0' : 'mb-2.5 px-3',
                        ]"
                        :title="sidebarCollapsed ? `${group.label} - ${group.description}` : undefined"
                    >
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-400 dark:ring-gray-800">
                            <component :is="group.icon" class="h-4 w-4" />
                        </div>
                        <div v-show="!sidebarCollapsed" class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-700 dark:text-gray-200">
                                {{ group.label }}
                            </p>
                            <p class="text-[10px] leading-4 text-gray-400 dark:text-gray-500 truncate">
                                {{ group.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Nav Items -->
                    <div class="space-y-0.5">
                        <Link
                            v-for="item in group.items"
                            :key="item.name"
                            :href="item.href"
                            :title="sidebarCollapsed ? item.name : undefined"
                            @click="mobileMenuOpen = false"
                            :class="[
                                'admin-sidebar-link flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-200 group',
                                sidebarCollapsed ? 'justify-center px-0 py-2.5' : 'px-3 py-2.5',
                                page.url.startsWith(item.href)
                                    ? 'bg-white text-primary-700 shadow-sm ring-1 ring-primary-100 dark:bg-gray-900 dark:text-primary-300 dark:ring-primary-900/50'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-white hover:text-gray-900 hover:shadow-sm dark:hover:bg-gray-900 dark:hover:text-white',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'h-5 w-5 flex-shrink-0 transition-colors',
                                    page.url.startsWith(item.href)
                                        ? 'text-primary-600 dark:text-primary-300'
                                        : 'text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200',
                                ]"
                            />
                            <span v-show="!sidebarCollapsed" class="whitespace-nowrap">{{ item.name }}</span>
                        </Link>
                    </div>
                </section>
            </nav>

            <!-- User Footer -->
            <div class="border-t border-gray-100 dark:border-gray-800 p-3 bg-gray-50/30 dark:bg-gray-900/30">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-sm ring-2 ring-white dark:ring-gray-800">
                        <span class="text-sm font-bold text-white">
                            {{ user?.name?.charAt(0) || 'U' }}
                        </span>
                    </div>
                    <div v-show="!sidebarCollapsed" class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ user?.roles?.[0] || 'Pengguna' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="['flex-1 flex flex-col transition-all duration-300', sidebarCollapsed ? 'lg:ml-[72px]' : 'lg:ml-64']">
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-950/80 backdrop-blur-md border-b border-gray-200/70 dark:border-gray-800/70 shadow-sm">
                <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                    <button
                        class="lg:hidden p-2 rounded-xl text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-90"
                        @click="mobileMenuOpen = true"
                    >
                        <Bars3Icon class="w-5 h-5" />
                    </button>

                    <div class="flex items-center gap-3 ml-auto">
                        <!-- Dark mode toggle -->
                        <button
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 active:scale-90"
                            @click="toggleDarkMode"
                            :title="darkMode ? 'Mode Terang' : 'Mode Gelap'"
                        >
                            <SunIcon v-if="darkMode" class="w-5 h-5" />
                            <MoonIcon v-else class="w-5 h-5" />
                        </button>

                        <!-- User dropdown -->
                        <div class="relative group">
                            <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-sm ring-2 ring-white dark:ring-gray-900">
                                    <span class="text-xs font-bold text-white">
                                        {{ user?.name?.charAt(0) || 'U' }}
                                    </span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-56 p-1.5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200/70 dark:border-gray-800/70 shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 translate-y-1 group-hover:translate-y-0">
                                <div class="px-3 py-2.5 mb-1 border-b border-gray-100 dark:border-gray-800">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
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
            </header>

            <!-- Slot: Header -->
            <slot name="header" />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-5 lg:p-8">
                <div :key="page.url" class="animate-fade-in">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
