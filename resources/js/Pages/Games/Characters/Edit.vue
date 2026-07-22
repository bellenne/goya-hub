<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { attributePointDelta, calculateAttributePointBalance } from '@/Composables/useAttributePointBalance';
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

const attributePointBalance = computed(() => calculateAttributePointBalance(
    form.attribute_values,
    props.template.attributes.items,
    props.template.attributes.points,
));
const attributeDelta = (item) => attributePointDelta(form.attribute_values, item);
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
    if (attributePointBalance.value.available < 0) {
        return;
    }

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

    <GameThemeLayout :game="game" section="Мой персонаж" :title="game.name">
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
                    <PrimaryButton :disabled="form.processing || attributePointBalance.available < 0" @click="submit">
                        {{ form.processing ? 'Сохраняем...' : 'Сохранить персонажа' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="fantasy-kicker">{{ character ? 'Редактирование листа' : 'Создание листа' }}</p>
                            <h2 class="theme-panel-title mt-2">Персонаж партии</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                                {{ back_to_session ? `Лист для сессии «${back_to_session.title}».` : 'Основные данные, характеристики, навыки и дополнительные поля.' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="gm-badge">{{ template.attributes.items.length }} характеристик</span>
                            <span class="gm-badge">{{ enabledSkillCount }} / {{ skillCount }} навыков</span>
                            <span class="gm-badge">{{ template.extra_fields.length }} полей</span>
                            <span class="gm-badge">{{ character ? 'Лист существует' : 'Новый лист' }}</span>
                        </div>
                    </div>
                    <div v-if="page.props.flash.success" class="gm-alert gm-alert-success mt-5">
                        {{ page.props.flash.success }}
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.45fr_0.75fr]">
                    <div class="space-y-6">
                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Основа</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Личность персонажа</h2>
                                </div>
                                <p class="gm-badge">
                                    Имя, происхождение, портрет и заметки
                                </p>
                            </div>

                            <div class="mt-6 grid gap-6 lg:grid-cols-[0.82fr_1.18fr]">
                                <div class="theme-card">
                                    <p class="text-sm font-medium text-stone-300">Портрет</p>
                                    <div class="mt-4 flex justify-center">
                                        <div
                                            class="flex h-56 w-full max-w-[18rem] items-center justify-center overflow-hidden theme-media border-dashed"
                                        >
                                            <img
                                                v-if="avatarPreviewUrl"
                                                :src="avatarPreviewUrl"
                                                alt="Портрет персонажа"
                                                class="h-full w-full object-contain"
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
                                            class="fantasy-file mt-2 block w-full"
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
                                            class="fantasy-textarea mt-2 block min-h-40 w-full"
                                            placeholder="Кратко опишите прошлое, мотивацию, связи или особенности героя."
                                        />
                                        <InputError class="mt-2" :message="form.errors.notes" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Характеристики</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Основные параметры</h2>
                                </div>
                                <div class="gm-badge">
                                    Осталось {{ attributePointBalance.available }} очков
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 md:grid-cols-4">
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Базовые очки</div>
                                    <div class="mt-2 text-xl font-semibold text-amber-50">{{ attributePointBalance.base }}</div>
                                </div>
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-emerald-200/70">Возвращено</div>
                                    <div class="mt-2 text-xl font-semibold text-emerald-100">+{{ attributePointBalance.gained }}</div>
                                </div>
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-rose-200/70">Потрачено</div>
                                    <div class="mt-2 text-xl font-semibold text-rose-100">-{{ attributePointBalance.spent }}</div>
                                </div>
                                <div
                                    class="theme-list-row"
                                    :class="attributePointBalance.available < 0 ? 'border-rose-400/35 bg-rose-500/15' : 'border-amber-300/20 bg-amber-300/10'"
                                >
                                    <div class="text-xs uppercase tracking-[0.18em]" :class="attributePointBalance.available < 0 ? 'text-rose-200/80' : 'text-amber-200/70'">Осталось</div>
                                    <div class="mt-2 text-xl font-semibold" :class="attributePointBalance.available < 0 ? 'text-rose-100' : 'text-amber-50'">{{ attributePointBalance.available }}</div>
                                </div>
                            </div>

                            <div v-if="fieldError('attribute_values')" class="theme-empty mt-5 text-rose-200">
                                {{ fieldError('attribute_values') }}
                            </div>
                            <div v-else-if="attributePointBalance.available < 0" class="theme-empty mt-5 text-rose-200">
                                Баланс характеристик отрицательный. Понизьте часть значений или верните очки, чтобы сохранить персонажа.
                            </div>

                            <div v-if="template.attributes.items.length === 0" class="mt-6 theme-empty">
                                В шаблоне пока нет характеристик.
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
                                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">
                                                База {{ item.default ?? 0 }} · пределы {{ item.min ?? '-' }} / {{ item.max ?? '-' }}
                                                <span v-if="item.player_editable === false"> · только мастер</span>
                                            </p>
                                        </div>
                                        <div class="gm-badge">
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
                                            class="fantasy-input block w-full text-lg font-semibold"
                                        />
                                        <p class="text-sm leading-6 text-stone-400">
                                            <span v-if="item.player_editable === false">Это значение может менять только мастер.</span>
                                            <span v-else>Настройте значение для этого персонажа. Модификатор рассчитывается автоматически по параметрам шаблона.</span>
                                        </p>
                                    </div>
                                    <p
                                        class="mt-3 text-xs font-semibold uppercase tracking-[0.18em]"
                                        :class="attributeDelta(item) < 0 ? 'text-emerald-300' : attributeDelta(item) > 0 ? 'text-rose-300' : 'text-stone-500'"
                                    >
                                        <template v-if="attributeDelta(item) < 0">Возвращает +{{ Math.abs(attributeDelta(item)) }} оч.</template>
                                        <template v-else-if="attributeDelta(item) > 0">Тратит -{{ attributeDelta(item) }} оч.</template>
                                        <template v-else>На базовом значении</template>
                                    </p>

                                    <InputError class="mt-3" :message="fieldError(`attribute_values.${item.key}`)" />
                                </article>
                            </div>
                        </section>

                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Навыки</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Владение умениями</h2>
                                </div>
                                <div class="gm-badge">
                                    Активно {{ enabledSkillCount }} из {{ skillCount }}
                                </div>
                            </div>

                            <div v-if="skillGroups.length === 0" class="mt-6 theme-empty">
                                В шаблоне пока нет навыков.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="skill in skillGroups"
                                    :key="skill.key"
                                    class="theme-card theme-card-interactive"
                                >
                                    <label class="flex cursor-pointer items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-teal-50">{{ skill.label }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-stone-400">
                                                <span v-if="skill.player_editable === false">Этот навык может менять только мастер.</span>
                                                <span v-else>Включите навык, если персонаж действительно владеет этим направлением.</span>
                                            </p>
                                        </div>
                                        <div
                                            class="gm-badge inline-flex items-center gap-3 py-2 transition"
                                            :class="form.skill_values[skill.key]
                                                ? 'gm-badge-success'
                                                : 'gm-badge-muted'"
                                        >
                                            <input
                                                :id="`skill-${skill.key}`"
                                                v-model="form.skill_values[skill.key]"
                                                type="checkbox"
                                                :disabled="skill.player_editable === false"
                                                class="h-4 w-4 rounded border-amber-300/30 bg-transparent text-emerald-400 focus:ring-emerald-300/40"
                                            />
                                            {{ form.skill_values[skill.key] ? 'Есть' : 'Нет' }}
                                        </div>
                                    </label>
                                    <InputError class="mt-3" :message="fieldError(`skill_values.${skill.key}`)" />

                                    <div v-if="skill.subskills?.length" class="mt-5 space-y-3">
                                        <label
                                            v-for="subskill in skill.subskills"
                                            :key="subskill.key"
                                            class="theme-list-row flex cursor-pointer items-center justify-between gap-4"
                                        >
                                            <div>
                                                <div class="font-medium text-stone-100">{{ subskill.label }}</div>
                                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">
                                                    Поднавык<span v-if="subskill.player_editable === false"> · только мастер</span>
                                                </div>
                                            </div>
                                            <div
                                                class="gm-badge inline-flex items-center gap-3 py-2 transition"
                                                :class="form.skill_values[subskill.key]
                                                    ? 'gm-badge-success'
                                                    : 'gm-badge-muted'"
                                            >
                                                <input
                                                    :id="`subskill-${subskill.key}`"
                                                    v-model="form.skill_values[subskill.key]"
                                                    type="checkbox"
                                                    :disabled="subskill.player_editable === false"
                                                    class="h-4 w-4 rounded border-amber-300/30 bg-transparent text-emerald-400 focus:ring-emerald-300/40"
                                                />
                                                {{ form.skill_values[subskill.key] ? 'Есть' : 'Нет' }}
                                            </div>
                                        </label>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="theme-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Дополнительно</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Поля шаблона</h2>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(limit, poolKey) in template.points ?? {}"
                                        :key="poolKey"
                                        class="gm-badge"
                                    >
                                        {{ poolKey }}: {{ extraPoolsSpent[poolKey] ?? 0 }} / {{ limit }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="fieldError('extra_field_values')" class="theme-empty mt-5 text-rose-200">
                                {{ fieldError('extra_field_values') }}
                            </div>

                            <div v-if="template.extra_fields.length === 0" class="mt-6 theme-empty">
                                Дополнительные поля в шаблоне пока не настроены.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="item in template.extra_fields"
                                    :key="item.key"
                                    class="theme-card theme-card-interactive"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-semibold text-amber-50">{{ item.label }}</h3>
                                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">
                                                {{ item.type === 'number' ? 'Числовое поле' : 'Текстовое поле' }}
                                                <span v-if="item.required"> · обязательно</span>
                                                <span v-if="item.type === 'number' && item.points_pool"> · пул {{ item.points_pool }}</span>
                                                <span v-if="item.player_editable === false"> · только мастер</span>
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
                                            class="fantasy-input block w-full text-lg font-semibold"
                                        />
                                        <textarea
                                            v-else
                                            :id="`extra-${item.key}`"
                                            v-model="form.extra_field_values[item.key]"
                                            :disabled="item.player_editable === false"
                                            class="fantasy-textarea block min-h-32 w-full"
                                        />
                                    </div>
                                    <p v-if="item.player_editable === false" class="mt-3 text-xs uppercase tracking-[0.18em] text-stone-500">
                                        Это поле редактируется только мастером.
                                    </p>

                                    <InputError class="mt-3" :message="fieldError(`extra_field_values.${item.key}`)" />
                                </article>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                        <section class="theme-panel">
                            <p class="fantasy-kicker">Быстрый контроль</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Перед сохранением</h2>
                            <div class="mt-5 space-y-3 text-sm leading-6 text-stone-300">
                                <div class="theme-list-row">
                                    Проверьте имя и происхождение, чтобы персонажа было легко отличать в игре.
                                </div>
                                <div class="theme-list-row">
                                    Не превышайте лимит свободных очков у характеристик и числовых доп. полей.
                                </div>
                                <div class="theme-list-row">
                                    Включайте только те навыки, которыми герой реально владеет по задумке.
                                </div>
                            </div>
                        </section>

                        <section class="theme-panel">
                            <p class="fantasy-kicker">Сводка</p>
                            <h2 class="mt-2 text-2xl font-semibold text-teal-50">Текущее состояние</h2>
                            <div class="mt-5 space-y-4">
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Имя</div>
                                    <div class="mt-2 text-base font-semibold text-stone-100">{{ form.name || 'Не указано' }}</div>
                                </div>
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Происхождение</div>
                                    <div class="mt-2 text-base font-semibold text-stone-100">{{ form.origin || 'Не указано' }}</div>
                                </div>
                                <div class="theme-list-row">
                                    <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Навыки</div>
                                    <div class="mt-2 text-base font-semibold text-stone-100">{{ enabledSkillCount }} активных</div>
                                </div>
                            </div>
                            <PrimaryButton class="mt-6 w-full" :disabled="form.processing || attributePointBalance.available < 0" @click="submit">
                                {{ form.processing ? 'Сохраняем...' : 'Сохранить персонажа' }}
                            </PrimaryButton>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </GameThemeLayout>
</template>
