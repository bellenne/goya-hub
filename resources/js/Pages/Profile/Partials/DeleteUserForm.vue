<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <p class="fantasy-kicker text-rose-200/80">Опасная зона</p>
            <h2 class="mt-2 text-2xl font-semibold text-rose-100">Удаление аккаунта</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-400">
                После удаления аккаунта все связанные с ним данные будут безвозвратно удалены. Перед этим убедитесь, что это действительно нужно.
            </p>
        </header>

        <div class="theme-empty text-rose-100/90">
            Это действие необратимо. Если вы продолжите, восстановить профиль уже не получится.
        </div>

        <DangerButton @click="confirmUserDeletion">Удалить аккаунт</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-rose-100">
                    Подтвердите удаление аккаунта
                </h2>

                <p class="mt-3 text-sm leading-6 text-stone-400">
                    Введите текущий пароль, чтобы подтвердить окончательное удаление аккаунта и всех связанных данных.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Пароль" class="sr-only" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Введите пароль"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        Отмена
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Удалить аккаунт
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
