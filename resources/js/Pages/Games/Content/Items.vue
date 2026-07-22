
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
    items: { type: Array, required: true },
    selectedItem: { type: Object, default: null },
});

const page = usePage();
const deleteModalItem = ref(null);
const imageInputKey = ref(0);
const search = ref('');

const emptyForm = () => ({
    name: '',
    image: null,
    category: '',
    description: '',
});

const form = useForm(emptyForm());
const deleteForm = useForm({});
const activeItemId = ref(props.selectedItem?.id ?? null);

const activeItem = computed(() => props.items.find((item) => item.id === activeItemId.value) ?? null);
const isEditing = computed(() => activeItem.value !== null);
const filteredItems = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.items;
    }

    return props.items.filter((item) => item.name.toLowerCase().includes(query));
});
const submitLabel = computed(() => {
    if (form.processing) {
        return isEditing.value ? 'Сохраняем...' : 'Создаём...';
    }

    return isEditing.value ? 'Сохранить предмет' : 'Создать предмет';
});

const applyItemToForm = (item) => {
    form.defaults({
        name: item?.name ?? '',
        image: null,
        category: item?.category ?? '',
        description: item?.description ?? '',
    });

    form.reset();
    form.clearErrors();
    imageInputKey.value += 1;
};

const startCreate = () => {
    activeItemId.value = null;
    applyItemToForm(null);
};

const selectItem = (item) => {
    activeItemId.value = item.id;
    applyItemToForm(item);
};

const setImage = (event) => {
    form.image = event.target.files[0] ?? null;
};

const submit = () => {
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(route('games.items.update', [props.game.id, activeItem.value.id]), {
            forceFormData: true,
            preserveScroll: true,
        });

        return;
    }

    form.transform((data) => data).post(route('games.items.store', props.game.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const confirmDelete = (item) => {
    deleteModalItem.value = item;
};

const destroyItem = () => {
    if (!deleteModalItem.value) {
        return;
    }

    deleteForm.delete(route('games.items.destroy', [props.game.id, deleteModalItem.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            if (activeItemId.value === deleteModalItem.value.id) {
                startCreate();
            }

            deleteModalItem.value = null;
        },
    });
};

watch(
    () => props.selectedItem,
    (item) => {
        activeItemId.value = item?.id ?? null;
        applyItemToForm(item ?? null);
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="`Предметы - ${game.name}`" />

    <GameThemeLayout :game="game" section="Предметы" :title="game.name">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Предметы</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Ведите каталог предметов игры в одном рабочем экране: список, создание, редактирование и удаление без отдельной страницы.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                    <PrimaryButton @click="startCreate">Новый предмет</PrimaryButton>
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
                                    <p class="fantasy-kicker">Список предметов</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-amber-50">Весь каталог игры</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Выберите предмет для редактирования или удалите его прямо из списка.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="gm-badge">{{ items.length }} всего</span>
                                    <span class="gm-badge">{{ isEditing ? 'Редактирование' : 'Создание' }}</span>
                                </div>
                                <PrimaryButton type="button" @click="startCreate">Создать новый</PrimaryButton>
                            </div>
                            <div class="mt-5">
                                <InputLabel for="item-search" value="Поиск по имени" />
                                <TextInput id="item-search" v-model="search" class="mt-2 block w-full" placeholder="Введите название предмета" />
                            </div>

                            <div v-if="items.length === 0" class="mt-5 theme-empty">
                                Предметы пока не добавлены.
                            </div>
                            <div v-else-if="filteredItems.length === 0" class="mt-5 theme-empty">
                                По такому имени предметы не найдены.
                            </div>

                            <div v-else class="mt-5 space-y-4">
                                <article
                                    v-for="item in filteredItems"
                                    :key="item.id"
                                    class="theme-list-row"
                                    :class="activeItem?.id === item.id
                                        ? 'border-amber-300/40 bg-amber-300/10 shadow-[0_0_40px_rgba(251,191,36,0.08)]'
                                        : 'theme-card-interactive'"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <button type="button" class="flex min-w-0 flex-1 items-start gap-4 text-left" @click="selectItem(item)">
                                            <div v-if="item.image_url" class="h-20 w-20 overflow-hidden theme-media">
                                                <img :src="item.image_url" alt="Изображение предмета" class="h-full w-full object-contain" />
                                            </div>
                                            <div v-else class="grid h-20 w-20 place-items-center theme-media text-xs uppercase tracking-[0.2em] text-stone-500">
                                                Предмет
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="truncate text-lg font-semibold text-amber-50">{{ item.name }}</h3>
                                                    <span v-if="item.category" class="fantasy-chip-muted">{{ item.category }}</span>
                                                </div>
                                                <p v-if="item.description" class="mt-2 text-sm leading-6 text-stone-400">{{ item.description }}</p>
                                                <p v-else class="mt-2 text-sm text-stone-500">Без описания.</p>
                                            </div>
                                        </button>

                                        <div class="flex items-center gap-2">
                                            <SecondaryButton type="button" @click="selectItem(item)">Изменить</SecondaryButton>
                                            <button
                                                type="button"
                                                class="gm-button gm-button-danger px-3 py-2 text-sm"
                                                @click="confirmDelete(item)"
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
                                    <h2 class="mt-2 text-2xl font-semibold text-teal-50">{{ isEditing ? 'Редактирование предмета' : 'Создание предмета' }}</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">{{ isEditing ? 'Измените данные выбранного предмета и сохраните их без перехода на другую страницу.' : 'Заполните карточку нового предмета и сразу добавьте его в каталог игры.' }}</p>
                                </div>
                                <div class="flex gap-3">
                                    <SecondaryButton type="button" @click="startCreate">Очистить форму</SecondaryButton>
                                    <button
                                        v-if="isEditing"
                                        type="button"
                                        class="gm-button gm-button-danger px-3 py-2 text-sm"
                                        @click="confirmDelete(activeItem)"
                                    >
                                        Удалить предмет
                                    </button>
                                </div>
                            </div>

                            <form class="mt-6 space-y-5" @submit.prevent="submit">
                                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
                                    <div>
                                        <InputLabel for="item-name" value="Название" />
                                        <TextInput id="item-name" v-model="form.name" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.name" />
                                    </div>
                                    <div>
                                        <InputLabel for="item-category" value="Категория" />
                                        <TextInput id="item-category" v-model="form.category" class="mt-2 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.category" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel :for="`item-image-${imageInputKey}`" value="Изображение" />
                                    <input :id="`item-image-${imageInputKey}`" :key="imageInputKey" type="file" accept="image/*" class="fantasy-file mt-2 block w-full" @change="setImage" />
                                    <InputError class="mt-2" :message="form.errors.image" />
                                </div>

                                <div v-if="activeItem?.image_url" class="theme-card">
                                    <p class="text-sm font-medium text-stone-300">Текущее изображение</p>
                                    <div class="mt-3 h-28 w-28 theme-media">
                                        <img :src="activeItem.image_url" alt="Текущее изображение предмета" class="h-full w-full object-contain" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="item-description" value="Описание" />
                                    <textarea id="item-description" v-model="form.description" class="fantasy-textarea mt-2 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.description" />
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

        <Modal :show="deleteModalItem !== null" @close="deleteModalItem = null">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Удалить предмет</h2>
                <p class="mt-3 text-sm leading-6 text-stone-400">
                    Предмет <span class="font-semibold text-stone-100">{{ deleteModalItem?.name }}</span> будет удалён из каталога игры. Это действие нельзя отменить.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton type="button" @click="deleteModalItem = null">Отмена</SecondaryButton>
                    <button
                        type="button"
                        class="gm-button gm-button-danger px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="deleteForm.processing"
                        @click="destroyItem"
                    >
                        {{ deleteForm.processing ? 'Удаляем...' : 'Удалить предмет' }}
                    </button>
                </div>
            </div>
        </Modal>
    </GameThemeLayout>
</template>
