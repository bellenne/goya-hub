<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="classic-guest-shell gm-shell gm-guest-shell">
        <Link href="/" class="gm-sidebar-brand classic-guest-brand">
            <span class="gm-sidebar-mark">
                <ApplicationLogo class="h-12 w-auto" />
            </span>
            <span>
                <span class="gm-kicker classic-kicker block">GoYa Table</span>
                <span class="gm-sidebar-title block">Книга мастера</span>
            </span>
        </Link>

        <div class="gm-panel classic-guest-panel">
            <slot />
        </div>
    </div>
</template>
