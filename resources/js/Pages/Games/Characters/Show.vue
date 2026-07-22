<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    character: { type: Object, required: true },
    template: { type: Object, required: true },
    catalogItems: { type: Array, required: true },
    can_manage_inventory: { type: Boolean, required: true },
});

const page = usePage();
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

const catalogForm = useForm({
    item_id: '',
    quantity: 1,
});

const customForm = useForm({
    custom_name: '',
    custom_description: '',
    custom_image: null,
    quantity: 1,
});

const skillGroups = computed(() => props.template.skills.items ?? []);
const flatSkillItems = computed(() => skillGroups.value.flatMap((skill) => [skill, ...(skill.subskills ?? [])]));
const enabledSkillCount = computed(() => flatSkillItems.value.filter((item) => normalizeBoolean(
    props.character.skill_values?.[item.key],
    normalizeBoolean(item.default),
)).length);
const attributeCount = computed(() => props.template.attributes.items.length);
const inventoryCount = computed(() => props.character.inventory_items.length);

const submitCatalog = () => {
    catalogForm.post(route('games.characters.inventory.store', [props.game.id, props.character.id]), {
        preserveScroll: true,
    });
};

const submitCustom = () => {
    customForm.post(route('games.characters.inventory.store', [props.game.id, props.character.id]), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => customForm.reset('custom_name', 'custom_description', 'custom_image', 'quantity'),
    });
};

const setCustomImage = (event) => {
    customForm.custom_image = event.target.files[0] ?? null;
};

const updateQuantity = (inventoryItem) => {
    useForm({ quantity: inventoryItem.quantity }).patch(
        route('games.characters.inventory.update', [props.game.id, props.character.id, inventoryItem.id]),
        { preserveScroll: true },
    );
};

const removeInventoryItem = (inventoryItem) => {
    useForm({}).delete(
        route('games.characters.inventory.destroy', [props.game.id, props.character.id, inventoryItem.id]),
        { preserveScroll: true },
    );
};

const skillValueLabel = (item) => normalizeBoolean(
    props.character.skill_values?.[item.key],
    normalizeBoolean(item.default),
) ? 'Есть' : 'Нет';
const fieldValue = (item) => props.character.extra_field_values?.[item.key] ?? '—';
const modifierPreview = (item) => {
    if (!item.roll?.enabled) {
        return 'Без броска';
    }

    const modifier = Number(props.character.attribute_values?.[item.key] ?? item.default ?? 0);

    return `${item.roll.dice} ${modifier >= 0 ? '+' : ''}${modifier}`;
};
</script>

<template>
    <Head :title="character.name" />

    <GameThemeLayout :game="game" section="Лист персонажа" :title="character.name">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Персонаж</p>
                    <h1 class="fantasy-title text-3xl">{{ character.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Полный лист персонажа: основные параметры, навыки, заметки и инвентарь.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.characters.index', game.id)">
                        <SecondaryButton>К списку персонажей</SecondaryButton>
                    </Link>
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="grid gap-6 xl:grid-cols-[16rem_1fr]">
                        <div class="flex justify-center xl:justify-start">
                            <div class="flex aspect-[3/4] w-56 items-center justify-center theme-media">
                                <img v-if="character.avatar_url" :src="character.avatar_url" alt="Портрет персонажа" class="h-full w-full object-contain" />
                                <div v-else class="px-6 text-center text-xs uppercase tracking-[0.24em] text-stone-500">
                                    Портрет не задан
                                </div>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Просмотр листа персонажа</p>
                                    <h2 class="theme-panel-title mt-2">{{ character.name }}</h2>
                                    <p v-if="character.origin" class="mt-3 max-w-3xl text-sm leading-6 text-stone-300">{{ character.origin }}</p>
                                    <p v-else class="mt-3 text-sm leading-6 text-stone-500">Происхождение не указано.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="gm-badge">{{ attributeCount }} характеристик</span>
                                    <span class="gm-badge">{{ enabledSkillCount }} / {{ flatSkillItems.length }} навыков</span>
                                    <span class="gm-badge">{{ inventoryCount }} предметов</span>
                                </div>
                            </div>
                            <div class="mt-5 grid gap-3 text-sm text-stone-300 sm:grid-cols-2">
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Игрок</div>
                                    <div class="mt-2 font-semibold text-stone-100">{{ character.user?.name ?? 'Не указан' }}</div>
                                </div>
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Контакт</div>
                                    <div class="mt-2 font-semibold text-stone-100">{{ character.user?.email ?? 'Не указан' }}</div>
                                </div>
                            </div>
                            <div v-if="character.notes" class="mt-4 theme-list-row text-sm leading-7 text-stone-300">
                                {{ character.notes }}
                            </div>
                            <div v-if="page.props.flash.success" class="gm-alert gm-alert-success mt-5">
                                {{ page.props.flash.success }}
                            </div>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div class="space-y-6">
                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Характеристики</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Основные параметры</h2>
                                </div>
                            </div>

                            <div v-if="template.attributes.items.length === 0" class="mt-6 theme-empty">
                                У персонажа пока нет характеристик по шаблону.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="item in template.attributes.items"
                                    :key="item.key"
                                    class="theme-card theme-card-interactive"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-50">{{ item.label }}</h3>
                                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">{{ modifierPreview(item) }}</p>
                                        </div>
                                        <div class="gm-badge text-lg">
                                            {{ character.attribute_values[item.key] ?? item.default ?? 0 }}
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Навыки</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Подготовка персонажа</h2>
                                </div>
                                <div class="gm-badge">
                                    Активно {{ enabledSkillCount }} из {{ flatSkillItems.length }}
                                </div>
                            </div>

                            <div v-if="skillGroups.length === 0" class="mt-6 theme-empty">
                                Навыки в шаблоне пока не заданы.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="skill in skillGroups"
                                    :key="skill.key"
                                    class="theme-card theme-card-interactive"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-teal-50">{{ skill.label }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-stone-400">Основной навык персонажа.</p>
                                        </div>
                                        <span
                                            class="gm-badge py-2"
                                            :class="skillValueLabel(skill) === 'Есть' ? 'gm-badge-success' : 'gm-badge-muted'"
                                        >
                                            {{ skillValueLabel(skill) }}
                                        </span>
                                    </div>

                                    <div v-if="skill.subskills?.length" class="mt-5 space-y-3">
                                        <div
                                            v-for="subskill in skill.subskills"
                                            :key="subskill.key"
                                            class="theme-list-row flex items-center justify-between px-4 py-3"
                                        >
                                            <div>
                                                <div class="font-medium text-stone-100">{{ subskill.label }}</div>
                                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Поднавык</div>
                                            </div>
                                            <span
                                                class="gm-badge py-2"
                                                :class="skillValueLabel(subskill) === 'Есть' ? 'gm-badge-success' : 'gm-badge-muted'"
                                            >
                                                {{ skillValueLabel(subskill) }}
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Дополнительно</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Поля листа</h2>
                                </div>
                            </div>

                            <div v-if="template.extra_fields.length === 0" class="mt-6 theme-empty">
                                Дополнительные поля для этого листа не настроены.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="item in template.extra_fields"
                                    :key="item.key"
                                    class="theme-card theme-card-interactive"
                                >
                                    <div class="text-sm uppercase tracking-[0.18em] text-stone-500">{{ item.label }}</div>
                                    <div class="mt-3 whitespace-pre-wrap text-base leading-7 text-stone-100">{{ fieldValue(item) }}</div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <div class="space-y-6">
                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Инвентарь</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Предметы персонажа</h2>
                                </div>
                            </div>

                            <div v-if="can_manage_inventory" class="mt-6 grid gap-4">
                                <form class="theme-card" @submit.prevent="submitCatalog">
                                    <h3 class="text-lg font-semibold text-stone-100">Выдать предмет из каталога</h3>
                                    <div class="mt-4">
                                        <InputLabel for="catalog-item" value="Предмет" />
                                        <select id="catalog-item" v-model="catalogForm.item_id" class="fantasy-select mt-2 block w-full">
                                            <option value="">Выберите предмет</option>
                                            <option v-for="item in catalogItems" :key="item.id" :value="item.id">
                                                {{ item.name }}{{ item.category ? ` (${item.category})` : '' }}
                                            </option>
                                        </select>
                                        <InputError class="mt-2" :message="catalogForm.errors.item_id" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="catalog-quantity" value="Количество" />
                                        <input id="catalog-quantity" v-model.number="catalogForm.quantity" type="number" min="1" class="fantasy-input mt-2 block w-full" />
                                        <InputError class="mt-2" :message="catalogForm.errors.quantity" />
                                    </div>
                                    <PrimaryButton class="mt-4" :disabled="catalogForm.processing">Выдать предмет</PrimaryButton>
                                </form>

                                <form class="theme-card" @submit.prevent="submitCustom">
                                    <h3 class="text-lg font-semibold text-stone-100">Выдать кастомный предмет</h3>
                                    <div class="mt-4">
                                        <InputLabel for="custom-name" value="Название" />
                                        <TextInput id="custom-name" v-model="customForm.custom_name" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="customForm.errors.custom_name" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="custom-description" value="Описание" />
                                        <textarea id="custom-description" v-model="customForm.custom_description" class="fantasy-textarea mt-2 block min-h-28 w-full" />
                                        <InputError class="mt-2" :message="customForm.errors.custom_description" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="custom-image" value="Изображение" />
                                        <input id="custom-image" type="file" accept="image/*" class="fantasy-file mt-2 block w-full" @change="setCustomImage" />
                                        <InputError class="mt-2" :message="customForm.errors.custom_image" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="custom-quantity" value="Количество" />
                                        <input id="custom-quantity" v-model.number="customForm.quantity" type="number" min="1" class="fantasy-input mt-2 block w-full" />
                                        <InputError class="mt-2" :message="customForm.errors.quantity" />
                                    </div>
                                    <PrimaryButton class="mt-4" :disabled="customForm.processing">Выдать кастомный предмет</PrimaryButton>
                                </form>
                            </div>

                            <div v-if="character.inventory_items.length === 0" class="mt-6 theme-empty">
                                Инвентарь пока пуст.
                            </div>

                            <div v-else class="mt-6 space-y-4">
                                <article
                                    v-for="inventoryItem in character.inventory_items"
                                    :key="inventoryItem.id"
                                    class="theme-card theme-card-interactive"
                                >
                                    <div class="flex flex-col gap-4">
                                        <div class="flex gap-4">
                                            <div v-if="inventoryItem.image_url" class="h-20 w-20 overflow-hidden theme-media">
                                                <img :src="inventoryItem.image_url" alt="Изображение предмета" class="h-full w-full object-contain" />
                                            </div>
                                            <div v-else class="grid h-20 w-20 place-items-center theme-media text-[11px] uppercase tracking-[0.18em] text-stone-500">
                                                Предмет
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <h3 class="text-lg font-semibold text-stone-100">{{ inventoryItem.name }}</h3>
                                                    <span v-if="inventoryItem.is_custom" class="rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-xs uppercase tracking-[0.2em] text-amber-200">
                                                        Кастомный
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm text-stone-400">Количество: {{ inventoryItem.quantity }}</p>
                                                <p v-if="inventoryItem.description" class="mt-3 text-sm leading-7 text-stone-300">{{ inventoryItem.description }}</p>
                                            </div>
                                        </div>

                                        <div v-if="can_manage_inventory" class="flex flex-wrap items-center gap-3">
                                            <form class="flex flex-wrap items-center gap-3" @submit.prevent="updateQuantity(inventoryItem)">
                                                <input v-model.number="inventoryItem.quantity" type="number" min="1" class="fantasy-input w-24" />
                                                <PrimaryButton>Обновить</PrimaryButton>
                                            </form>
                                            <form @submit.prevent="removeInventoryItem(inventoryItem)">
                                                <DangerButton>Удалить</DangerButton>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </GameThemeLayout>
</template>
