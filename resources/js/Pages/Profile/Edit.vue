<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Настройки профиля" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Профиль</p>
                    <h1 class="fantasy-title text-3xl">Настройки пользователя</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Управление личными данными, паролем и безопасностью аккаунта.
                    </p>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="theme-panel-head">
                        <div>
                            <p class="gm-kicker">Аккаунт</p>
                            <h2 class="theme-panel-title">Настройки пользователя</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="gm-badge">Профиль</span>
                            <span class="gm-badge">Безопасность</span>
                            <span class="gm-badge">Удаление</span>
                        </div>
                    </div>
                    <p class="mt-4 max-w-3xl text-sm leading-6 text-[#d7c5a4]">
                        Обновление имени, почты, пароля и критических действий аккаунта в одном рабочем разделе.
                    </p>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <section class="theme-panel">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </section>

                    <section class="theme-panel">
                        <UpdatePasswordForm />
                    </section>
                </div>

                <section class="theme-panel">
                    <DeleteUserForm />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
