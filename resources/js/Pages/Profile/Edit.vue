<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { UserCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-8 pb-12">
            <!-- Header -->
            <div class="glass-card p-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <UserCircleIcon class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white gradient-text">Profil</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Kelola informasi akun dan keamanan Anda</p>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-primary-500 via-purple-500 to-indigo-500" />
                <div class="p-6">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>
            </div>

            <!-- Password -->
            <div class="glass-card overflow-hidden">
                <div class="h-1.5 bg-gradient-to-r from-emerald-500 to-teal-500" />
                <div class="p-6">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>
            </div>

            <!-- Delete Account -->
            <div class="glass-card overflow-hidden border-red-200/50 dark:border-red-900/30">
                <div class="h-1.5 bg-gradient-to-r from-red-500 to-rose-500" />
                <div class="p-6">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
