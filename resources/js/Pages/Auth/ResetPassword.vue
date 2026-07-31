<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Atur Ulang Kata Sandi" />

        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 border border-gray-200/80 dark:border-gray-800/80 overflow-hidden">
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-violet-50/60 to-purple-50/30 dark:from-violet-950/20 dark:to-transparent" />
                <div class="relative px-7 pt-8 pb-6 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20 ring-2 ring-white/50 dark:ring-gray-900/50 mb-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Atur ulang kata sandi</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat kata sandi baru untuk <span class="font-medium text-gray-700 dark:text-gray-300">{{ email }}</span></p>
                </div>
            </div>

            <div class="px-7 pb-8">
                <form @submit.prevent="submit" class="space-y-4.5">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Kata Sandi Baru</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <LockClosedIcon class="w-5 h-5" />
                            </div>
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-violet-500 focus:ring-violet-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-11 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.password }"
                                required autocomplete="new-password" placeholder="Min. 8 karakter" />
                            <button type="button"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                @click="showPassword = !showPassword">
                                <EyeSlashIcon v-if="showPassword" class="w-4.5 h-4.5" />
                                <EyeIcon v-else class="w-4.5 h-4.5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <LockClosedIcon class="w-5 h-5" />
                            </div>
                            <input id="password_confirmation" v-model="form.password_confirmation" type="password"
                                class="block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-950 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:border-violet-500 focus:ring-violet-500/20 focus:ring-4 focus:bg-white dark:focus:bg-gray-900 text-sm pl-11 pr-4 py-3.5 transition-all duration-200"
                                :class="{ 'border-red-400 dark:border-red-500': form.errors.password_confirmation }"
                                required autocomplete="new-password" placeholder="Ulangi kata sandi" />
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md shadow-violet-500/20 active:scale-[0.98]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ form.processing ? 'Mengatur ulang...' : 'Atur Ulang Kata Sandi' }}
                    </button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
