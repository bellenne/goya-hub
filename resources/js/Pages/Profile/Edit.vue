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

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-white/10 bg-stone-950/45 p-5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Управление аккаунтом</p>
                                <h2 class="mt-3 text-3xl font-semibold text-amber-50">Все личные настройки в одном месте</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">
                                    Обновите имя и почту, смените пароль или удалите аккаунт, если это действительно необходимо.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Профиль</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">01</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Безопасность</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">02</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Удаление</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">03</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Совет</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    После смены почты проверьте её подтверждение, чтобы не потерять доступ к уведомлениям и входу.
                                </p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Безопасность</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Используйте длинный уникальный пароль и не удаляйте аккаунт без необходимости.
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </section>

                    <section class="rounded-[1.75rem] border border-teal-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                        <UpdatePasswordForm />
                    </section>
                </div>

                <section class="rounded-[1.75rem] border border-rose-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                    <DeleteUserForm />
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
