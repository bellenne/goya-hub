<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
    sky: 'border-sky-300/25 bg-sky-400/10 text-sky-100',
    amber: 'border-amber-300/25 bg-amber-400/10 text-amber-100',
    violet: 'border-violet-300/25 bg-violet-400/10 text-violet-100',
    emerald: 'border-emerald-300/25 bg-emerald-400/10 text-emerald-100',
    stone: 'border-stone-500/35 bg-stone-700/30 text-stone-200',
}[tone] ?? 'border-stone-500/35 bg-stone-700/30 text-stone-200');
</script>

<template>
    <Head :title="`Тикеты — ${game.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Тикеты</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        {{ can_manage_tickets ? 'Обращения игроков к GM/co-GM по этой игре.' : 'Ваши личные обращения к мастерам этой игры.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>К игре</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="fantasy-kicker">Список</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">{{ can_manage_tickets ? 'Все тикеты игры' : 'Мои тикеты' }}</h2>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem] lg:w-[32rem]">
                            <TextInput v-model="search" placeholder="Поиск по заголовку, автору или тексту" />
                            <select v-model="statusFilter" class="rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30">
                                <option value="">Все статусы</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="page.props.flash.success" class="mt-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="filteredTickets.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-8 text-center text-sm text-stone-500">
                        Тикетов по текущим условиям нет.
                    </div>

                    <div v-else class="mt-6 space-y-4">
                        <Link v-for="ticket in filteredTickets" :key="ticket.id" :href="route('games.tickets.show', [game.id, ticket.id])">
                            <article class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/25 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="truncate text-lg font-semibold text-amber-50">{{ ticket.title }}</h3>
                                            <span class="rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em]" :class="statusClass(statusByValue[ticket.status]?.tone)">
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
                    <section class="rounded-[1.75rem] border border-violet-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                        <p class="fantasy-kicker">Новый тикет</p>
                        <h2 class="mt-2 text-2xl font-semibold text-violet-50">Обращение к GM</h2>
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
                                    class="mt-2 block min-h-44 w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm leading-6 text-stone-100 shadow-sm transition focus:border-violet-300/60 focus:outline-none focus:ring-2 focus:ring-violet-300/30"
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
    </AuthenticatedLayout>
</template>
