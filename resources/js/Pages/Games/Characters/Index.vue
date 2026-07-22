<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ThemeIcon from '@/Components/ThemeIcon.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    characters: { type: Array, required: true },
});

const withAvatarCount = computed(() => props.characters.filter((character) => character.avatar_url).length);
const withoutOriginCount = computed(() => props.characters.filter((character) => !character.origin).length);
</script>

<template>
    <Head :title="`Персонажи - ${game.name}`" />

    <GameThemeLayout :game="game" section="Персонажи" :title="game.name">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Персонажи</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Все персонажи игры на одном экране.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                    <Link :href="route('games.character.edit', game.id)">
                        <PrimaryButton>Мой персонаж</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="theme-panel-head">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="gm-panel-icon">
                                <ThemeIcon src="/storage/ui/icons/Characters.png" name="characters" />
                            </span>
                            <div>
                                <p class="gm-kicker">Состав партии</p>
                                <h2 class="theme-panel-title">Все игровые листы</h2>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="gm-badge">{{ characters.length }} всего</span>
                            <span class="gm-badge">{{ withAvatarCount }} с аватаром</span>
                            <span class="gm-badge">{{ withoutOriginCount }} без происхождения</span>
                        </div>
                    </div>

                    <div v-if="characters.length === 0" class="theme-empty mt-5">
                        В игре пока нет созданных персонажей.
                    </div>

                    <div v-else class="theme-list-grid">
                        <article v-for="character in characters" :key="character.id" class="theme-card theme-card-interactive">
                            <div class="grid gap-4 sm:grid-cols-[6rem_1fr]">
                                <div v-if="character.avatar_url" class="theme-media h-24 w-24">
                                    <img :src="character.avatar_url" alt="Аватар персонажа" />
                                </div>
                                <div v-else class="theme-media grid h-24 w-24 place-items-center text-xs uppercase tracking-[0.2em] text-stone-500">
                                    Герой
                                </div>

                                <div class="min-w-0">
                                    <p class="gm-kicker">{{ character.user.name }}</p>
                                    <h3 class="mt-1 truncate text-lg font-semibold text-[#fff1c8]">{{ character.name }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-[#b8a685]">
                                        {{ character.origin || 'Происхождение не указано' }}
                                    </p>
                                    <p class="mt-2 truncate text-xs text-[#8f7e63]">{{ character.user.email }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <Link :href="route('games.characters.show', [game.id, character.id])">
                                    <PrimaryButton>Открыть лист</PrimaryButton>
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </GameThemeLayout>
</template>
