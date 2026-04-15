<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    invite: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const form = useForm({});
const currentUrl = computed(() => page.url);
</script>

<template>
    <Head :title="`Invite to ${invite.game.name}`" />

    <GuestLayout>
        <div class="w-full max-w-3xl">
            <section class="fantasy-panel">
                <p class="fantasy-kicker">Game Invite</p>
                <h1 class="mt-3 text-3xl font-semibold text-amber-50">{{ invite.game.name }}</h1>
                <p v-if="invite.game.description" class="mt-4 text-sm leading-7 text-stone-300">
                    {{ invite.game.description }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3 text-sm text-stone-400">
                    <span>GM: {{ invite.game.owner.name }}</span>
                    <span>Players: {{ invite.game.member_count }}</span>
                </div>

                <div class="mt-8 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg border border-amber-300/20 bg-stone-950/50 p-4">
                        <p class="text-sm font-semibold text-amber-50">1. Join game</p>
                        <p class="mt-2 text-sm text-stone-400">Become a member of this campaign.</p>
                    </div>
                    <div class="rounded-lg border border-amber-300/20 bg-stone-950/50 p-4">
                        <p class="text-sm font-semibold text-amber-50">2. Create character</p>
                        <p class="mt-2 text-sm text-stone-400">Fill your sheet before session play.</p>
                    </div>
                    <div class="rounded-lg border border-amber-300/20 bg-stone-950/50 p-4">
                        <p class="text-sm font-semibold text-amber-50">3. Join session</p>
                        <p class="mt-2 text-sm text-stone-400">Use the session code or session link from GM.</p>
                    </div>
                </div>

                <div
                    v-if="invite.is_expired"
                    class="mt-8 rounded-lg border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-200"
                >
                    This invite link has expired. Ask the GM for a new game invite.
                </div>

                <div v-else class="mt-8 space-y-4">
                    <form
                        v-if="page.props.auth.user"
                        @submit.prevent="form.post(route('invites.accept', invite.token), { preserveScroll: true, preserveState: true })"
                    >
                        <PrimaryButton :disabled="form.processing">
                            Join game
                        </PrimaryButton>
                    </form>

                    <div v-else class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4 text-sm text-stone-300">
                        Sign in or create an account first. After that, this invite will open again and you can join the game.
                    </div>

                    <div v-if="!page.props.auth.user" class="flex flex-wrap gap-3">
                        <Link :href="route('login', { redirect: currentUrl })">
                            <SecondaryButton>Log in</SecondaryButton>
                        </Link>
                        <Link :href="route('register', { redirect: currentUrl })">
                            <SecondaryButton>Register</SecondaryButton>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>
