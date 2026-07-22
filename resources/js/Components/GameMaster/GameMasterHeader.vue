<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FantasyIconButton from '@/Components/Fantasy/FantasyIconButton.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    showMenu: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['menu']);

const page = usePage();
const initials = computed(() => (page.props.auth?.user?.name || '?').slice(0, 2));
</script>

<template>
    <header class="gm-topbar">
        <div class="flex min-w-0 items-center gap-3">
            <button v-if="showMenu" type="button" class="gm-menu-button xl:hidden" aria-label="Открыть меню" @click="$emit('menu')">
                <span />
                <span />
                <span />
            </button>

            <div class="min-w-0">
                <h1 class="gm-topbar-title">{{ title }}</h1>
                <p v-if="subtitle" class="gm-topbar-subtitle">{{ subtitle }}</p>
            </div>
        </div>

        <div class="gm-topbar-actions">
            <FantasyIconButton label="Журнал" icon="/storage/ui/icons/Notes.png" />
            <FantasyIconButton label="Уведомления" icon="/storage/ui/icons/Notifications.png" />

            <Dropdown align="right" width="48" content-classes="gm-dropdown-panel">
                <template #trigger>
                    <button type="button" class="gm-profile-button">
                        <span class="gm-profile-avatar">{{ initials }}</span>
                        <span class="hidden min-w-0 text-left sm:block">
                            <span class="gm-kicker block">Профиль</span>
                            <span class="block truncate text-sm font-semibold text-[#fff1c8]">{{ page.props.auth.user.name }}</span>
                        </span>
                    </button>
                </template>

                <template #content>
                    <DropdownLink :href="route('profile.edit')">Настройки</DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">Выйти</DropdownLink>
                </template>
            </Dropdown>
        </div>
    </header>
</template>
