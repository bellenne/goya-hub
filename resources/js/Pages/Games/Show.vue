<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    game: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const inviteForm = useForm({});
const memberRoleForms = {};
const copiedInvite = ref(false);

const roleOptions = [
    { value: 'player', label: 'Игрок' },
    { value: 'co_gm', label: 'Со-ГМ' },
];

const activeInviteLink = computed(() => page.props.flash.invite_link || props.game.invite_link);
const playerCount = computed(() => props.game.members.filter((member) => member.role === 'player').length);
const managementCount = computed(() => props.game.members.filter((member) => member.role !== 'player').length);
const quickLinks = computed(() => {
    const links = [];

    if (props.game.can_edit_character) {
        links.push({
            label: props.game.current_user_character_id ? 'Редактировать персонажа' : 'Создать персонажа',
            description: props.game.current_user_character_id
                ? 'Откройте свой лист и обновите характеристики, навыки и заметки.'
                : 'Подготовьте личный лист перед первой сессией.',
            href: route('games.character.edit', props.game.id),
            primary: true,
            accent: 'amber',
        });
    }

    if (props.game.can_view_characters) {
        links.push({
            label: 'Персонажи',
            description: 'Полный список героев партии и быстрый переход к каждому листу.',
            href: route('games.characters.index', props.game.id),
            accent: 'teal',
        });
    }

    if (props.game.can_view_sessions) {
        links.push({
            label: 'Сессии',
            description: 'Подготовка, запуск и управление игровыми сессиями.',
            href: route('games.sessions.index', props.game.id),
            accent: 'sky',
        });
    }

    if (props.game.can_view_tickets) {
        links.push({
            label: 'Тикеты',
            description: 'Личные обращения игроков к GM/co-GM и переписка по вопросам игры.',
            href: route('games.tickets.index', props.game.id),
            accent: 'violet',
        });
    }

    if (props.game.can_manage_content) {
        links.push({
            label: 'NPC',
            description: 'Создание и настройка нейтралов, союзников и врагов.',
            href: route('games.npcs.index', props.game.id),
            accent: 'rose',
        });
        links.push({
            label: 'Предметы',
            description: 'Каталог игровых предметов для выдачи персонажам.',
            href: route('games.items.index', props.game.id),
            accent: 'amber',
        });
        links.push({
            label: 'Фоны',
            description: 'Коллекция сцен и изображений для активной сессии.',
            href: route('games.backgrounds.index', props.game.id),
            accent: 'violet',
        });
        links.push({
            label: 'Лист персонажа',
            description: 'Настройка шаблона характеристик, навыков и дополнительных полей.',
            href: route('games.character-template.edit', props.game.id),
            accent: 'emerald',
        });
    }

    return links;
});

const formForMember = (member) => {
    if (!memberRoleForms[member.id]) {
        memberRoleForms[member.id] = useForm({
            role: member.role,
        });
    }

    return memberRoleForms[member.id];
};

const updateMemberRole = (member) => {
    formForMember(member).patch(route('games.members.role.update', [props.game.id, member.id]), {
        preserveScroll: true,
        preserveState: true,
    });
};

const submitInvite = () => {
    inviteForm.post(route('games.invites.store', props.game.id), {
        preserveScroll: true,
        preserveState: true,
    });
};

const copyInvite = async () => {
    if (!activeInviteLink.value || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(activeInviteLink.value);
    copiedInvite.value = true;
    window.setTimeout(() => {
        copiedInvite.value = false;
    }, 1600);
};
</script>

<template>
    <Head :title="game.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Игра</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Центральный экран игры: состав, доступные разделы, приглашение и управление участниками.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.index')">
                        <SecondaryButton>К списку игр</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-white/10 bg-stone-950/45 p-5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Обзор кампании</p>
                                <h2 class="mt-3 text-3xl font-semibold text-amber-50">{{ game.current_user_role_label }}</h2>
                                <p v-if="game.description" class="mt-3 max-w-2xl text-sm leading-7 text-stone-300">{{ game.description }}</p>
                                <p v-else class="mt-3 max-w-2xl text-sm leading-7 text-stone-500">Описание игры пока не заполнено.</p>
                                <div class="mt-5 flex flex-wrap gap-3 text-sm">
                                    <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-stone-300">
                                        Владелец: <span class="font-semibold text-stone-100">{{ game.owner.name }}</span>
                                    </div>
                                    <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-stone-300">
                                        Участников: <span class="font-semibold text-stone-100">{{ game.members.length }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="page.props.flash.success" class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                                {{ page.props.flash.success }}
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                            <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                                <p class="text-sm text-stone-400">Всего участников</p>
                                <p class="mt-2 text-3xl font-semibold text-white">{{ game.members.length }}</p>
                            </article>
                            <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                                <p class="text-sm text-stone-400">Игроки</p>
                                <p class="mt-2 text-3xl font-semibold text-white">{{ playerCount }}</p>
                            </article>
                            <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                                <p class="text-sm text-stone-400">GM / co-GM</p>
                                <p class="mt-2 text-3xl font-semibold text-white">{{ managementCount }}</p>
                            </article>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <div class="space-y-6">
                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Навигация</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Разделы игры</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Все основные действия по игре собраны на одном экране.</p>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                <Link v-for="link in quickLinks" :key="link.label" :href="link.href">
                                    <article
                                        class="group h-full rounded-[1.4rem] border p-5 transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_50px_rgba(0,0,0,0.28)]"
                                        :class="{
                                            'border-amber-300/30 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.22),transparent_12rem),rgba(41,37,36,0.92)] hover:border-amber-200/45': link.accent === 'amber',
                                            'border-teal-300/25 bg-[radial-gradient(circle_at_top_left,rgba(45,212,191,0.2),transparent_12rem),rgba(28,25,23,0.92)] hover:border-teal-200/45': link.accent === 'teal',
                                            'border-sky-300/25 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.2),transparent_12rem),rgba(28,25,23,0.92)] hover:border-sky-200/45': link.accent === 'sky',
                                            'border-rose-300/25 bg-[radial-gradient(circle_at_top_left,rgba(251,113,133,0.2),transparent_12rem),rgba(28,25,23,0.92)] hover:border-rose-200/45': link.accent === 'rose',
                                            'border-violet-300/25 bg-[radial-gradient(circle_at_top_left,rgba(167,139,250,0.2),transparent_12rem),rgba(28,25,23,0.92)] hover:border-violet-200/45': link.accent === 'violet',
                                            'border-emerald-300/25 bg-[radial-gradient(circle_at_top_left,rgba(52,211,153,0.2),transparent_12rem),rgba(28,25,23,0.92)] hover:border-emerald-200/45': link.accent === 'emerald',
                                        }"
                                    >
                                        <div class="flex h-full flex-col">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p
                                                        class="text-xs font-semibold uppercase tracking-[0.18em]"
                                                        :class="{
                                                            'text-amber-100/80': link.accent === 'amber',
                                                            'text-teal-100/80': link.accent === 'teal',
                                                            'text-sky-100/80': link.accent === 'sky',
                                                            'text-rose-100/80': link.accent === 'rose',
                                                            'text-violet-100/80': link.accent === 'violet',
                                                            'text-emerald-100/80': link.accent === 'emerald',
                                                        }"
                                                    >
                                                        Раздел
                                                    </p>
                                                    <h3 class="mt-3 text-xl font-semibold text-stone-50">{{ link.label }}</h3>
                                                </div>
                                            </div>

                                            <p class="mt-4 flex-1 text-sm leading-6 text-stone-300">
                                                {{ link.description }}
                                            </p>

                                            <div class="mt-5 flex items-center justify-between text-sm">
                                                <span class="text-stone-500 transition group-hover:text-stone-300">Открыть раздел</span>
                                                <span
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border text-lg transition group-hover:translate-x-1"
                                                    :class="{
                                                        'border-amber-200/25 bg-amber-400/10 text-amber-100': link.accent === 'amber',
                                                        'border-teal-200/25 bg-teal-400/10 text-teal-100': link.accent === 'teal',
                                                        'border-sky-200/25 bg-sky-400/10 text-sky-100': link.accent === 'sky',
                                                        'border-rose-200/25 bg-rose-400/10 text-rose-100': link.accent === 'rose',
                                                        'border-violet-200/25 bg-violet-400/10 text-violet-100': link.accent === 'violet',
                                                        'border-emerald-200/25 bg-emerald-400/10 text-emerald-100': link.accent === 'emerald',
                                                    }"
                                                >
                                                    →
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                </Link>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Участники</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Состав игры</h2>
                                </div>
                                <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-stone-300">
                                    {{ game.members.length }} человек
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <article
                                    v-for="member in game.members"
                                    :key="member.id"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/25 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]"
                                >
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-50">{{ member.user.name }}</h3>
                                            <p class="mt-1 text-sm text-stone-400">{{ member.user.email }}</p>
                                        </div>
                                        <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-stone-300">
                                            {{ member.role_label }}
                                        </div>
                                    </div>

                                    <div v-if="game.can_manage_member_roles && member.user.id !== game.owner.id" class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                                        <div>
                                            <label class="fantasy-kicker">Роль</label>
                                            <select
                                                v-model="formForMember(member).role"
                                                class="mt-2 block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30"
                                            >
                                                <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <InputError class="mt-2" :message="formForMember(member).errors.role" />
                                        </div>

                                        <PrimaryButton :disabled="formForMember(member).processing" @click="updateMemberRole(member)">
                                            Сохранить роль
                                        </PrimaryButton>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <div class="space-y-6">
                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div>
                                <p class="fantasy-kicker">Приглашение</p>
                                <h2 class="mt-2 text-2xl font-semibold text-amber-50">Ссылка для входа</h2>
                                <p class="mt-2 text-sm leading-6 text-stone-400">Для игры может существовать одна активная ссылка приглашения.</p>
                            </div>

                            <form v-if="game.can_manage_invites" class="mt-6" @submit.prevent="submitInvite">
                                <PrimaryButton :disabled="inviteForm.processing">
                                    {{ activeInviteLink ? 'Перегенерировать ссылку' : 'Сгенерировать ссылку' }}
                                </PrimaryButton>
                            </form>

                            <div v-if="activeInviteLink" class="mt-6 rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5">
                                <p class="text-sm font-semibold text-stone-100">Активная ссылка</p>
                                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                                    <input
                                        :value="activeInviteLink"
                                        class="block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm"
                                        readonly
                                    />
                                    <SecondaryButton @click="copyInvite">
                                        {{ copiedInvite ? 'Скопировано' : 'Копировать' }}
                                    </SecondaryButton>
                                </div>
                            </div>

                            <div v-else class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                Активной ссылки пока нет.
                            </div>

                            <div v-if="!game.can_manage_invites" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                Управлять приглашениями могут только GM и co-GM.
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-teal-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <p class="fantasy-kicker">Подсказка</p>
                            <h2 class="mt-2 text-2xl font-semibold text-teal-50">Рабочий сценарий</h2>
                            <div class="mt-5 space-y-3 text-sm leading-6 text-stone-300">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    Сначала проверьте состав участников и роли, затем переходите к контенту и сессиям.
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    Для игроков основной вход обычно начинается с создания персонажа и просмотра своих листов.
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    Для мастера этот экран работает как центральная панель управления всей игрой.
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
