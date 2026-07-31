<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRightIcon,
    PlayIcon,
    BoltIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    ChartBarIcon,
    QrCodeIcon,
    CalculatorIcon,
    CheckIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';
import payrollproLogo from '/public/logoo.png';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const year = new Date().getFullYear();

const highlights = [
    'Kepatuhan PPh21 & BPJS',
    'Slip gaji PDF profesional',
    'Absensi QR real-time',
];

const stats = [
    { value: '100%', label: 'Kepatuhan PPh21 & BPJS' },
    { value: '< 5 mnt', label: 'Proses payroll per periode' },
    { value: 'TER 2024', label: 'Skema pajak terbaru' },
    { value: '24/7', label: 'Akses portal karyawan' },
];

const features = [
    {
        icon: BoltIcon,
        title: 'Otomatisasi Penggajian',
        desc: 'Hitung gaji kotor, potongan BPJS, PPh21, hingga gaji bersih secara otomatis dengan skema pajak Indonesia terbaru.',
        iconWrap: 'bg-indigo-50 dark:bg-indigo-950/60',
        iconText: 'text-indigo-600 dark:text-indigo-400',
        wide: true,
    },
    {
        icon: DocumentTextIcon,
        title: 'Slip Gaji Profesional',
        desc: 'Terbitkan slip gaji PDF yang rapi dan siap dibagikan ke karyawan.',
        iconWrap: 'bg-emerald-50 dark:bg-emerald-950/60',
        iconText: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        icon: ShieldCheckIcon,
        title: 'Kepatuhan Pajak',
        desc: 'PPh21 metode TER, BPJS Kesehatan & Ketenagakerjaan terhitung akurat.',
        iconWrap: 'bg-violet-50 dark:bg-violet-950/60',
        iconText: 'text-violet-600 dark:text-violet-400',
    },
    {
        icon: QrCodeIcon,
        title: 'Absensi QR',
        desc: 'Kehadiran karyawan tercatat real-time lewat pemindaian QR yang aman.',
        iconWrap: 'bg-amber-50 dark:bg-amber-950/60',
        iconText: 'text-amber-600 dark:text-amber-400',
    },
    {
        icon: ChartBarIcon,
        title: 'Laporan Cerdas',
        desc: 'Ringkasan penggajian, pajak, dan analitik absensi dalam satu tampilan.',
        iconWrap: 'bg-blue-50 dark:bg-blue-950/60',
        iconText: 'text-blue-600 dark:text-blue-400',
    },
    {
        icon: CalculatorIcon,
        title: 'Konfigurasi Fleksibel',
        desc: 'Atur komponen gaji, tunjangan, dan potongan sesuai kebijakan perusahaan.',
        iconWrap: 'bg-rose-50 dark:bg-rose-950/60',
        iconText: 'text-rose-600 dark:text-rose-400',
    },
];

const chartBars = [42, 58, 47, 66, 54, 73, 61];
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="relative min-h-screen overflow-x-hidden bg-white font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        <!-- Decorative background -->
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[900px] overflow-hidden">
            <div class="landing-grid absolute inset-0"></div>
            <div class="landing-glow-a absolute -top-40 left-1/2 h-[520px] w-[820px] -translate-x-1/2"></div>
            <div class="landing-glow-b absolute -top-24 right-0 h-[420px] w-[520px]"></div>
        </div>

        <!-- Navbar -->
        <header class="sticky top-0 z-50 border-b border-gray-200/70 bg-white/70 backdrop-blur-xl dark:border-gray-800/70 dark:bg-gray-950/60">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <img :src="payrollproLogo" alt="PayrollPro" class="h-9 w-auto" />

                <div class="hidden items-center gap-8 text-sm font-medium text-gray-600 md:flex dark:text-gray-300">
                    <a href="#fitur" class="transition-colors hover:text-gray-900 dark:hover:text-white">Fitur</a>
                    <a href="#keunggulan" class="transition-colors hover:text-gray-900 dark:hover:text-white">Keunggulan</a>
                    <a href="#mulai" class="transition-colors hover:text-gray-900 dark:hover:text-white">Mulai</a>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <Link v-if="canLogin" :href="route('login')" class="hidden rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:text-gray-900 sm:inline-flex dark:text-gray-300 dark:hover:text-white">
                        Masuk
                    </Link>
                    <Link :href="route('demo.login')" class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                        Coba Demo
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                </div>
            </nav>
        </header>

        <!-- Hero -->
        <main>
            <section class="mx-auto max-w-7xl px-6 pt-20 pb-16 text-center sm:pt-28">
                <div class="reveal inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50/80 px-4 py-1.5 text-sm font-medium text-indigo-700 backdrop-blur dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-300">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                    </span>
                    Manajemen Penggajian Modern
                </div>

                <h1 class="reveal mx-auto mt-8 max-w-4xl font-display text-5xl font-extrabold leading-[1.05] tracking-tight text-gray-900 md:text-6xl lg:text-7xl dark:text-white" style="animation-delay: 60ms">
                    Penggajian jadi
                    <span class="bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 bg-clip-text text-transparent">sederhana</span>
                    &amp; presisi
                </h1>

                <p class="reveal mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-gray-500 md:text-xl dark:text-gray-400" style="animation-delay: 120ms">
                    Platform penggajian modern untuk bisnis Indonesia. Kelola gaji, BPJS, PPh21, absensi, dan slip gaji profesional — semuanya dalam satu tempat yang elegan.
                </p>

                <div class="reveal mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row" style="animation-delay: 180ms">
                    <Link :href="route('demo.login')" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 hover:from-indigo-700 hover:to-violet-700 hover:shadow-xl hover:shadow-indigo-500/30 active:scale-[0.98] sm:w-auto">
                        <PlayIcon class="h-5 w-5" />
                        Coba Demo Gratis
                    </Link>
                    <Link v-if="canLogin" :href="route('login')" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-gray-200 bg-white/70 px-7 py-3.5 text-base font-semibold text-gray-700 shadow-sm backdrop-blur transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:bg-white hover:text-gray-900 hover:shadow-md active:scale-[0.98] sm:w-auto dark:border-gray-700 dark:bg-gray-900/70 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-900 dark:hover:text-white">
                        Masuk ke Akun
                        <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                </div>

                <div class="reveal mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-gray-500 dark:text-gray-400" style="animation-delay: 240ms">
                    <span v-for="item in highlights" :key="item" class="inline-flex items-center gap-1.5">
                        <CheckIcon class="h-4 w-4 text-emerald-500" />
                        {{ item }}
                    </span>
                </div>

                <!-- Product preview -->
                <div class="reveal relative mx-auto mt-16 max-w-5xl" style="animation-delay: 300ms">
                    <div class="rounded-[1.75rem] border border-gray-200/80 bg-white/70 p-2 shadow-2xl shadow-indigo-500/10 backdrop-blur-xl dark:border-gray-800/80 dark:bg-gray-900/60">
                        <div class="overflow-hidden rounded-3xl border border-gray-200/70 bg-white dark:border-gray-800 dark:bg-gray-950">
                            <!-- Window bar -->
                            <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800/80">
                                <span class="h-3 w-3 rounded-full bg-rose-400/80"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-400/80"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-400/80"></span>
                                <div class="mx-auto hidden items-center gap-2 rounded-lg bg-gray-100 px-3 py-1 text-xs text-gray-400 sm:flex dark:bg-gray-800/70 dark:text-gray-500">
                                    app.payrollpro.id/dashboard
                                </div>
                            </div>

                            <!-- Dashboard mock -->
                            <div class="flex text-left">
                                <!-- Sidebar -->
                                <div class="hidden w-14 shrink-0 flex-col items-center gap-4 border-r border-gray-100 py-5 sm:flex dark:border-gray-800/80">
                                    <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600"></div>
                                    <div class="h-7 w-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/70"></div>
                                    <div class="h-7 w-7 rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="h-7 w-7 rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="h-7 w-7 rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                                </div>

                                <!-- Main -->
                                <div class="flex-1 p-5 sm:p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-xs text-gray-400">Selamat datang kembali</p>
                                            <p class="font-display text-lg font-bold text-gray-900 dark:text-white">Dashboard Penggajian</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="hidden rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600 sm:inline dark:bg-emerald-950/60 dark:text-emerald-400">Periode Juli 2026</span>
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500"></div>
                                        </div>
                                    </div>

                                    <!-- Stat tiles -->
                                    <div class="mt-5 grid grid-cols-3 gap-3">
                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3 sm:p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                            <p class="text-[11px] text-gray-400">Total Karyawan</p>
                                            <p class="mt-1 font-display text-lg font-bold text-gray-900 sm:text-xl dark:text-white">128</p>
                                        </div>
                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3 sm:p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                            <p class="text-[11px] text-gray-400">Gaji Bulan Ini</p>
                                            <p class="mt-1 font-display text-lg font-bold text-gray-900 sm:text-xl dark:text-white">Rp 342jt</p>
                                        </div>
                                        <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3 sm:p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                            <p class="text-[11px] text-gray-400">Slip Terbit</p>
                                            <p class="mt-1 inline-flex items-center gap-1 font-display text-lg font-bold text-gray-900 sm:text-xl dark:text-white">
                                                128
                                                <ArrowTrendingUpIcon class="h-4 w-4 text-emerald-500" />
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Chart -->
                                    <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Realisasi Gaji</p>
                                            <p class="text-[11px] text-gray-400">7 periode</p>
                                        </div>
                                        <div class="mt-4 flex h-24 items-end gap-2 sm:gap-3">
                                            <div
                                                v-for="(h, i) in chartBars"
                                                :key="i"
                                                class="flex-1 rounded-t-md bg-gradient-to-t from-indigo-500/70 to-violet-500"
                                                :style="{ height: h + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating badges -->
                    <div class="absolute -left-3 top-24 hidden rotate-[-4deg] items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-xl lg:flex dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
                        <ShieldCheckIcon class="h-4 w-4 text-indigo-500" />
                        PPh21 TER otomatis
                    </div>
                    <div class="absolute -right-3 bottom-16 hidden rotate-[4deg] items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-xl lg:flex dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
                        <CheckIcon class="h-4 w-4 text-emerald-500" />
                        BPJS terhitung akurat
                    </div>
                </div>
            </section>

            <!-- Stats band -->
            <section id="keunggulan" class="border-y border-gray-200/70 bg-gray-50/60 dark:border-gray-800/70 dark:bg-gray-900/30">
                <div class="mx-auto grid max-w-7xl grid-cols-2 gap-y-8 px-6 py-12 md:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label" class="text-center">
                        <p class="font-display text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl dark:text-white">{{ stat.value }}</p>
                        <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                    </div>
                </div>
            </section>

            <!-- Features -->
            <section id="fitur" class="mx-auto max-w-7xl px-6 py-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Fitur Unggulan</p>
                    <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl dark:text-white">
                        Semua yang dibutuhkan tim HR &amp; Keuangan
                    </h2>
                    <p class="mt-4 text-lg text-gray-500 dark:text-gray-400">
                        Dirancang minimalis agar Anda fokus pada yang penting — bukan pada rumitnya perhitungan.
                    </p>
                </div>

                <div class="mt-14 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        :class="['group relative rounded-3xl border border-gray-200/80 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-gray-300 hover:shadow-xl hover:shadow-gray-200/60 dark:border-gray-800/80 dark:bg-gray-900/50 dark:hover:border-gray-700 dark:hover:shadow-black/30', feature.wide ? 'sm:col-span-2' : '']"
                    >
                        <div :class="['flex h-12 w-12 items-center justify-center rounded-2xl transition-transform duration-300 group-hover:scale-110', feature.iconWrap]">
                            <component :is="feature.icon" :class="['h-6 w-6', feature.iconText]" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">{{ feature.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ feature.desc }}</p>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section id="mulai" class="mx-auto max-w-7xl px-6 pb-24">
                <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-6 py-16 text-center shadow-2xl shadow-indigo-500/20 sm:px-12">
                    <div class="pointer-events-none absolute inset-0 landing-cta-grid opacity-20"></div>
                    <div class="relative">
                        <h2 class="mx-auto max-w-2xl font-display text-3xl font-extrabold tracking-tight text-white md:text-4xl">
                            Siap membuat penggajian lebih mudah?
                        </h2>
                        <p class="mx-auto mt-4 max-w-xl text-lg text-indigo-100">
                            Jelajahi seluruh fitur PayrollPro tanpa perlu registrasi. Mulai dari demo hanya dalam hitungan detik.
                        </p>
                        <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <Link :href="route('demo.login')" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-7 py-3.5 text-base font-semibold text-indigo-700 shadow-lg transition-all hover:-translate-y-0.5 hover:bg-indigo-50 sm:w-auto">
                                <PlayIcon class="h-5 w-5" />
                                Coba Demo Sekarang
                            </Link>
                            <Link v-if="canLogin" :href="route('login')" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/30 bg-white/10 px-7 py-3.5 text-base font-semibold text-white backdrop-blur transition-all hover:-translate-y-0.5 hover:bg-white/20 sm:w-auto">
                                Masuk ke Akun
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200/70 dark:border-gray-800/70">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-10 sm:flex-row">
                <div class="flex flex-col items-center gap-1 sm:items-start">
                    <img :src="payrollproLogo" alt="PayrollPro" class="h-8 w-auto" />
                    <p class="text-sm text-gray-400 dark:text-gray-500">Kabupaten Bangkalan, Jawa Timur</p>
                </div>
                <p class="text-sm text-gray-400 dark:text-gray-500">
                    &copy; {{ year }} PayrollPro. Seluruh hak cipta dilindungi.
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Subtle grid pattern for the hero backdrop, fading out toward the edges */
.landing-grid {
    background-image:
        linear-gradient(to right, rgba(99, 102, 241, 0.07) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(99, 102, 241, 0.07) 1px, transparent 1px);
    background-size: 56px 56px;
    -webkit-mask-image: radial-gradient(ellipse 75% 55% at 50% 0%, #000 30%, transparent 100%);
    mask-image: radial-gradient(ellipse 75% 55% at 50% 0%, #000 30%, transparent 100%);
}

:global(.dark) .landing-grid {
    background-image:
        linear-gradient(to right, rgba(129, 140, 248, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(129, 140, 248, 0.08) 1px, transparent 1px);
}

/* Soft radial glows */
.landing-glow-a {
    background: radial-gradient(closest-side, rgba(99, 102, 241, 0.22), transparent 70%);
    filter: blur(20px);
}

.landing-glow-b {
    background: radial-gradient(closest-side, rgba(168, 85, 247, 0.18), transparent 70%);
    filter: blur(20px);
}

:global(.dark) .landing-glow-a {
    background: radial-gradient(closest-side, rgba(99, 102, 241, 0.28), transparent 70%);
}

:global(.dark) .landing-glow-b {
    background: radial-gradient(closest-side, rgba(168, 85, 247, 0.22), transparent 70%);
}

/* Dotted texture inside the CTA card */
.landing-cta-grid {
    background-image: radial-gradient(rgba(255, 255, 255, 0.35) 1px, transparent 1px);
    background-size: 22px 22px;
}

/* Entrance animation */
.reveal {
    opacity: 0;
    animation: reveal-up 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes reveal-up {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .reveal {
        opacity: 1;
        animation: none;
    }
}
</style>
