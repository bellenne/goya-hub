<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
const enabledSkillCount = computed(() => flatSkillItems.value.filter((item) => props.character.skill_values?.[item.key] ?? item.default ?? false).length);
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

const skillValueLabel = (item) => (props.character.skill_values?.[item.key] ?? item.default ?? false) ? 'Есть' : 'Нет';
const fieldValue = (item) => props.character.extra_field_values?.[item.key] ?? '—';
const modifierPreview = (item) => {
    if (!item.roll?.enabled) {
        return 'Без броска';
    }

    const value = Number(props.character.attribute_values?.[item.key] ?? item.default ?? 0);
    const base = Number(item.default ?? 0);
    const step = Number(item.roll.modifier_step || 1);
    const modifier = Math.trunc((value - base) / step);

    return `${item.roll.dice} ${modifier >= 0 ? '+' : ''}${modifier}`;
};
</script>

<template>
    <Head :title="character.name" />

    <AuthenticatedLayout>
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

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                        <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
                            <div class="flex justify-center lg:justify-start">
                                <div class="flex h-56 w-56 items-center justify-center overflow-hidden rounded-[1.9rem] border border-white/10 bg-[radial-gradient(circle_at_top,rgba(245,158,11,0.14),transparent_55%),rgba(10,10,9,0.88)] shadow-[0_18px_50px_rgba(0,0,0,0.35)]">
                                    <img v-if="character.avatar_url" :src="character.avatar_url" alt="Портрет персонажа" class="h-full w-full object-cover" />
                                    <div v-else class="px-6 text-center text-xs uppercase tracking-[0.24em] text-stone-500">
                                        Портрет не задан
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="rounded-[1.5rem] border border-white/10 bg-stone-950/45 p-5 backdrop-blur">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Игровой лист</p>
                                    <h2 class="mt-3 text-3xl font-semibold text-amber-50">{{ character.name }}</h2>
                                    <p v-if="character.origin" class="mt-3 text-sm leading-6 text-stone-300">{{ character.origin }}</p>
                                    <p v-else class="mt-3 text-sm leading-6 text-stone-500">Происхождение не указано.</p>
                                    <div class="mt-4 grid gap-3 text-sm text-stone-300 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                            <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Игрок</div>
                                            <div class="mt-2 font-semibold text-stone-100">{{ character.user?.name ?? 'Не указан' }}</div>
                                        </div>
                                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                            <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Контакт</div>
                                            <div class="mt-2 font-semibold text-stone-100">{{ character.user?.email ?? 'Не указан' }}</div>
                                        </div>
                                    </div>
                                    <div v-if="character.notes" class="mt-4 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-4 text-sm leading-7 text-stone-300">
                                        {{ character.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                                <p class="text-sm text-stone-400">Характеристики</p>
                                <p class="mt-2 text-3xl font-semibold text-white">{{ attributeCount }}</p>
                            </article>
                            <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                                <p class="text-sm text-stone-400">Навыки</p>
                                <p class="mt-2 text-3xl font-semibold text-white">{{ enabledSkillCount }} / {{ flatSkillItems.length }}</p>
                            </article>
                            <article class="rounded-[1.4rem] border border-white/10 bg-white/[0.05] p-5 backdrop-blur">
                                <p class="text-sm text-stone-400">Инвентарь</p>
                                <p class="mt-2 text-3xl font-semibold text-white">{{ inventoryCount }}</p>
                            </article>
                            <div
                                v-if="page.props.flash.success"
                                class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
                            >
                                {{ page.props.flash.success }}
                            </div>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div class="space-y-6">
                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Характеристики</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Основные параметры</h2>
                                </div>
                            </div>

                            <div v-if="template.attributes.items.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                У персонажа пока нет характеристик по шаблону.
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
                                            <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">{{ modifierPreview(item) }}</p>
                                        </div>
                                        <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-lg font-semibold text-white">
                                            {{ character.attribute_values[item.key] ?? item.default ?? 0 }}
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Навыки</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Подготовка персонажа</h2>
                                </div>
                                <div class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-stone-300">
                                    Активно {{ enabledSkillCount }} из {{ flatSkillItems.length }}
                                </div>
                            </div>

                            <div v-if="skillGroups.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                Навыки в шаблоне пока не заданы.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="skill in skillGroups"
                                    :key="skill.key"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-teal-300/25 hover:shadow-[0_0_40px_rgba(45,212,191,0.08)]"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-teal-50">{{ skill.label }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-stone-400">Основной навык персонажа.</p>
                                        </div>
                                        <span
                                            class="rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em]"
                                            :class="skillValueLabel(skill) === 'Есть' ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200' : 'border-stone-600/40 bg-stone-950/70 text-stone-400'"
                                        >
                                            {{ skillValueLabel(skill) }}
                                        </span>
                                    </div>

                                    <div v-if="skill.subskills?.length" class="mt-5 space-y-3">
                                        <div
                                            v-for="subskill in skill.subskills"
                                            :key="subskill.key"
                                            class="flex items-center justify-between rounded-[1.15rem] border border-white/10 bg-stone-950/75 px-4 py-3"
                                        >
                                            <div>
                                                <div class="font-medium text-stone-100">{{ subskill.label }}</div>
                                                <div class="text-xs uppercase tracking-[0.18em] text-stone-500">Поднавык</div>
                                            </div>
                                            <span
                                                class="rounded-full border px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em]"
                                                :class="skillValueLabel(subskill) === 'Есть' ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-200' : 'border-stone-600/40 bg-stone-900/70 text-stone-400'"
                                            >
                                                {{ skillValueLabel(subskill) }}
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Дополнительно</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Поля листа</h2>
                                </div>
                            </div>

                            <div v-if="template.extra_fields.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                Дополнительные поля для этого листа не настроены.
                            </div>

                            <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
                                <article
                                    v-for="item in template.extra_fields"
                                    :key="item.key"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/25 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]"
                                >
                                    <div class="text-sm uppercase tracking-[0.18em] text-stone-500">{{ item.label }}</div>
                                    <div class="mt-3 whitespace-pre-wrap text-base leading-7 text-stone-100">{{ fieldValue(item) }}</div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <div class="space-y-6">
                        <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="fantasy-kicker">Инвентарь</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Предметы персонажа</h2>
                                </div>
                            </div>

                            <div v-if="can_manage_inventory" class="mt-6 grid gap-4">
                                <form class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5" @submit.prevent="submitCatalog">
                                    <h3 class="text-lg font-semibold text-stone-100">Выдать предмет из каталога</h3>
                                    <div class="mt-4">
                                        <InputLabel for="catalog-item" value="Предмет" />
                                        <select id="catalog-item" v-model="catalogForm.item_id" class="mt-2 block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30">
                                            <option value="">Выберите предмет</option>
                                            <option v-for="item in catalogItems" :key="item.id" :value="item.id">
                                                {{ item.name }}{{ item.category ? ` (${item.category})` : '' }}
                                            </option>
                                        </select>
                                        <InputError class="mt-2" :message="catalogForm.errors.item_id" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="catalog-quantity" value="Количество" />
                                        <input id="catalog-quantity" v-model.number="catalogForm.quantity" type="number" min="1" class="mt-2 block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30" />
                                        <InputError class="mt-2" :message="catalogForm.errors.quantity" />
                                    </div>
                                    <PrimaryButton class="mt-4" :disabled="catalogForm.processing">Выдать предмет</PrimaryButton>
                                </form>

                                <form class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5" @submit.prevent="submitCustom">
                                    <h3 class="text-lg font-semibold text-stone-100">Выдать кастомный предмет</h3>
                                    <div class="mt-4">
                                        <InputLabel for="custom-name" value="Название" />
                                        <TextInput id="custom-name" v-model="customForm.custom_name" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="customForm.errors.custom_name" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="custom-description" value="Описание" />
                                        <textarea id="custom-description" v-model="customForm.custom_description" class="mt-2 block min-h-28 w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30" />
                                        <InputError class="mt-2" :message="customForm.errors.custom_description" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="custom-image" value="Изображение" />
                                        <input id="custom-image" type="file" accept="image/*" class="mt-2 block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 file:mr-4 file:rounded-xl file:border-0 file:bg-amber-500/15 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-amber-100" @change="setCustomImage" />
                                        <InputError class="mt-2" :message="customForm.errors.custom_image" />
                                    </div>
                                    <div class="mt-4">
                                        <InputLabel for="custom-quantity" value="Количество" />
                                        <input id="custom-quantity" v-model.number="customForm.quantity" type="number" min="1" class="mt-2 block w-full rounded-[1.15rem] border border-white/10 bg-stone-950 px-4 py-3 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30" />
                                        <InputError class="mt-2" :message="customForm.errors.quantity" />
                                    </div>
                                    <PrimaryButton class="mt-4" :disabled="customForm.processing">Выдать кастомный предмет</PrimaryButton>
                                </form>
                            </div>

                            <div v-if="character.inventory_items.length === 0" class="mt-6 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                                Инвентарь пока пуст.
                            </div>

                            <div v-else class="mt-6 space-y-4">
                                <article
                                    v-for="inventoryItem in character.inventory_items"
                                    :key="inventoryItem.id"
                                    class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:border-amber-300/20"
                                >
                                    <div class="flex flex-col gap-4">
                                        <div class="flex gap-4">
                                            <div v-if="inventoryItem.image_url" class="h-20 w-20 overflow-hidden rounded-2xl border border-white/10 bg-stone-950/60">
                                                <img :src="inventoryItem.image_url" alt="Изображение предмета" class="h-full w-full object-cover" />
                                            </div>
                                            <div v-else class="grid h-20 w-20 place-items-center rounded-2xl border border-dashed border-stone-600/50 bg-stone-950/50 text-[11px] uppercase tracking-[0.18em] text-stone-500">
                                                Item
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
                                                <input v-model.number="inventoryItem.quantity" type="number" min="1" class="w-24 rounded-[1rem] border border-white/10 bg-stone-950 px-3 py-2 text-sm text-stone-100 shadow-sm transition focus:border-amber-300/60 focus:outline-none focus:ring-2 focus:ring-amber-300/30" />
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
    </AuthenticatedLayout>
</template>
