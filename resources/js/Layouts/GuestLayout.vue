<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="app-shell flex min-h-screen flex-col items-center px-4 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-amber-300" />
            </Link>
        </div>

        <div
            class="mt-6 w-full max-w-3xl overflow-hidden rounded-lg border border-amber-300/20 bg-stone-900/75 px-6 py-4 text-stone-100 shadow-[0_24px_80px_rgba(0,0,0,0.45)]"
        >
            <slot />
        </div>
    </div>
</template>
