
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    template: { type: Object, required: true },
    npcs: { type: Array, required: true },
    npcTypes: { type: Array, required: true },
    selectedNpc: { type: Object, default: null },
    characterSheetAvailable: { type: Boolean, required: true },
});

const page = usePage();
const deleteModalNpc = ref(null);
const avatarInputKey = ref(0);
const normalizeBoolean = (value, fallback = false) => {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    const normalized = String(value).toLowerCase();

    if (['1', 'true', 'on', 'yes'].includes(normalized)) {
        return true;
    }

    if (['0', 'false', 'off', 'no'].includes(normalized)) {
        return false;
    }

    return fallback;
};
const flattenSkillItems = (items) => items.flatMap((skill) => [skill, ...(skill.subskills ?? [])]);
const templateAttributeItems = computed(() => props.template.attributes?.items ?? []);
const templateSkillItems = computed(() => flattenSkillItems(props.template.skills?.items ?? []));
const templateExtraItems = computed(() => props.template.extra_fields ?? []);

const buildSheetState = (npc = null) => ({
    attribute_values: Object.fromEntries(templateAttributeItems.value.map((item) => [
        item.key,
        npc?.attribute_values?.[item.key] ?? item.default ?? 0,
    ])),
    skill_values: Object.fromEntries(templateSkillItems.value.map((item) => [
        item.key,
        normalizeBoolean(npc?.skill_values?.[item.key], normalizeBoolean(item.default)),
    ])),
    extra_field_values: Object.fromEntries(templateExtraItems.value.map((item) => [
        item.key,
        npc?.extra_field_values?.[item.key] ?? item.default ?? (item.type === 'number' ? 0 : ''),
    ])),
});

const emptyForm = () => ({
    name: '',
    avatar: null,
    type: props.npcTypes[0]?.value ?? 'neutral',
    description: '',
    uses_character_sheet: false,
    ...buildSheetState(),
});

const form = useForm(emptyForm());
const deleteForm = useForm({});
const activeNpcId = ref(props.selectedNpc?.id ?? null);

const activeNpc = computed(() => props.npcs.find((npc) => npc.id === activeNpcId.value) ?? null);
const isEditing = computed(() => activeNpc.value !== null);
const submitLabel = computed(() => {
    if (form.processing) {
        return isEditing.value ? 'Сохраняем...' : 'Создаём...';
    }

    return isEditing.value ? 'Сохранить NPC' : 'Создать NPC';
});

const applyNpcToForm = (npc) => {
    form.defaults({
        name: npc?.name ?? '',
        avatar: null,
        type: npc?.type ?? (props.npcTypes[0]?.value ?? 'neutral'),
        description: npc?.description ?? '',
        uses_character_sheet: props.characterSheetAvailable ? Boolean(npc?.uses_character_sheet) : false,
        ...buildSheetState(npc),
    });

    form.reset();
    form.clearErrors();
    avatarInputKey.value += 1;
};

const startCreate = () => {
    activeNpcId.value = null;
    applyNpcToForm(null);
};

const selectNpc = (npc) => {
    activeNpcId.value = npc.id;
    applyNpcToForm(npc);
};

const setAvatar = (event) => {
    form.avatar = event.target.files[0] ?? null;
};

const submit = () => {
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(route('games.npcs.update', [props.game.id, activeNpc.value.id]), {
            forceFormData: true,
            preserveScroll: true,
        });

        return;
    }

    form.transform((data) => data).post(route('games.npcs.store', props.game.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const confirmDelete = (npc) => {
    deleteModalNpc.value = npc;
};

const destroyNpc = () => {
    if (!deleteModalNpc.value) {
        return;
    }

    deleteForm.delete(route('games.npcs.destroy', [props.game.id, deleteModalNpc.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            if (activeNpcId.value === deleteModalNpc.value.id) {
                startCreate();
            }

            deleteModalNpc.value = null;
        },
    });
};

watch(
    () => props.selectedNpc,
    (npc) => {
        activeNpcId.value = npc?.id ?? null;
        applyNpcToForm(npc ?? null);
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="`NPC - ${game.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">NPC</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Соберите библиотеку персонажей мастера и редактируйте их в одном рабочем экране.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                    <PrimaryButton @click="startCreate">Новый NPC</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-white/10 bg-stone-950/45 p-5 backdrop-blur">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Библиотека NPC</p>
                                        <h2 class="mt-3 text-3xl font-semibold text-amber-50">Список и редактор на одном экране</h2>
                                        <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">Выберите существующего NPC для правки или создайте нового, не переключаясь между отдельными страницами.</p>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-400/25 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                                        Удаление доступно прямо из списка и формы
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Всего NPC</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ npcs.length }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Выбранный режим</p>
                                    <p class="mt-2 text-2xl font-semibold text-white">{{ isEditing ? 'Редактирование' : 'Создание' }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Активный NPC</p>
                                    <p class="mt-2 truncate text-2xl font-semibold text-white">{{ activeNpc?.name ?? 'Не выбран' }}</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Подсказка для GM</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">Если NPC должен использовать те же характеристики, что и игроки, просто подключите к нему игровой лист. Для фоновых персонажей можно оставить лист отключённым.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Рабочий процесс</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">Нажмите на карточку слева, чтобы сразу открыть NPC на редактирование. Кнопка «Новый NPC» возвращает форму в режим создания.</p>
                                <div v-if="page.props.flash.success" class="mt-4 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                                    {{ page.props.flash.success }}
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
                <div class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
                    <section class="space-y-6">
                        <article class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Список NPC</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Все действующие лица</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Выберите персонажа для редактирования или удалите его прямо из списка.</p>
                                </div>
                                <PrimaryButton type="button" @click="startCreate">Создать нового</PrimaryButton>
                            </div>

                            <div v-if="npcs.length === 0" class="mt-5 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                NPC пока не добавлены.
                            </div>

                            <div v-else class="mt-5 space-y-4">
                                <article
                                    v-for="npc in npcs"
                                    :key="npc.id"
                                    class="rounded-[1.4rem] border p-4 transition duration-300"
                                    :class="activeNpc?.id === npc.id
                                        ? 'border-amber-300/40 bg-amber-300/10 shadow-[0_0_40px_rgba(251,191,36,0.08)]'
                                        : 'border-stone-700/50 bg-stone-900/80 hover:-translate-y-0.5 hover:border-amber-300/25'"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <button type="button" class="flex min-w-0 flex-1 items-start gap-4 text-left" @click="selectNpc(npc)">
                                            <div v-if="npc.avatar_url" class="h-20 w-20 overflow-hidden rounded-2xl border border-white/10 bg-stone-950/60">
                                                <img :src="npc.avatar_url" alt="Аватар NPC" class="h-full w-full object-cover" />
                                            </div>
                                            <div v-else class="grid h-20 w-20 place-items-center rounded-2xl border border-dashed border-stone-600/50 bg-stone-950/50 text-xs uppercase tracking-[0.2em] text-stone-500">
                                                NPC
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="truncate text-lg font-semibold text-amber-50">{{ npc.name }}</h3>
                                                    <span class="fantasy-chip-muted">{{ npc.type_label }}</span>
                                                    <span
                                                        v-if="npc.uses_character_sheet"
                                                        class="rounded-full border border-teal-300/25 bg-teal-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-teal-100"
                                                    >
                                                        лист подключён
                                                    </span>
                                                </div>
                                                <p v-if="npc.description" class="mt-2 text-sm leading-6 text-stone-400">{{ npc.description }}</p>
                                                <p v-else class="mt-2 text-sm text-stone-500">Без описания.</p>
                                            </div>
                                        </button>

                                        <div class="flex items-center gap-2">
                                            <SecondaryButton type="button" @click="selectNpc(npc)">Изменить</SecondaryButton>
                                            <button
                                                type="button"
                                                class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20"
                                                @click="confirmDelete(npc)"
                                            >
                                                Удалить
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </article>
                    </section>

                    <section class="space-y-6">
                        <article class="rounded-[1.75rem] border border-teal-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker text-teal-200/80">Редактор</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-teal-50">{{ isEditing ? 'Редактирование NPC' : 'Создание NPC' }}</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">{{ isEditing ? 'Измените данные выбранного NPC и сохраните их без перехода на другую страницу.' : 'Заполните карточку нового NPC и сразу добавьте его в библиотеку игры.' }}</p>
                                </div>
                                <div class="flex gap-3">
                                    <SecondaryButton type="button" @click="startCreate">Очистить форму</SecondaryButton>
                                    <button
                                        v-if="isEditing"
                                        type="button"
                                        class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20"
                                        @click="confirmDelete(activeNpc)"
                                    >
                                        Удалить NPC
                                    </button>
                                </div>
                            </div>

                            <form class="mt-6 space-y-5" @submit.prevent="submit">
                                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_15rem]">
                                    <div>
                                        <InputLabel for="npc-name" value="Имя" />
                                        <TextInput id="npc-name" v-model="form.name" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.name" />
                                    </div>
                                    <div>
                                        <InputLabel for="npc-type" value="Тип" />
                                        <select id="npc-type" v-model="form.type" class="fantasy-select mt-2 block w-full">
                                            <option v-for="type in npcTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.type" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel :for="`npc-avatar-${avatarInputKey}`" value="Аватар" />
                                    <input :id="`npc-avatar-${avatarInputKey}`" :key="avatarInputKey" type="file" accept="image/*" class="fantasy-file mt-2 block w-full" @change="setAvatar" />
                                    <InputError class="mt-2" :message="form.errors.avatar" />
                                </div>

                                <div v-if="activeNpc?.avatar_url" class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4">
                                    <p class="text-sm font-medium text-stone-300">Текущий аватар</p>
                                    <img :src="activeNpc.avatar_url" alt="Текущий аватар NPC" class="mt-3 h-28 w-28 rounded-2xl object-cover ring-1 ring-amber-300/20" />
                                </div>

                                <div>
                                    <InputLabel for="npc-description" value="Описание" />
                                    <textarea id="npc-description" v-model="form.description" class="fantasy-textarea mt-2 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.description" />
                                </div>

                                <div class="rounded-[1.25rem] border border-white/10 bg-white/[0.03] p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-stone-100">Лист характеристик</p>
                                            <p class="mt-2 text-sm leading-6 text-stone-400">
                                                {{ props.characterSheetAvailable
                                                    ? 'Подключает к NPC текущий шаблон листа персонажа этой игры.'
                                                    : 'Сначала создайте шаблон листа персонажа в разделе листа характеристик, затем его можно будет подключить к NPC.' }}
                                            </p>
                                        </div>
                                        <label
                                            class="inline-flex items-center gap-3 rounded-full border px-3 py-2 text-sm transition"
                                            :class="props.characterSheetAvailable
                                                ? 'border-white/8 bg-white/[0.04] text-stone-200 hover:bg-white/[0.08]'
                                                : 'cursor-not-allowed border-stone-700/60 bg-stone-900/60 text-stone-500'"
                                        >
                                            <input
                                                v-model="form.uses_character_sheet"
                                                :disabled="!props.characterSheetAvailable"
                                                type="checkbox"
                                                class="peer sr-only"
                                            />
                                            <span
                                                class="relative h-6 w-11 rounded-full transition after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition"
                                                :class="form.uses_character_sheet && props.characterSheetAvailable
                                                    ? 'bg-teal-500/70 after:translate-x-5'
                                                    : 'bg-stone-700/80'"
                                            />
                                            {{ form.uses_character_sheet ? 'Подключён' : 'Отключён' }}
                                        </label>
                                    </div>
                                    <InputError class="mt-3" :message="form.errors.uses_character_sheet" />
                                </div>

                                <div v-if="form.uses_character_sheet && props.characterSheetAvailable" class="space-y-5 rounded-[1.35rem] border border-teal-300/15 bg-stone-950/55 p-5">
                                    <section v-if="templateAttributeItems.length">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-teal-50">Характеристики NPC</p>
                                                <p class="mt-1 text-sm text-stone-400">Числовые значения для конкретного NPC по шаблону игры.</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                            <label v-for="item in templateAttributeItems" :key="`npc-attr-${item.key}`" class="rounded-xl border border-stone-700/50 bg-stone-900/75 p-4">
                                                <span class="text-sm text-stone-300">{{ item.label }}</span>
                                                <input
                                                    v-model.number="form.attribute_values[item.key]"
                                                    type="number"
                                                    :min="item.min ?? undefined"
                                                    :max="item.max ?? undefined"
                                                    class="fantasy-input mt-2 block w-full"
                                                />
                                            </label>
                                        </div>
                                    </section>

                                    <section v-if="templateSkillItems.length">
                                        <div>
                                            <p class="text-sm font-semibold text-teal-50">Навыки NPC</p>
                                            <p class="mt-1 text-sm text-stone-400">Переключатели навыков и поднавыков для этого NPC.</p>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <label
                                                v-for="item in templateSkillItems"
                                                :key="`npc-skill-${item.key}`"
                                                class="flex items-center justify-between gap-3 rounded-xl border border-stone-700/50 bg-stone-900/75 px-4 py-3"
                                            >
                                                <span class="text-sm text-stone-200">{{ item.label }}</span>
                                                <span class="inline-flex items-center gap-3 rounded-full border border-white/8 bg-white/[0.04] px-3 py-2 text-sm text-stone-200">
                                                    <input v-model="form.skill_values[item.key]" type="checkbox" class="peer sr-only" />
                                                    <span class="relative h-6 w-11 rounded-full bg-stone-700/80 transition after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:bg-teal-500/70 peer-checked:after:translate-x-5" />
                                                    {{ form.skill_values[item.key] ? 'Есть' : 'Нет' }}
                                                </span>
                                            </label>
                                        </div>
                                    </section>

                                    <section v-if="templateExtraItems.length">
                                        <div>
                                            <p class="text-sm font-semibold text-teal-50">Дополнительные поля NPC</p>
                                            <p class="mt-1 text-sm text-stone-400">Сюжетные и вспомогательные значения по тому же шаблону.</p>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <label
                                                v-for="item in templateExtraItems"
                                                :key="`npc-extra-${item.key}`"
                                                class="rounded-xl border border-stone-700/50 bg-stone-900/75 p-4"
                                            >
                                                <span class="text-sm text-stone-300">{{ item.label }}</span>
                                                <textarea
                                                    v-if="item.type === 'textarea'"
                                                    v-model="form.extra_field_values[item.key]"
                                                    class="fantasy-textarea mt-2 block w-full"
                                                />
                                                <input
                                                    v-else
                                                    v-model="form.extra_field_values[item.key]"
                                                    :type="item.type === 'number' ? 'number' : 'text'"
                                                    class="fantasy-input mt-2 block w-full"
                                                />
                                            </label>
                                        </div>
                                    </section>
                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <SecondaryButton type="button" @click="startCreate">Сбросить</SecondaryButton>
                                    <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
                                </div>
                            </form>
                        </article>
                    </section>
                </div>
            </div>
        </div>

        <Modal :show="deleteModalNpc !== null" @close="deleteModalNpc = null">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Удалить NPC</h2>
                <p class="mt-3 text-sm leading-6 text-stone-400">
                    NPC <span class="font-semibold text-stone-100">{{ deleteModalNpc?.name }}</span> будет удалён из библиотеки игры. Это действие нельзя отменить.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="deleteModalNpc = null">Отмена</SecondaryButton>
                    <button
                        type="button"
                        class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-4 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="deleteForm.processing"
                        @click="destroyNpc"
                    >
                        {{ deleteForm.processing ? 'Удаляем...' : 'Удалить NPC' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
