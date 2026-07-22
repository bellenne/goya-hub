
<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
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
const search = ref('');
const typeFilter = ref('');
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
const filteredNpcs = computed(() => {
    const query = search.value.trim().toLowerCase();
    const selectedType = typeFilter.value;

    return props.npcs.filter((npc) => {
        const matchesName = !query || npc.name.toLowerCase().includes(query);
        const matchesType = !selectedType || npc.type === selectedType;

        return matchesName && matchesType;
    });
});
const submitLabel = computed(() => {
    if (form.processing) {
        return isEditing.value ? 'Сохраняем...' : 'Создаём...';
    }

    return isEditing.value ? 'Сохранить НПС' : 'Создать НПС';
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
    <Head :title="`НПС - ${game.name}`" />

    <GameThemeLayout :game="game" section="НПС" :title="game.name">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">НПС</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Соберите библиотеку персонажей мастера и редактируйте их в одном рабочем экране.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                    <PrimaryButton @click="startCreate">Новый НПС</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <div v-if="page.props.flash.success" class="gm-alert gm-alert-success">
                    {{ page.props.flash.success }}
                </div>

                <div class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
                    <section class="space-y-6">
                        <article class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Список НПС</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Все действующие лица</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Выберите персонажа для редактирования или удалите его прямо из списка.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="gm-badge">{{ npcs.length }} всего</span>
                                    <span class="gm-badge">{{ filteredNpcs.length }} показано</span>
                                    <span class="gm-badge">{{ isEditing ? 'Редактирование' : 'Создание' }}</span>
                                </div>
                                <PrimaryButton type="button" @click="startCreate">Создать нового</PrimaryButton>
                            </div>
                            <div class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_14rem]">
                                <div>
                                    <InputLabel for="npc-search" value="Поиск по имени" />
                                    <TextInput id="npc-search" v-model="search" class="mt-2 block w-full" placeholder="Введите имя НПС" />
                                </div>
                                <div>
                                    <InputLabel for="npc-type-filter" value="Фильтр по типу" />
                                    <select id="npc-type-filter" v-model="typeFilter" class="fantasy-select mt-2 block w-full">
                                        <option value="">Все типы</option>
                                        <option v-for="type in npcTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="npcs.length === 0" class="mt-5 theme-empty">
                                НПС пока не добавлены.
                            </div>
                            <div v-else-if="filteredNpcs.length === 0" class="mt-5 theme-empty">
                                НПС по текущим фильтрам не найдены.
                            </div>

                            <div v-else class="mt-5 space-y-4">
                                <article
                                    v-for="npc in filteredNpcs"
                                    :key="npc.id"
                                    class="theme-list-row"
                                    :class="activeNpc?.id === npc.id
                                        ? 'border-amber-300/40 bg-amber-300/10 shadow-[0_0_40px_rgba(251,191,36,0.08)]'
                                        : 'theme-card-interactive'"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <button type="button" class="flex min-w-0 flex-1 items-start gap-4 text-left" @click="selectNpc(npc)">
                                            <div v-if="npc.avatar_url" class="h-20 w-20 overflow-hidden theme-media">
                                                <img :src="npc.avatar_url" alt="Аватар НПС" class="h-full w-full object-contain" />
                                            </div>
                                            <div v-else class="grid h-20 w-20 place-items-center theme-media text-xs uppercase tracking-[0.2em] text-stone-500">
                                                НПС
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
                                                class="gm-button gm-button-danger px-3 py-2 text-sm"
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
                        <article class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker text-teal-200/80">Редактор</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-teal-50">{{ isEditing ? 'Редактирование НПС' : 'Создание НПС' }}</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">{{ isEditing ? 'Измените данные выбранного НПС и сохраните их без перехода на другую страницу.' : 'Заполните карточку нового НПС и сразу добавьте его в библиотеку игры.' }}</p>
                                </div>
                                <div class="flex gap-3">
                                    <SecondaryButton type="button" @click="startCreate">Очистить форму</SecondaryButton>
                                    <button
                                        v-if="isEditing"
                                        type="button"
                                        class="gm-button gm-button-danger px-3 py-2 text-sm"
                                        @click="confirmDelete(activeNpc)"
                                    >
                                        Удалить НПС
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

                                <div v-if="activeNpc?.avatar_url" class="theme-card">
                                    <p class="text-sm font-medium text-stone-300">Текущий аватар</p>
                                    <div class="mt-3 h-28 w-28 theme-media">
                                        <img :src="activeNpc.avatar_url" alt="Текущий аватар НПС" class="h-full w-full object-contain" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="npc-description" value="Описание" />
                                    <textarea id="npc-description" v-model="form.description" class="fantasy-textarea mt-2 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.description" />
                                </div>

                                <div class="theme-card">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-stone-100">Лист характеристик</p>
                                            <p class="mt-2 text-sm leading-6 text-stone-400">
                                                {{ props.characterSheetAvailable
                                                    ? 'Подключает к НПС текущий шаблон листа персонажа этой игры.'
                                                    : 'Сначала создайте шаблон листа персонажа в разделе листа характеристик, затем его можно будет подключить к НПС.' }}
                                            </p>
                                        </div>
                                        <label
                                            class="gm-badge inline-flex items-center gap-3 text-sm transition"
                                            :class="props.characterSheetAvailable
                                                ? 'text-stone-200'
                                                : 'cursor-not-allowed text-stone-500'"
                                        >
                                            <input
                                                v-model="form.uses_character_sheet"
                                                :disabled="!props.characterSheetAvailable"
                                                type="checkbox"
                                                class="peer sr-only"
                                            />
                                            <span
                                                class="gm-toggle-track"
                                                :class="form.uses_character_sheet && props.characterSheetAvailable
                                                    ? 'gm-toggle-track-active'
                                                    : ''"
                                            />
                                            {{ form.uses_character_sheet ? 'Подключён' : 'Отключён' }}
                                        </label>
                                    </div>
                                    <InputError class="mt-3" :message="form.errors.uses_character_sheet" />
                                </div>

                                <div v-if="form.uses_character_sheet && props.characterSheetAvailable" class="theme-card space-y-5">
                                    <section v-if="templateAttributeItems.length">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-teal-50">Характеристики НПС</p>
                                                <p class="mt-1 text-sm text-stone-400">Числовые значения для конкретного НПС по шаблону игры.</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                            <label v-for="item in templateAttributeItems" :key="`npc-attr-${item.key}`" class="theme-card">
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
                                            <p class="text-sm font-semibold text-teal-50">Навыки НПС</p>
                                            <p class="mt-1 text-sm text-stone-400">Переключатели навыков и поднавыков для этого НПС.</p>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <label
                                                v-for="item in templateSkillItems"
                                                :key="`npc-skill-${item.key}`"
                                                class="theme-list-row flex items-center justify-between gap-3"
                                            >
                                                <span class="text-sm text-stone-200">{{ item.label }}</span>
                                                <span class="gm-badge inline-flex items-center gap-3 text-sm text-stone-200">
                                                    <input v-model="form.skill_values[item.key]" type="checkbox" class="peer sr-only" />
                                                    <span class="gm-toggle-track" />
                                                    {{ form.skill_values[item.key] ? 'Есть' : 'Нет' }}
                                                </span>
                                            </label>
                                        </div>
                                    </section>

                                    <section v-if="templateExtraItems.length">
                                        <div>
                                            <p class="text-sm font-semibold text-teal-50">Дополнительные поля НПС</p>
                                            <p class="mt-1 text-sm text-stone-400">Сюжетные и вспомогательные значения по тому же шаблону.</p>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <label
                                                v-for="item in templateExtraItems"
                                                :key="`npc-extra-${item.key}`"
                                                class="theme-card"
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
                <h2 class="text-lg font-semibold text-amber-50">Удалить НПС</h2>
                <p class="mt-3 text-sm leading-6 text-stone-400">
                    НПС <span class="font-semibold text-stone-100">{{ deleteModalNpc?.name }}</span> будет удалён из библиотеки игры. Это действие нельзя отменить.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="deleteModalNpc = null">Отмена</SecondaryButton>
                    <button
                        type="button"
                        class="gm-button gm-button-danger px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="deleteForm.processing"
                        @click="destroyNpc"
                    >
                        {{ deleteForm.processing ? 'Удаляем...' : 'Удалить НПС' }}
                    </button>
                </div>
            </div>
        </Modal>
    </GameThemeLayout>
</template>
