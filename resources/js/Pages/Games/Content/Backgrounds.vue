<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ThemeIcon from '@/Components/ThemeIcon.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    backgrounds: { type: Array, required: true },
});

const page = usePage();
const showCreateModal = ref(false);
const form = useForm({
    title: '',
    image: null,
});

const setImage = (event) => {
    form.image = event.target.files[0] ?? null;
};

const submit = () => {
    form.post(route('games.backgrounds.store', props.game.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset('title', 'image');
            showCreateModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Фоны - ${game.name}`" />

    <GameThemeLayout :game="game" section="Фоны" :title="game.name">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="fantasy-kicker">Фоны</p>
                    <h1 class="fantasy-title">Фоны игры {{ game.name }}</h1>
                </div>
                <Link :href="route('games.show', game.id)">
                    <SecondaryButton>Назад к игре</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="theme-panel-head">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="gm-panel-icon">
                                <ThemeIcon src="/storage/ui/icons/backgrounds.png" name="backgrounds" />
                            </span>
                            <div>
                                <p class="gm-kicker">Каталог фонов</p>
                                <h2 class="theme-panel-title">Сцены и локации</h2>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="gm-badge">{{ backgrounds.length }} фонов</span>
                            <PrimaryButton @click="showCreateModal = true">Загрузить фон</PrimaryButton>
                        </div>
                    </div>

                    <div v-if="page.props.flash.success" class="gm-alert gm-alert-success mt-4">
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="backgrounds.length === 0" class="theme-empty mt-5">
                        Фоны пока не добавлены.
                    </div>

                    <div v-else class="theme-list-grid">
                        <article v-for="background in backgrounds" :key="background.id" class="theme-card theme-card-interactive">
                            <div class="theme-media aspect-[16/9] w-full">
                                <img v-if="background.image_url" :src="background.image_url" :alt="background.title" />
                                <div v-else class="grid h-full place-items-center text-xs uppercase tracking-[0.18em] text-stone-500">Фон</div>
                            </div>
                            <div class="mt-4 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="gm-kicker">Локация</p>
                                    <h3 class="mt-1 truncate text-lg font-semibold text-[#fff1c8]">{{ background.title }}</h3>
                                </div>
                                <Link :href="route('games.backgrounds.edit', [game.id, background.id])">
                                    <SecondaryButton>Изменить</SecondaryButton>
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Загрузить фон</h2>
                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="background-title" value="Название" />
                        <TextInput id="background-title" v-model="form.title" class="mt-2 block w-full" />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>
                    <div>
                        <InputLabel for="background-image" value="Изображение" />
                        <input id="background-image" type="file" accept="image/*" class="fantasy-file mt-2 block w-full" @change="setImage" />
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton @click="showCreateModal = false">Отмена</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Загрузить фон</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </GameThemeLayout>
</template>
