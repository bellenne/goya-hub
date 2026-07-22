<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Восстановление пароля" />

        <div class="mb-6">
            <p class="fantasy-kicker">Доступ</p>
            <h1 class="mt-2 text-2xl font-semibold text-amber-50">Восстановление пароля</h1>
            <p class="mt-2 text-sm leading-6 text-stone-400">
                Укажите почту аккаунта. Мы отправим ссылку, по которой можно задать новый пароль.
            </p>
        </div>

        <div
            v-if="status"
            class="gm-alert gm-alert-success mb-4"
        >
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Электронная почта" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Отправить ссылку
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
