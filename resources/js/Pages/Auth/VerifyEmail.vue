<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Подтверждение почты" />

        <div class="mb-6">
            <p class="fantasy-kicker">Почта</p>
            <h1 class="mt-2 text-2xl font-semibold text-amber-50">Подтвердите адрес</h1>
            <p class="mt-2 text-sm leading-6 text-stone-400">
                Мы отправили письмо со ссылкой подтверждения. Если письмо не пришло, можно запросить новое.
            </p>
        </div>

        <div
            class="gm-alert gm-alert-success mb-4"
            v-if="verificationLinkSent"
        >
            Новая ссылка подтверждения отправлена на почту, указанную при регистрации.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Отправить письмо повторно
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-medium text-teal-200 transition hover:text-amber-100 focus:outline-none"
                    >Выйти</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
