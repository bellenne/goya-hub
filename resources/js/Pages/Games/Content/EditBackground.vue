<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    game: { type: Object, required: true },
    background: { type: Object, required: true },
});

const form = useForm({
    title: props.background.title,
    image: null,
});

const setImage = (event) => {
    form.image = event.target.files[0] ?? null;
};

const submit = () => {
    form.patch(route('games.backgrounds.update', [props.game.id, props.background.id]), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Редактирование фона - ${background.title}`" />

    <GameThemeLayout :game="game" section="Фоны" :title="background.title">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Фоны</p>
                    <h1 class="fantasy-title text-3xl">Редактирование фона</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Обновите название или изображение сцены, не затрагивая остальные материалы игры.
                    </p>
                </div>
                <Link :href="route('games.backgrounds.index', game.id)">
                    <SecondaryButton>Назад к списку</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="theme-page">
            <div class="mx-auto max-w-5xl">
                <form class="theme-panel" @submit.prevent="submit">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                        <div class="space-y-5">
                            <div>
                                <InputLabel for="background-title" value="Название" />
                                <TextInput id="background-title" v-model="form.title" class="mt-2 block w-full" />
                                <InputError class="mt-2" :message="form.errors.title" />
                            </div>
                            <div>
                                <InputLabel for="background-image" value="Новое изображение" />
                                <input id="background-image" type="file" accept="image/*" class="fantasy-file mt-2 block w-full" @change="setImage" />
                                <InputError class="mt-2" :message="form.errors.image" />
                            </div>
                        </div>

                        <div class="theme-card">
                            <p class="text-sm font-medium text-stone-300">Текущее изображение</p>
                            <div v-if="background.image_url" class="mt-3 h-48 w-full theme-media">
                                <img :src="background.image_url" :alt="background.title" class="h-full w-full object-contain" />
                            </div>
                            <div v-else class="theme-empty mt-3 grid h-48 place-items-center text-sm">
                                Изображение не загружено
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <PrimaryButton :disabled="form.processing">Сохранить фон</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </GameThemeLayout>
</template>
