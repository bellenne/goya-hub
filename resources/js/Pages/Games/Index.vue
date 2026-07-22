<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ThemeIcon from '@/Components/ThemeIcon.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    games: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const showCreateModal = ref(false);

const form = useForm({
    name: '',
    description: '',
});

const gmGamesCount = computed(() => props.games.filter((game) => game.role === 'gm' || game.role === 'co_gm').length);
const playerGamesCount = computed(() => props.games.filter((game) => game.role === 'player').length);

const submit = () => {
    form.post(route('games.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Мои игры" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Игры</p>
                    <h1 class="fantasy-title text-3xl">Мои кампании</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Каталог миров, ролей и входов в мастерские панели.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <PrimaryButton @click="showCreateModal = true">
                        Создать игру
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="theme-panel-head">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="gm-panel-icon">
                                <ThemeIcon src="/storage/ui/icons/Sessions.png" name="sessions" />
                            </span>
                            <div>
                                <p class="gm-kicker">Каталог кампаний</p>
                                <h2 class="theme-panel-title">Игровые миры</h2>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <PrimaryButton @click="showCreateModal = true">Новая кампания</PrimaryButton>
                        </div>
                    </div>

                    <div class="relative z-10 mt-5 grid gap-4 md:grid-cols-3">
                        <article class="theme-list-row">
                            <p class="theme-stat-label">Всего игр</p>
                            <strong class="mt-2 block text-xl font-semibold text-[#fff1c8]">{{ games.length }}</strong>
                        </article>
                        <article class="theme-list-row">
                            <p class="theme-stat-label">Мастерские роли</p>
                            <strong class="mt-2 block text-xl font-semibold text-[#fff1c8]">{{ gmGamesCount }}</strong>
                        </article>
                        <article class="theme-list-row">
                            <p class="theme-stat-label">Игрок</p>
                            <strong class="mt-2 block text-xl font-semibold text-[#fff1c8]">{{ playerGamesCount }}</strong>
                        </article>
                    </div>
                </section>

                <section class="theme-panel">
                    <div class="theme-panel-head">
                        <div>
                            <p class="gm-kicker">Список игр</p>
                            <h2 class="theme-panel-title">Доступные кампании</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="gm-badge">{{ games.length }} всего</span>
                        </div>
                    </div>

                    <div
                        v-if="page.props.flash.success"
                        class="gm-alert gm-alert-success mt-5"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="games.length === 0" class="theme-empty mt-5">
                        У вас пока нет игр. Создайте первую кампанию и настройте её под свою партию.
                    </div>

                    <div v-else class="theme-list-grid">
                        <Link
                            v-for="game in games"
                            :key="game.id"
                            :href="route('games.show', game.id)"
                            class="theme-card theme-card-interactive block"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="gm-kicker">Кампания</p>
                                    <h3 class="mt-2 truncate text-xl font-semibold text-[#fff1c8]">{{ game.name }}</h3>
                                </div>
                                <span class="gm-badge shrink-0">{{ game.role_label }}</span>
                            </div>

                            <p v-if="game.description" class="mt-4 line-clamp-4 text-sm leading-6 text-[#d7c5a4]">
                                {{ game.description }}
                            </p>
                            <p v-else class="mt-4 text-sm leading-6 text-[#9d8b6d]">
                                Описание пока не заполнено.
                            </p>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="theme-list-row">
                                    <p class="theme-stat-label">Владелец</p>
                                    <p class="mt-2 truncate text-sm font-semibold text-[#fff1c8]">{{ game.owner.name }}</p>
                                </div>
                                <div class="theme-list-row">
                                    <p class="theme-stat-label">Участников</p>
                                    <p class="mt-2 text-sm font-semibold text-[#fff1c8]">{{ game.member_count }}</p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Создать игру</h2>
                <p class="mt-2 text-sm leading-6 text-stone-400">
                    После создания вы автоматически становитесь мастером этой кампании.
                </p>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Название игры" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-2 block w-full"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Описание" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="fantasy-textarea mt-2 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton @click="showCreateModal = false">Отмена</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            Создать игру
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
