<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="app-shell">
            <nav class="app-nav">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex min-h-[5.5rem] flex-wrap items-center justify-between gap-4 py-4">
                        <div class="flex items-center gap-5">
                            <ApplicationLogo class="block h-12 w-auto fill-current" />
                            <div class="flex shrink-0 items-center">
                            
                                <Link :href="route('home')">
                                    <div class="flex items-center gap-3 rounded-[1.35rem] border border-amber-300/15 bg-white/[0.04] px-3 py-2 transition hover:border-amber-300/25 hover:bg-white/[0.06]">
                                        
                                        <div class="hidden sm:block">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-amber-200/70">GoYa Hub</div>
                                            <div class="text-sm font-medium text-stone-100">Игровая панель</div>
                                        </div>
                                    </div>
                                </Link>
                            </div>

                            <div class="hidden items-center gap-2 lg:flex">
                                <NavLink :href="route('home')" :active="route().current('home')">
                                    Главная
                                </NavLink>
                                <NavLink :href="route('games.index')" :active="route().current('games.*')">
                                    Игры
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48" content-classes="app-dropdown-panel">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-3 rounded-[1.25rem] border border-white/10 bg-white/[0.04] px-3 py-2 text-sm font-medium leading-4 text-slate-200 transition duration-150 ease-in-out hover:border-amber-300/25 hover:bg-white/[0.08] hover:text-white focus:outline-none"
                                            >
                                                <span class="flex h-9 w-9 items-center justify-center rounded-full border border-amber-300/20 bg-amber-300/10 text-xs font-semibold uppercase tracking-[0.16em] text-amber-100">
                                                    {{ ($page.props.auth.user.name || '?').slice(0, 2) }}
                                                </span>
                                                <span class="text-left">
                                                    <span class="block text-xs uppercase tracking-[0.2em] text-amber-200/70">Профиль</span>
                                                    <span class="block text-sm text-stone-100">{{ $page.props.auth.user.name }}</span>
                                                </span>

                                                <svg
                                                    class="h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Настройки
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Выйти
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] p-2 text-slate-300 transition duration-150 ease-in-out hover:border-amber-300/20 hover:bg-white/[0.08] hover:text-white focus:bg-white/[0.08] focus:text-white focus:outline-none"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-2 px-4 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('home')" :active="route().current('home')">
                            Главная
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('games.index')" :active="route().current('games.*')">
                            Игры
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-white/10 px-4 pb-4 pt-4">
                        <div class="rounded-[1.25rem] border border-white/10 bg-white/[0.04] px-4 py-4">
                            <div class="text-base font-medium text-slate-100">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-slate-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Настройки
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                                Выйти
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="app-header" v-if="$slots.header">
                <div class="app-header-shell">
                    <div class="mx-auto max-w-7xl">
                        <div class="app-header-panel">
                            <div class="app-header-inner">
                                <slot name="header" />
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
