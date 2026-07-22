<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Подтверждение пароля" />

        <div class="mb-6">
            <p class="fantasy-kicker">Безопасность</p>
            <h1 class="mt-2 text-2xl font-semibold text-amber-50">Подтвердите пароль</h1>
            <p class="mt-2 text-sm leading-6 text-stone-400">
                Для доступа к этому разделу нужно повторно подтвердить пароль аккаунта.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Пароль" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Подтвердить
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
