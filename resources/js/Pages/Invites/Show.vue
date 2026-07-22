<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    invite: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const form = useForm({});
const currentUrl = computed(() => page.url);
</script>

<template>
    <Head :title="`Приглашение в ${invite.game.name}`" />

    <GuestLayout>
        <div class="theme-page w-full">
            <div class="theme-stack max-w-3xl">
            <section class="theme-panel">
                <p class="fantasy-kicker">Приглашение в игру</p>
                <h1 class="mt-3 text-3xl font-semibold text-amber-50">{{ invite.game.name }}</h1>
                <p v-if="invite.game.description" class="mt-4 text-sm leading-7 text-stone-300">
                    {{ invite.game.description }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3 text-sm text-stone-400">
                    <span>Мастер: {{ invite.game.owner.name }}</span>
                    <span>Участников: {{ invite.game.member_count }}</span>
                </div>

                <div class="theme-list-grid">
                    <div class="theme-card">
                        <p class="text-sm font-semibold text-amber-50">1. Вступите в игру</p>
                        <p class="mt-2 text-sm text-stone-400">После принятия приглашения игра появится в вашем списке кампаний.</p>
                    </div>
                    <div class="theme-card">
                        <p class="text-sm font-semibold text-amber-50">2. Создайте персонажа</p>
                        <p class="mt-2 text-sm text-stone-400">Заполните лист до первой сессии или после согласования с мастером.</p>
                    </div>
                    <div class="theme-card">
                        <p class="text-sm font-semibold text-amber-50">3. Подключитесь к сессии</p>
                        <p class="mt-2 text-sm text-stone-400">Используйте код или ссылку сессии, которые выдаст мастер.</p>
                    </div>
                </div>

                <div
                    v-if="invite.is_expired"
                    class="theme-empty mt-8 text-rose-200"
                >
                    Срок действия приглашения истёк. Попросите мастера создать новую ссылку.
                </div>

                <div v-else class="mt-8 space-y-4">
                    <form
                        v-if="page.props.auth.user"
                        @submit.prevent="form.post(route('invites.accept', invite.token), { preserveScroll: true, preserveState: true })"
                    >
                        <PrimaryButton :disabled="form.processing">
                            Принять приглашение
                        </PrimaryButton>
                    </form>

                    <div v-else class="theme-card text-sm text-stone-300">
                        Сначала войдите или создайте аккаунт. После этого приглашение снова откроется, и вы сможете вступить в игру.
                    </div>

                    <div v-if="!page.props.auth.user" class="flex flex-wrap gap-3">
                        <Link :href="route('login', { redirect: currentUrl })">
                            <SecondaryButton>Войти</SecondaryButton>
                        </Link>
                        <Link :href="route('register', { redirect: currentUrl })">
                            <SecondaryButton>Зарегистрироваться</SecondaryButton>
                        </Link>
                    </div>
                </div>
            </section>
            </div>
        </div>
    </GuestLayout>
</template>
