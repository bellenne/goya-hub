<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
    <Head :title="`Edit Background - ${background.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-cyan-400/80">Backgrounds</p>
                    <h1 class="text-2xl font-semibold text-white">Редактирование фона</h1>
                </div>
                <Link :href="route('games.backgrounds.index', game.id)">
                    <SecondaryButton>Назад к списку</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-4 rounded-3xl border border-white/10 bg-slate-900/80 p-6" @submit.prevent="submit">
                    <div>
                        <InputLabel for="background-title" value="Название" />
                        <TextInput id="background-title" v-model="form.title" class="mt-2 block w-full" />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>
                    <div>
                        <InputLabel for="background-image" value="Изображение" />
                        <input id="background-image" type="file" accept="image/*" class="mt-2 block w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm text-slate-100" @change="setImage" />
                        <img v-if="background.image_url" :src="background.image_url" :alt="background.title" class="mt-3 h-40 w-full rounded-2xl object-cover" />
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>
                    <PrimaryButton :disabled="form.processing">Сохранить фон</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
