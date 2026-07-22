<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    ticket: { type: Object, required: true },
    statuses: { type: Array, required: true },
    can_manage_tickets: { type: Boolean, required: true },
});

const page = usePage();
const localTicket = ref({ ...props.ticket, messages: [...props.ticket.messages] });
const messageBody = ref('');
const messageError = ref('');
const statusError = ref('');
const isSending = ref(false);
const isUpdatingStatus = ref(false);
const thread = ref(null);

const currentUserId = computed(() => page.props.auth.user?.id);
const statusByValue = computed(() => Object.fromEntries(props.statuses.map((status) => [status.value, status])));
const currentStatus = computed(() => statusByValue.value[localTicket.value.status] ?? {
    label: localTicket.value.status_label,
    tone: localTicket.value.status_tone,
});

const formattedDate = (value) => {
    if (!value) return '';

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: 'long',
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

const mergeTicketMeta = (ticket) => {
    localTicket.value = {
        ...localTicket.value,
        ...ticket,
        messages: localTicket.value.messages,
    };
};

const scrollThreadToBottom = async () => {
    await nextTick();
    if (thread.value) {
        thread.value.scrollTop = thread.value.scrollHeight;
    }
};

const submitMessage = async () => {
    if (!messageBody.value.trim()) {
        messageError.value = 'Введите сообщение.';
        return;
    }

    isSending.value = true;
    messageError.value = '';

    try {
        const response = await window.axios.post(route('games.tickets.messages.store', [props.game.id, localTicket.value.id]), {
            body: messageBody.value,
        });

        localTicket.value.messages.push(response.data.message);
        mergeTicketMeta(response.data.ticket);
        messageBody.value = '';
        await scrollThreadToBottom();
    } catch (error) {
        messageError.value = error.response?.data?.errors?.body?.[0] ?? 'Не удалось отправить сообщение.';
    } finally {
        isSending.value = false;
    }
};

const updateStatus = async (event) => {
    const nextStatus = event.target.value;
    const previousStatus = localTicket.value.status;

    localTicket.value.status = nextStatus;
    isUpdatingStatus.value = true;
    statusError.value = '';

    try {
        const response = await window.axios.patch(route('games.tickets.status.update', [props.game.id, localTicket.value.id]), {
            status: nextStatus,
        });

        mergeTicketMeta(response.data.ticket);
    } catch (error) {
        localTicket.value.status = previousStatus;
        statusError.value = error.response?.data?.errors?.status?.[0] ?? 'Не удалось обновить статус.';
    } finally {
        isUpdatingStatus.value = false;
    }
};
</script>

<template>
    <Head :title="`${localTicket.title} — тикет`" />

    <GameThemeLayout :game="game" section="Тикет" :title="localTicket.title">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Тикет #{{ localTicket.id }}</p>
                    <h1 class="fantasy-title text-3xl">{{ localTicket.title }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Автор: <span class="text-stone-200">{{ localTicket.creator?.name ?? 'Неизвестно' }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.tickets.index', game.id)">
                        <SecondaryButton>К тикетам</SecondaryButton>
                    </Link>
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>К игре</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="mx-auto grid max-w-7xl gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
                <section class="theme-panel">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="fantasy-kicker">Переписка</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Переписка по обращению</h2>
                        </div>
                        <span class="gm-badge px-4 py-2" :class="statusClass(currentStatus.tone)">
                            {{ currentStatus.label }}
                        </span>
                    </div>

                    <div ref="thread" class="mt-6 max-h-[42rem] space-y-4 overflow-y-auto pr-2">
                        <article
                            v-for="message in localTicket.messages"
                            :key="message.id"
                            class="theme-card"
                            :class="message.author?.id === currentUserId
                                ? 'ml-auto max-w-3xl border-amber-300/20 bg-amber-300/10'
                                : 'mr-auto max-w-3xl'"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-stone-100">{{ message.author?.name ?? 'Неизвестный автор' }}</div>
                                    <div class="mt-1 text-xs uppercase tracking-[0.16em] text-stone-500">{{ formattedDate(message.created_at) }}</div>
                                </div>
                            </div>
                            <div class="mt-4 whitespace-pre-wrap text-sm leading-7 text-stone-200">{{ message.body }}</div>
                        </article>
                    </div>

                    <form class="mt-6 theme-card" @submit.prevent="submitMessage">
                        <label class="text-sm font-medium text-stone-300">Новое сообщение</label>
                        <textarea
                            v-model="messageBody"
                            class="fantasy-textarea mt-2 block min-h-36 w-full"
                            placeholder="Ответьте в тикет..."
                        />
                        <InputError class="mt-2" :message="messageError" />
                        <div class="mt-4 flex justify-end">
                            <PrimaryButton :disabled="isSending">
                                {{ isSending ? 'Отправляем...' : 'Отправить' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </section>

                <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                    <section class="theme-panel">
                        <p class="fantasy-kicker">Состояние</p>
                        <h2 class="mt-2 text-2xl font-semibold text-violet-50">Детали тикета</h2>
                        <div class="mt-5 space-y-3 text-sm leading-6 text-stone-300">
                            <div class="theme-list-row">
                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Создан</div>
                                <div class="mt-1 font-semibold text-stone-100">{{ formattedDate(localTicket.created_at) }}</div>
                            </div>
                            <div class="theme-list-row">
                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Последнее сообщение</div>
                                <div class="mt-1 font-semibold text-stone-100">{{ formattedDate(localTicket.last_message_at) || 'Нет данных' }}</div>
                            </div>
                            <div v-if="localTicket.closed_at" class="theme-list-row">
                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Закрыт</div>
                                <div class="mt-1 font-semibold text-stone-100">{{ formattedDate(localTicket.closed_at) }}</div>
                            </div>
                        </div>
                    </section>

                    <section v-if="can_manage_tickets" class="theme-panel">
                        <p class="fantasy-kicker">Управление мастера</p>
                        <h2 class="mt-2 text-2xl font-semibold text-amber-50">Статус</h2>
                        <select
                            :value="localTicket.status"
                            class="fantasy-select mt-5 block w-full"
                            :disabled="isUpdatingStatus"
                            @change="updateStatus"
                        >
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                        <InputError class="mt-2" :message="statusError" />
                        <p class="mt-3 text-xs uppercase tracking-[0.16em] text-stone-500">
                            {{ isUpdatingStatus ? 'Сохраняем статус...' : 'Статус меняют только мастера игры.' }}
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </GameThemeLayout>
</template>
