<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    theme_preference: user.theme_preference ?? 'classic',
});

const themes = [
    {
        value: 'classic',
        title: 'Классическая',
        description: 'Тёмная фэнтези-панель с деревом, кожей, латунью и пергаментом.',
    },
    {
        value: 'modern',
        title: 'Современная',
        description: 'Текущий лаконичный тёмный интерфейс без выраженной декоративной фактуры.',
    },
];
</script>

<template>
    <section>
        <header>
            <p class="fantasy-kicker">Личные данные</p>
            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Профиль пользователя</h2>
            <p class="mt-3 text-sm leading-6 text-stone-400">
                Обновите имя и адрес электронной почты, которые используются в аккаунте.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Имя" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-2 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Электронная почта" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel value="Тема интерфейса" />
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label
                        v-for="theme in themes"
                        :key="theme.value"
                        class="theme-choice"
                        :class="{ 'theme-choice-active': form.theme_preference === theme.value }"
                    >
                        <input v-model="form.theme_preference" type="radio" name="theme_preference" :value="theme.value" class="sr-only" />
                        <span class="theme-choice-title">{{ theme.title }}</span>
                        <span class="theme-choice-description">{{ theme.description }}</span>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.theme_preference" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="theme-empty text-amber-100">
                <p>
                    Адрес электронной почты ещё не подтверждён.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="font-semibold text-amber-50 underline decoration-amber-300/50 underline-offset-4 transition hover:text-white focus:outline-none"
                    >
                        Отправить письмо повторно.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-3 text-sm font-medium text-emerald-200">
                    Новая ссылка подтверждения отправлена на вашу почту.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Сохранить изменения</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-200">
                        Сохранено.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
