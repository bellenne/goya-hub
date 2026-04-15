<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
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
    <Head :title="`Backgrounds - ${game.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="fantasy-kicker">Backgrounds</p>
                    <h1 class="fantasy-title">Фоны игры {{ game.name }}</h1>
                </div>
                <Link :href="route('games.show', game.id)">
                    <SecondaryButton>Назад к игре</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <section class="fantasy-panel">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-amber-50">Сцены и локации</h2>
                            <p class="fantasy-subtitle mt-2">
                                Загруженные изображения используются как фон сцены в realtime-сессии.
                            </p>
                        </div>
                        <PrimaryButton @click="showCreateModal = true">Загрузить фон</PrimaryButton>
                    </div>

                    <div v-if="page.props.flash.success" class="mt-4 rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="backgrounds.length === 0" class="fantasy-empty mt-6">
                        Фоны пока не добавлены.
                    </div>

                    <div v-else class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="background in backgrounds" :key="background.id" class="fantasy-card">
                            <img v-if="background.image_url" :src="background.image_url" :alt="background.title" class="h-48 w-full rounded-lg object-cover ring-1 ring-amber-300/20" />
                            <h3 class="mt-4 text-lg font-semibold text-amber-50">{{ background.title }}</h3>
                            <Link :href="route('games.backgrounds.edit', [game.id, background.id])" class="mt-4 inline-flex">
                                <SecondaryButton>Редактировать</SecondaryButton>
                            </Link>
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
    </AuthenticatedLayout>
</template>
