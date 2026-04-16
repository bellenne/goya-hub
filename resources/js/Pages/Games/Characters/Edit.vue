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
    character: { type: Object, default: null },
    back_to_session: { type: Object, default: null },
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

const buildSectionValues = (items, existingValues = {}, type = 'default') =>
    items.reduce((acc, item) => {
        if (type === 'skill') {
            acc[item.key] = normalizeBoolean(existingValues[item.key], normalizeBoolean(item.default));
            return acc;
        }

        acc[item.key] = existingValues[item.key] ?? item.default ?? (item.type === 'number' ? 0 : '');
        return acc;
    }, {});

const skillGroups = computed(() => props.template.skills.items ?? []);
const flatSkillItems = computed(() => skillGroups.value.flatMap((skill) => [skill, ...(skill.subskills ?? [])]));

const form = useForm({
    name: props.character?.name ?? '',
    avatar: null,
    origin: props.character?.origin ?? '',
    notes: props.character?.notes ?? '',
    attribute_values: buildSectionValues(props.template.attributes.items, props.character?.attribute_values),
    skill_values: buildSectionValues(flatSkillItems.value, props.character?.skill_values, 'skill'),
    extra_field_values: buildSectionValues(props.template.extra_fields, props.character?.extra_field_values),
    back_to_session_id: props.back_to_session?.id ?? null,
});

const spentPoints = (values, items) => items.reduce((sum, item) => {
    const value = Number(values[item.key] ?? item.default ?? 0);
    const baseline = Number(item.default ?? 0);
    return sum + Math.max(0, value - baseline);
}, 0);

const attributesSpent = computed(() => spentPoints(form.attribute_values, props.template.attributes.items));
const extraPoolsSpent = computed(() => {
    const result = {};

    Object.keys(props.template.points ?? {}).forEach((poolKey) => {
        const items = props.template.extra_fields.filter((item) => item.type === 'number' && item.points_pool === poolKey);
        result[poolKey] = spentPoints(form.extra_field_values, items);
    });

    return result;
});

const skillCount = computed(() => flatSkillItems.value.length);
const enabledSkillCount = computed(() => flatSkillItems.value.filter((item) => form.skill_values[item.key]).length);
const avatarPreviewUrl = computed(() => {
    if (form.avatar instanceof File) {
        return URL.createObjectURL(form.avatar);
    }

    return props.character?.avatar_url ?? null;
});

const submit = () => {
    form.post(route('games.character.upsert', props.game.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
    });
};

const setAvatar = (event) => {
    form.avatar = event.target.files[0] ?? null;
};

const fieldError = (path) => form.errors[path];

const modifierPreview = (item, value) => {
    if (!item.roll?.enabled) {
        return 'Без модификатора';
    }

    const modifier = Number(value ?? item.default ?? 0);

    return `${item.roll.dice} ${modifier >= 0 ? '+' : ''}${modifier}`;
};
</script>

<template>
    <Head :title="`${character ? 'Редактирование' : 'Создание'} персонажа`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Персонаж</p>
                    <h1 class="fantasy-title text-3xl">{{ character ? 'Редактирование листа' : 'Создание листа' }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        {{ back_to_session ? `Подготовьте персонажа для сессии «${back_to_session.title}».` : 'Заполните лист персонажа перед игрой.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="back_to_session ? route('games.sessions.show', [game.id, back_to_session.id]) : route('games.show', game.id)">
                        <SecondaryButton>{{ back_to_session ? 'Вернуться к сессии' : 'Вернуться к игре' }}</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Сохраняем...' : 'Сохранить персонажа' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-4 lg:grid-cols-4">
                        <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                            <p class="text-sm text-stone-400">Характеристики</p>
                            <p class="mt-2 text-3xl font-semibold text-white">{{ template.attributes.items.length }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">
                                Потрачено {{ attributesSpent }} из {{ template.attributes.points }}
                            </p>
                        </article>
                        <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                            <p class="text-sm text-stone-400">Навыки</p>
                            <p class="mt-2 text-3xl font-semibold text-white">{{ enabledSkillCount }} / {{ skillCount }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">Активные умения персонажа</p>
                        </article>
                        <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                            <p class="text-sm text-stone-400">Доп. поля</p>
                            <p class="mt-2 text-3xl font-semibold text-white">{{ template.extra_fields.length }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">Биография, ресурсы и заметки</p>
                        </article>
                        <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                            <p class="text-sm text-stone-400">Статус</p>
                            <p class="mt-2 text-lg font-semibold text-amber-50">{{ character ? 'Лист существует' : 'Новый лист' }}</p>
                            <div
                                v-if="page.props.flash.success"
                                class="mt-3 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-sm text-emerald-200"
                            >
                                {{ page.props.flash.success }}
                            </div>
                        </article>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.45fr_0.75fr]">
                    <div class="space-y-6">
                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Основа</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Личность персонажа</h2>
                                </div>
                                <p class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-xs uppercase tracking-[0.18em] text-stone-400">
                                    Имя, происхождение, портрет и заметки
                                </p>
                            </div>

                            <div class="mt-6 grid gap-6 lg:grid-cols-[0.82fr_1.18fr]">
                                <div class="rounded-[1.4rem] border border-stone-700/60 bg-stone-900/75 p-5">
                                    <p class="text-sm font-medium text-stone-300">Портрет</p>
                                    <div class="mt-4 flex justify-center">
                                        <div
                                            class="flex h-56 w-full max-w-[18rem] items-center justify-center overflow-hidden rounded-[1.6rem] border border-dashed border-stone-600/50 bg-[radial-gradient(circle_at_top,rgba(245,158,11,0.12),transparent_55%),rgba(10,10,9,0.88)]"
                                        >
                                            <img
                                                v-if="avatarPreviewUrl"
                                                :src="avatarPreviewUrl"
                                                alt="Портрет персонажа"
                                                class="h-full w-full object-cover"
                                            />
                                            <div v-else class="px-6 text-center text-xs uppercase tracking-[0.24em] text-stone-500">
                                                Портрет не выбран
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="avatar" value="Изображение" />
                                        <input
                                            id="avatar"
                                            type="file"
                                            accept="image/*"
                                            class="mt-2 block w-full rounded-2xl border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 file:mr-4 file:rounded-xl file:border-0 file:bg-amber-500/15 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-amber-100"
                                            @change="setAvatar"
                                        />
                                        <InputError class="mt-2" :message="form.errors.avatar" />
                                    </div>
                                </div>

                                <div class="grid gap-5">
                                    <div>
                                        <InputLabel for="name" value="Имя персонажа" />
                                        <TextInput id="name" v-model="form.name" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.name" />
                                    </div>

                                    <div>
                                        <InputLabel for="origin" value="Происхождение" />
                                        <TextInput id="origin" v-model="form.origin" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.origin" />
                                    </div>

                                    <div>
                                        <InputLabel for="notes" value="Заметки" />
                                        <textarea
                                            id="notes"
                                            v-model="form.notes"
                                            class="mt-2 block min-h-40 w-full rounded-[1.25rem] border border-white/10 bg-stone-950/90 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30"
                                            placeholder="Кратко опишите прошлое, мотивацию, связи или особенности героя."
                                        />
                                        <InputError class="mt-2" :message="form.errors.notes" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Характеристики</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Основные параметры</h2>
                                </div>
                                <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-stone-300">
                                    {{ attributesSpent }} / {{ template.attributes.points }} свободных очков использовано
                                </div>
                            </div>

                            <div v-if="fieldError('attribute_values')" class="mt-5 rounded-2xl border border-rose-400/25 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                                {{ fieldError('attribute_values') }}
                            </div>

                            <div v-if="template.attributes.items.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                В шаблоне пока нет характеристик.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="item in template.attributes.items"
                                    :key="item.key"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/25 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-50">{{ item.label }}</h3>
                                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">
                                                База {{ item.default ?? 0 }} · пределы {{ item.min ?? '-' }} / {{ item.max ?? '-' }}
                                                <span v-if="item.player_editable === false"> · только GM</span>
                                            </p>
                                        </div>
                                        <div class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-stone-300">
                                            {{ modifierPreview(item, form.attribute_values[item.key]) }}
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-3 sm:grid-cols-[140px_1fr] sm:items-center">
                                        <input
                                            :id="`attribute-${item.key}`"
                                            v-model.number="form.attribute_values[item.key]"
                                            type="number"
                                            :min="item.min ?? undefined"
                                            :max="item.max ?? undefined"
                                            :disabled="item.player_editable === false"
                                            class="block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-lg font-semibold text-white shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30"
                                        />
                                        <p class="text-sm leading-6 text-stone-400">
                                            <span v-if="item.player_editable === false">Это значение может менять только GM.</span>
                                            <span v-else>Настройте значение для этого персонажа. Модификатор рассчитывается автоматически по параметрам шаблона.</span>
                                        </p>
                                    </div>

                                    <InputError class="mt-3" :message="fieldError(`attribute_values.${item.key}`)" />
                                </article>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Навыки</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Владение умениями</h2>
                                </div>
                                <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-stone-300">
                                    Активно {{ enabledSkillCount }} из {{ skillCount }}
                                </div>
                            </div>

                            <div v-if="skillGroups.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                В шаблоне пока нет навыков.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="skill in skillGroups"
                                    :key="skill.key"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-teal-300/25 hover:shadow-[0_0_40px_rgba(45,212,191,0.08)]"
                                >
                                    <label class="flex cursor-pointer items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-teal-50">{{ skill.label }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-stone-400">
                                                <span v-if="skill.player_editable === false">Этот навык может менять только GM.</span>
                                                <span v-else>Включите навык, если персонаж действительно владеет этим направлением.</span>
                                            </p>
                                        </div>
                                        <div
                                            class="inline-flex items-center gap-3 rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition"
                                            :class="form.skill_values[skill.key]
                                                ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200'
                                                : 'border-stone-600/40 bg-stone-950/80 text-stone-400'"
                                        >
                                            <input
                                                :id="`skill-${skill.key}`"
                                                v-model="form.skill_values[skill.key]"
                                                type="checkbox"
                                                :disabled="skill.player_editable === false"
                                                class="h-4 w-4 rounded border-stone-500 bg-stone-950 text-emerald-400 focus:ring-emerald-300/40"
                                            />
                                            {{ form.skill_values[skill.key] ? 'Есть' : 'Нет' }}
                                        </div>
                                    </label>
                                    <InputError class="mt-3" :message="fieldError(`skill_values.${skill.key}`)" />

                                    <div v-if="skill.subskills?.length" class="mt-5 space-y-3">
                                        <label
                                            v-for="subskill in skill.subskills"
                                            :key="subskill.key"
                                            class="flex cursor-pointer items-center justify-between gap-4 rounded-[1.15rem] border border-white/10 bg-stone-950/75 px-4 py-3 transition hover:border-white/15"
                                        >
                                            <div>
                                                <div class="font-medium text-stone-100">{{ subskill.label }}</div>
                                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">
                                                    Поднавык<span v-if="subskill.player_editable === false"> · только GM</span>
                                                </div>
                                            </div>
                                            <div
                                                class="inline-flex items-center gap-3 rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition"
                                                :class="form.skill_values[subskill.key]
                                                    ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200'
                                                    : 'border-stone-600/40 bg-stone-900/80 text-stone-400'"
                                            >
                                                <input
                                                    :id="`subskill-${subskill.key}`"
                                                    v-model="form.skill_values[subskill.key]"
                                                    type="checkbox"
                                                    :disabled="subskill.player_editable === false"
                                                    class="h-4 w-4 rounded border-stone-500 bg-stone-950 text-emerald-400 focus:ring-emerald-300/40"
                                                />
                                                {{ form.skill_values[subskill.key] ? 'Есть' : 'Нет' }}
                                            </div>
                                        </label>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Дополнительно</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Поля шаблона</h2>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(limit, poolKey) in template.points ?? {}"
                                        :key="poolKey"
                                        class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-stone-300"
                                    >
                                        {{ poolKey }}: {{ extraPoolsSpent[poolKey] ?? 0 }} / {{ limit }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="fieldError('extra_field_values')" class="mt-5 rounded-2xl border border-rose-400/25 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                                {{ fieldError('extra_field_values') }}
                            </div>

                            <div v-if="template.extra_fields.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                Дополнительные поля в шаблоне пока не настроены.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="item in template.extra_fields"
                                    :key="item.key"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/25 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-50">{{ item.label }}</h3>
                                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">
                                                {{ item.type === 'number' ? 'Числовое поле' : 'Текстовое поле' }}
                                                <span v-if="item.required"> · обязательно</span>
                                                <span v-if="item.type === 'number' && item.points_pool"> · пул {{ item.points_pool }}</span>
                                                <span v-if="item.player_editable === false"> · только GM</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <input
                                            v-if="item.type === 'number'"
                                            :id="`extra-${item.key}`"
                                            v-model.number="form.extra_field_values[item.key]"
                                            type="number"
                                            :min="item.min ?? undefined"
                                            :max="item.max ?? undefined"
                                            :disabled="item.player_editable === false"
                                            class="block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-lg font-semibold text-white shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30"
                                        />
                                        <textarea
                                            v-else
                                            :id="`extra-${item.key}`"
                                            v-model="form.extra_field_values[item.key]"
                                            :disabled="item.player_editable === false"
                                            class="block min-h-32 w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30"
                                        />
                                    </div>
                                    <p v-if="item.player_editable === false" class="mt-3 text-xs uppercase tracking-[0.18em] text-stone-500">
                                        Это поле редактируется только GM.
                                    </p>

                                    <InputError class="mt-3" :message="fieldError(`extra_field_values.${item.key}`)" />
                                </article>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <p class="fantasy-kicker">Быстрый контроль</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Перед сохранением</h2>
                            <div class="mt-5 space-y-3 text-sm leading-6 text-stone-300">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    Проверьте имя и происхождение, чтобы персонажа было легко отличать в игре.
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    Не превышайте лимит свободных очков у характеристик и числовых доп. полей.
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    Включайте только те навыки, которыми герой реально владеет по задумке.
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-teal-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <p class="fantasy-kicker">Сводка</p>
                            <h2 class="mt-2 text-2xl font-semibold text-teal-50">Текущее состояние</h2>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Имя</div>
                                    <div class="mt-2 text-base font-semibold text-stone-100">{{ form.name || 'Не указано' }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Происхождение</div>
                                    <div class="mt-2 text-base font-semibold text-stone-100">{{ form.origin || 'Не указано' }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Навыки</div>
                                    <div class="mt-2 text-base font-semibold text-stone-100">{{ enabledSkillCount }} активных</div>
                                </div>
                            </div>
                            <PrimaryButton class="mt-6 w-full" :disabled="form.processing" @click="submit">
                                {{ form.processing ? 'Сохраняем...' : 'Сохранить персонажа' }}
                            </PrimaryButton>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
