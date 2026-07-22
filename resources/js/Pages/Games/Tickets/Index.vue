<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    tickets: { type: Array, required: true },
    statuses: { type: Array, required: true },
    can_manage_tickets: { type: Boolean, required: true },
});

const page = usePage();
const search = ref('');
const statusFilter = ref('');

const form = useForm({
    title: '',
    body: '',
});

const statusByValue = computed(() => Object.fromEntries(props.statuses.map((status) => [status.value, status])));
const filteredTickets = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.tickets.filter((ticket) => {
        const matchesStatus = !statusFilter.value || ticket.status === statusFilter.value;
        const haystack = [
            ticket.title,
            ticket.creator?.name,
            ticket.creator?.email,
            ticket.preview,
        ].filter(Boolean).join(' ').toLowerCase();

        return matchesStatus && (!query || haystack.includes(query));
    });
});

const submit = () => {
    form.post(route('games.tickets.store', props.game.id), {
        preserveScroll: true,
    });
};

const formattedDate = (value) => {
    if (!value) return 'Нет сообщений';

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const statusClass = (tone) => ({
    sky: 'gm-badge-warning',
    amber: 'gm-badge-warning',
    violet: 'gm-badge-warning',
    emerald: 'gm-badge-success',
    stone: 'gm-badge-muted',
}[tone] ?? 'gm-badge-muted');
</script>

<template>
    <Head :title="`Тикеты — ${game.name}`" />

    <GameThemeLayout :game="game" section="Тикеты" :title="game.name">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Тикеты</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        {{ can_manage_tickets ? 'Обращения игроков к мастерам этой игры.' : 'Ваши личные обращения к мастерам этой игры.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>К игре</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-grid theme-grid-main">
                <section class="theme-panel">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="fantasy-kicker">Список</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">{{ can_manage_tickets ? 'Все тикеты игры' : 'Мои тикеты' }}</h2>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem] lg:w-[32rem]">
                            <TextInput v-model="search" placeholder="Поиск по заголовку, автору или тексту" />
                            <select v-model="statusFilter" class="fantasy-select block w-full">
                                <option value="">Все статусы</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="page.props.flash.success" class="gm-alert gm-alert-success mt-5">
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="filteredTickets.length === 0" class="mt-6 theme-empty text-center">
                        Тикетов по текущим условиям нет.
                    </div>

                    <div v-else class="mt-6 space-y-4">
                        <Link v-for="ticket in filteredTickets" :key="ticket.id" :href="route('games.tickets.show', [game.id, ticket.id])">
                            <article class="theme-card theme-card-interactive">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="truncate text-lg font-semibold text-amber-50">{{ ticket.title }}</h3>
                                            <span class="gm-badge" :class="statusClass(statusByValue[ticket.status]?.tone)">
                                                {{ ticket.status_label }}
                                            </span>
                                        </div>
                                        <p v-if="ticket.preview" class="mt-3 line-clamp-2 text-sm leading-6 text-stone-400">{{ ticket.preview }}</p>
                                        <p v-else class="mt-3 text-sm text-stone-500">Сообщений пока нет.</p>
                                    </div>
                                    <div class="shrink-0 text-left text-sm text-stone-400 lg:text-right">
                                        <div v-if="can_manage_tickets" class="font-semibold text-stone-200">{{ ticket.creator?.name ?? 'Автор неизвестен' }}</div>
                                        <div class="mt-1">{{ formattedDate(ticket.last_message_at ?? ticket.updated_at) }}</div>
                                    </div>
                                </div>
                            </article>
                        </Link>
                    </div>
                </section>

                <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                    <section class="theme-panel">
                        <p class="fantasy-kicker">Новый тикет</p>
                        <h2 class="mt-2 text-2xl font-semibold text-violet-50">Обращение к мастеру</h2>
                        <form class="mt-5 space-y-4" @submit.prevent="submit">
                            <div>
                                <label class="text-sm font-medium text-stone-300">Заголовок</label>
                                <TextInput v-model="form.title" class="mt-2 block w-full" maxlength="180" />
                                <InputError class="mt-2" :message="form.errors.title" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-stone-300">Первое сообщение</label>
                                <textarea
                                    v-model="form.body"
                                    class="fantasy-textarea mt-2 block min-h-44 w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.body" />
                            </div>
                            <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                                {{ form.processing ? 'Создаём...' : 'Создать тикет' }}
                            </PrimaryButton>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    </GameThemeLayout>
</template>
