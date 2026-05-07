
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    template: { type: Object, required: true },
});

const page = usePage();
const diceOptions = ['d4', 'd6', 'd8', 'd10', 'd12', 'd20', 'd100'];
const fieldTypes = [
    { value: 'text', label: 'Короткий текст' },
    { value: 'textarea', label: 'Большой текст' },
    { value: 'number', label: 'Число' },
];
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

const transliterationMap = {
    а: 'a', б: 'b', в: 'v', г: 'g', д: 'd', е: 'e', ё: 'e', ж: 'zh', з: 'z', и: 'i', й: 'y',
    к: 'k', л: 'l', м: 'm', н: 'n', о: 'o', п: 'p', р: 'r', с: 's', т: 't', у: 'u', ф: 'f',
    х: 'h', ц: 'ts', ч: 'ch', ш: 'sh', щ: 'sch', ъ: '', ы: 'y', ь: '', э: 'e', ю: 'yu', я: 'ya',
};

const clone = (value) => JSON.parse(JSON.stringify(value));

const slugify = (value) => String(value ?? '')
    .toLowerCase()
    .split('')
    .map((char) => transliterationMap[char] ?? char)
    .join('')
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '');

const nextUniqueKey = (seed, prefix, usedKeys) => {
    const base = slugify(seed) || prefix;
    let index = 1;
    let candidate = base;

    while (usedKeys.has(candidate)) {
        index += 1;
        candidate = `${base}_${index}`;
    }

    usedKeys.add(candidate);

    return candidate;
};

const resolveKey = (existingKey, label, prefix, usedKeys, fallbackIndex) => {
    const seed = slugify(existingKey) || slugify(label) || `${prefix}_${fallbackIndex}`;
    return nextUniqueKey(seed, prefix, usedKeys);
};

const form = useForm({
    attributes: {
        points: props.template.attributes?.points ?? 0,
        items: clone(props.template.attributes?.items ?? []).map((item) => ({
            ...item,
            player_editable: normalizeBoolean(item.player_editable, true),
            roll: {
                enabled: normalizeBoolean(item.roll?.enabled, true),
                dice: item.roll?.dice ?? 'd20',
            },
        })),
    },
    skills: {
        points: 0,
        items: clone(props.template.skills?.items ?? []).map((skill) => ({
            ...skill,
            default: normalizeBoolean(skill.default),
            player_editable: normalizeBoolean(skill.player_editable, true),
            subskills: (skill.subskills ?? []).map((subskill) => ({
                ...subskill,
                default: normalizeBoolean(subskill.default),
                player_editable: normalizeBoolean(subskill.player_editable, true),
            })),
        })),
    },
    points: clone(props.template.points ?? {}),
    extra_fields: clone(props.template.extra_fields ?? []).map((field) => ({
        ...field,
        required: normalizeBoolean(field.required),
        player_editable: normalizeBoolean(field.player_editable, true),
    })),
});

const totalSkillCount = computed(() => form.skills.items.reduce(
    (sum, skill) => sum + 1 + (skill.subskills?.length ?? 0),
    0,
));

const activeByDefaultCount = computed(() => form.skills.items.reduce((sum, skill) => {
    const subskills = (skill.subskills ?? []).filter((item) => item.default).length;

    return sum + (skill.default ? 1 : 0) + subskills;
}, 0));

const rollEnabledCount = computed(() => form.attributes.items.filter((item) => item.roll?.enabled).length);

const addAttribute = () => {
    form.attributes.items.push({
        label: 'Новая характеристика',
        key: null,
        default: 0,
        min: 0,
        max: 10,
        player_editable: true,
        roll: { enabled: true, dice: 'd20' },
    });
};

const addSkill = () => {
    form.skills.items.push({
        label: 'Новый навык',
        key: null,
        default: false,
        player_editable: true,
        subskills: [],
    });
};

const addSubskill = (skill) => {
    skill.subskills ??= [];
    skill.subskills.push({
        label: 'Новый поднавык',
        key: null,
        default: false,
        player_editable: true,
    });
};

const addExtraField = () => {
    form.extra_fields.push({
        label: 'Новое поле',
        key: null,
        type: 'text',
        required: false,
        player_editable: true,
        default: '',
        min: 0,
        max: 10,
        max_length: 255,
        points_pool: null,
    });
};

const removeAt = (items, index) => {
    items.splice(index, 1);
};

const buildPayload = () => {
    const attributeKeys = new Set();
    const attributes = form.attributes.items.map((item, index) => ({
        ...clone(item),
        key: resolveKey(item.key, item.label, 'attribute', attributeKeys, index + 1),
        player_editable: normalizeBoolean(item.player_editable, true),
        roll: {
            enabled: normalizeBoolean(item.roll?.enabled, true),
            dice: item.roll?.dice ?? 'd20',
        },
    }));

    const skillKeys = new Set();
    const skills = form.skills.items.map((skill, index) => {
        const key = resolveKey(skill.key, skill.label, 'skill', skillKeys, index + 1);

        return {
            ...clone(skill),
            key,
            default: normalizeBoolean(skill.default),
            player_editable: normalizeBoolean(skill.player_editable, true),
            subskills: (skill.subskills ?? []).map((subskill, subIndex) => ({
                ...clone(subskill),
                key: resolveKey(
                    subskill.key,
                    `${skill.label || key}_${subskill.label || `subskill_${subIndex + 1}`}`,
                    `${key}_subskill`,
                    skillKeys,
                    subIndex + 1,
                ),
                default: normalizeBoolean(subskill.default),
                player_editable: normalizeBoolean(subskill.player_editable, true),
            })),
        };
    });

    const extraFieldKeys = new Set();
    const extra_fields = form.extra_fields.map((field, index) => ({
        ...clone(field),
        key: resolveKey(field.key, field.label, 'field', extraFieldKeys, index + 1),
        required: normalizeBoolean(field.required),
        player_editable: normalizeBoolean(field.player_editable, true),
    }));

    return {
        attributes: {
            points: Number(form.attributes.points ?? 0),
            items: attributes,
        },
        skills: {
            points: 0,
            items: skills,
        },
        points: clone(form.points ?? {}),
        extra_fields,
    };
};

const submit = () => {
    const payload = buildPayload();

    form.transform(() => payload).patch(route('games.character-template.update', props.game.id), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head :title="`Шаблон листа персонажа - ${game.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Редактор шаблона</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Соберите структуру листа так, как её будет видеть игрок во время игры.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Сохраняем...' : 'Сохранить шаблон' }}
                    </PrimaryButton>
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
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Сцена мастера</p>
                                        <h2 class="mt-3 text-3xl font-semibold text-amber-50">Настройка листа персонажа</h2>
                                        <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">Начните с базовых характеристик, затем добавьте навыки и сюжетные поля под ваш сеттинг.</p>
                                    </div>
                                    <div class="rounded-2xl border border-emerald-400/25 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                                        Можно оставить шаблон пустым и собрать с нуля
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur transition duration-300 hover:-translate-y-0.5 hover:bg-white/[0.08]">
                                    <p class="text-sm text-stone-400">Характеристики</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ form.attributes.items.length }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur transition duration-300 hover:-translate-y-0.5 hover:bg-white/[0.08]">
                                    <p class="text-sm text-stone-400">Навыки и поднавыки</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ totalSkillCount }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur transition duration-300 hover:-translate-y-0.5 hover:bg-white/[0.08]">
                                    <p class="text-sm text-stone-400">Активны по умолчанию</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ activeByDefaultCount }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur transition duration-300 hover:-translate-y-0.5 hover:bg-white/[0.08]">
                                    <p class="text-sm text-stone-400">Поля для бросков</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ rollEnabledCount }}</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Подсказка для старта</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">Для MVP обычно хватает 3-6 характеристик, списка ключевых навыков и нескольких сюжетных полей вроде происхождения, роли или заметок.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Практический совет</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">Если лист должен читаться быстро во время сессии, держите названия короткими, а числовые поля оставляйте только там, где они реально участвуют в бросках.</p>
                                <div
                                    v-if="page.props.flash.success"
                                    class="mt-4 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
                                >
                                    {{ page.props.flash.success }}
                                </div>
                            </article>
                        </div>
                    </div>
                </section>
                <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <section class="space-y-6">
                        <article class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Характеристики</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Числовая основа персонажа</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Задайте только те параметры, которые игрок будет использовать регулярно во время партии.</p>
                                </div>
                                <PrimaryButton type="button" @click="addAttribute">Добавить характеристику</PrimaryButton>
                            </div>

                            <div class="mt-5 grid gap-4 rounded-[1.4rem] border border-white/10 bg-white/[0.03] p-5 md:grid-cols-[minmax(0,1fr)_14rem] md:items-end">
                                <div>
                                    <p class="text-sm font-medium text-amber-50">Пул свободных очков</p>
                                    <p class="mt-1 text-sm leading-6 text-stone-400">Количество очков, которое игрок сможет распределить при создании персонажа.</p>
                                </div>
                                <div>
                                    <InputLabel for="attribute-points" value="Свободные очки" />
                                    <input id="attribute-points" v-model.number="form.attributes.points" type="number" min="0" class="fantasy-input mt-2 block w-full" />
                                    <InputError class="mt-2" :message="form.errors['attributes.points']" />
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <article
                                    v-for="(item, index) in form.attributes.items"
                                    :key="item.key || index"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/30 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="grid min-w-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1.4fr)_7rem_7rem_7rem]">
                                            <div>
                                                <InputLabel value="Название" />
                                                <TextInput v-model="item.label" class="mt-2 block w-full" />
                                                <InputError class="mt-2" :message="form.errors[`attributes.items.${index}.label`]" />
                                            </div>
                                            <div>
                                                <InputLabel value="Старт" />
                                                <input v-model.number="item.default" type="number" class="fantasy-input mt-2 block w-full" />
                                            </div>
                                            <div>
                                                <InputLabel value="Мин." />
                                                <input v-model.number="item.min" type="number" class="fantasy-input mt-2 block w-full" />
                                            </div>
                                            <div>
                                                <InputLabel value="Макс." />
                                                <input v-model.number="item.max" type="number" class="fantasy-input mt-2 block w-full" />
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20"
                                            @click="removeAt(form.attributes.items, index)"
                                        >
                                            Удалить
                                        </button>
                                    </div>

                                    <div class="mt-4 grid gap-4 rounded-[1.15rem] border border-stone-700/50 bg-stone-950/60 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8rem] md:items-end">
                                        <label class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-stone-200 transition hover:bg-white/[0.06]">
                                            <input v-model="item.roll.enabled" type="checkbox" class="rounded border-amber-300/20 bg-stone-950 text-amber-500" />
                                            Участвует в бросках
                                        </label>
                                        <label class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-stone-200 transition hover:bg-white/[0.06]">
                                            <input
                                                :checked="item.player_editable === false"
                                                type="checkbox"
                                                class="rounded border-amber-300/20 bg-stone-950 text-amber-500"
                                                @change="item.player_editable = !$event.target.checked"
                                            />
                                            Запретить игрокам редактирование
                                        </label>
                                        <div>
                                            <InputLabel value="Кубик" />
                                            <select v-model="item.roll.dice" class="fantasy-select mt-2 block w-full">
                                                <option v-for="dice in diceOptions" :key="dice" :value="dice">{{ dice }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </article>

                                <p v-if="!form.attributes.items.length" class="rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-3 text-sm text-stone-500">
                                    Добавьте хотя бы одну характеристику.
                                </p>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Дополнительно</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">История и сюжетные поля</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Используйте этот блок для происхождения, фракции, прозвища, цели персонажа и других полей, важных именно для вашего мира.</p>
                                </div>
                                <PrimaryButton type="button" @click="addExtraField">Добавить поле</PrimaryButton>
                            </div>

                            <div class="mt-5 space-y-4">
                                <article
                                    v-for="(field, index) in form.extra_fields"
                                    :key="field.key || index"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/30"
                                >
                                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_11rem_auto] lg:items-start">
                                        <div>
                                            <InputLabel value="Название поля" />
                                            <TextInput v-model="field.label" class="mt-2 block w-full" />
                                            <InputError class="mt-2" :message="form.errors[`extra_fields.${index}.label`]" />
                                        </div>
                                        <div>
                                            <InputLabel value="Тип поля" />
                                            <select v-model="field.type" class="fantasy-select mt-2 block w-full">
                                                <option v-for="type in fieldTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center justify-end pt-7">
                                            <button
                                                type="button"
                                                class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20"
                                                @click="removeAt(form.extra_fields, index)"
                                            >
                                                Удалить
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.35fr)_7rem_7rem] xl:items-end">
                                        <label class="min-w-0 rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-stone-300 transition hover:bg-white/[0.06]">
                                            <span class="flex min-w-0 items-center gap-2">
                                            <input v-model="field.required" type="checkbox" class="rounded border-amber-300/20 bg-stone-950 text-amber-500" />
                                            <span class="min-w-0 break-words">Обязательное поле</span>
                                            </span>
                                        </label>
                                        <label class="min-w-0 rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3 text-sm text-stone-300 transition hover:bg-white/[0.06]">
                                            <span class="flex min-w-0 items-center gap-2">
                                            <input
                                                :checked="field.player_editable === false"
                                                type="checkbox"
                                                class="rounded border-amber-300/20 bg-stone-950 text-amber-500"
                                                @change="field.player_editable = !$event.target.checked"
                                            />
                                            <span class="min-w-0 break-words">Запретить игрокам редактирование</span>
                                            </span>
                                        </label>
                                        <div class="min-w-0 md:col-span-2 xl:col-span-1">
                                            <InputLabel value="Значение по умолчанию" />
                                            <input
                                                v-if="field.type === 'number'"
                                                v-model.number="field.default"
                                                type="number"
                                                class="fantasy-input mt-2 block w-full"
                                            />
                                            <TextInput v-else v-model="field.default" class="mt-2 block w-full" />
                                        </div>
                                        <div v-if="field.type === 'number'" class="min-w-0">
                                            <InputLabel value="Мин." />
                                            <input v-model.number="field.min" type="number" class="fantasy-input mt-2 block w-full" />
                                        </div>
                                        <div v-if="field.type === 'number'" class="min-w-0">
                                            <InputLabel value="Макс." />
                                            <input v-model.number="field.max" type="number" class="fantasy-input mt-2 block w-full" />
                                        </div>
                                    </div>
                                </article>

                                <p v-if="!form.extra_fields.length" class="rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-3 text-sm text-stone-500">
                                    Дополнительные поля пока не добавлены.
                                </p>
                            </div>
                        </article>
                    </section>

                    <section class="space-y-6">
                        <article class="rounded-[1.75rem] border border-teal-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker text-teal-200/80">Навыки</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-teal-50">Навыки как on/off-состояния</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Оставляйте в списке только те навыки, по которым GM и игроки действительно будут быстро ориентироваться за столом.</p>
                                </div>
                                <PrimaryButton type="button" @click="addSkill">Добавить навык</PrimaryButton>
                            </div>

                            <div class="mt-5 space-y-4">
                                <article
                                    v-for="(skill, skillIndex) in form.skills.items"
                                    :key="skill.key || skillIndex"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-teal-300/30 hover:shadow-[0_0_40px_rgba(45,212,191,0.08)]"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="min-w-0 flex-1">
                                            <InputLabel value="Название навыка" />
                                            <TextInput v-model="skill.label" class="mt-2 block w-full" />
                                            <InputError class="mt-2" :message="form.errors[`skills.items.${skillIndex}.label`]" />
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 pt-7">
                                            <label class="inline-flex items-center gap-3 rounded-full border border-white/8 bg-white/[0.04] px-3 py-2 text-sm text-stone-200 transition hover:bg-white/[0.08]">
                                                <input v-model="skill.default" type="checkbox" class="peer sr-only" />
                                                <span class="relative h-6 w-11 rounded-full bg-stone-700/80 transition after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:bg-teal-500/70 peer-checked:after:translate-x-5" />
                                                Есть по умолчанию
                                            </label>
                                            <label class="inline-flex items-center gap-3 rounded-full border border-white/8 bg-white/[0.04] px-3 py-2 text-sm text-stone-200 transition hover:bg-white/[0.08]">
                                                <input
                                                    :checked="skill.player_editable === false"
                                                    type="checkbox"
                                                    class="rounded border-amber-300/20 bg-stone-950 text-amber-500"
                                                    @change="skill.player_editable = !$event.target.checked"
                                                />
                                                Запретить игрокам
                                            </label>
                                            <button
                                                type="button"
                                                class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20"
                                                @click="removeAt(form.skills.items, skillIndex)"
                                            >
                                                Удалить
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-[1.2rem] border border-stone-700/50 bg-stone-950/60 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-teal-50">Поднавыки</p>
                                                <p class="mt-1 text-xs text-stone-400">Добавляйте их только там, где они реально нужны в системе.</p>
                                            </div>
                                            <SecondaryButton type="button" @click="addSubskill(skill)">Добавить поднавык</SecondaryButton>
                                        </div>

                                        <div v-if="skill.subskills?.length" class="mt-4 space-y-3">
                                            <div
                                                v-for="(subskill, subIndex) in skill.subskills"
                                                :key="subskill.key || subIndex"
                                                class="flex flex-wrap items-center gap-3 rounded-2xl border border-stone-700/50 bg-stone-900/75 px-4 py-3"
                                            >
                                                <div class="min-w-[14rem] flex-1">
                                                    <TextInput v-model="subskill.label" class="block w-full" placeholder="Название поднавыка" />
                                                    <InputError class="mt-2" :message="form.errors[`skills.items.${skillIndex}.subskills.${subIndex}.label`]" />
                                                </div>
                                                <label class="inline-flex items-center gap-3 rounded-full border border-white/8 bg-white/[0.04] px-3 py-2 text-sm text-stone-200 transition hover:bg-white/[0.08]">
                                                    <input v-model="subskill.default" type="checkbox" class="peer sr-only" />
                                                    <span class="relative h-6 w-11 rounded-full bg-stone-700/80 transition after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:bg-teal-500/70 peer-checked:after:translate-x-5" />
                                                    Активен по умолчанию
                                                </label>
                                                <label class="inline-flex items-center gap-3 rounded-full border border-white/8 bg-white/[0.04] px-3 py-2 text-sm text-stone-200 transition hover:bg-white/[0.08]">
                                                    <input
                                                        :checked="subskill.player_editable === false"
                                                        type="checkbox"
                                                        class="rounded border-amber-300/20 bg-stone-950 text-amber-500"
                                                        @change="subskill.player_editable = !$event.target.checked"
                                                    />
                                                    Запретить игрокам
                                                </label>
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-rose-400/25 bg-rose-400/10 px-3 py-2 text-sm font-medium text-rose-200 transition hover:bg-rose-400/20"
                                                    @click="removeAt(skill.subskills, subIndex)"
                                                >
                                                    Удалить
                                                </button>
                                            </div>
                                        </div>

                                        <p v-else class="mt-4 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-3 text-sm text-stone-500">
                                            Пока без поднавыков.
                                        </p>
                                    </div>
                                </article>

                                <p v-if="!form.skills.items.length" class="rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-3 text-sm text-stone-500">
                                    Добавьте навыки, если они нужны в вашей системе.
                                </p>
                            </div>
                        </article>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
