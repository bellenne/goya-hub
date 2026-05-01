<script setup>
import { computed } from 'vue';

const props = defineProps({
    effect: { type: Object, default: null },
});

const frameStyle = computed(() => {
    if (!props.effect) return {};

    const phase = props.effect.phase;
    const point = phase === 'flying'
        ? props.effect.target
        : props.effect.center;
    const scale = phase === 'entering' ? 0.74 : (phase === 'flying' ? 0.44 : 1);
    const opacity = phase === 'entering' ? 0 : (phase === 'flying' ? 0.08 : 1);

    return {
        '--npc-spawn-glow': props.effect.glowColor,
        '--npc-spawn-glow-soft': props.effect.glowSoftColor,
        opacity,
        transform: `translate3d(${point.x}px, ${point.y}px, 0) translate(-50%, -50%) scale(${scale})`,
    };
});

const initial = computed(() => props.effect?.title?.trim()?.charAt(0) ?? '?');
</script>

<template>
    <div class="pointer-events-none fixed inset-0 z-[70] overflow-hidden">
        <Transition name="npc-spawn-shell">
            <div
                v-if="effect"
                :key="effect.id"
                class="npc-spawn-frame fixed left-0 top-0"
                :class="`npc-spawn-frame-${effect.phase}`"
                :style="frameStyle"
            >
                <div class="npc-spawn-aura" />
                <article class="npc-spawn-card">
                    <div class="npc-spawn-portrait">
                        <img
                            v-if="effect.avatarUrl"
                            :src="effect.avatarUrl"
                            :alt="effect.title"
                            class="npc-spawn-image"
                        />
                        <span v-else>{{ initial }}</span>
                    </div>
                    <div class="npc-spawn-title-row">
                        <h2>{{ effect.title }}</h2>
                        <span v-if="effect.badge" class="npc-spawn-badge">{{ effect.badge }}</span>
                    </div>
                    <p class="npc-spawn-type">{{ effect.typeLabel }}</p>
                </article>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.npc-spawn-frame {
    width: fit-content;
    max-width: calc(100vw - 3rem);
    will-change: transform, opacity;
    transition:
        transform 1150ms cubic-bezier(0.18, 0.84, 0.28, 1),
        opacity 420ms ease;
}

.npc-spawn-frame-holding {
    transition:
        transform 520ms cubic-bezier(0.18, 0.84, 0.28, 1),
        opacity 520ms ease;
}

.npc-spawn-frame-flying {
    transition:
        transform 1150ms cubic-bezier(0.16, 0.82, 0.24, 1),
        opacity 850ms ease 220ms;
}

.npc-spawn-aura {
    position: absolute;
    inset: -5.5rem;
    border-radius: 999px;
    background:
        radial-gradient(circle at 50% 48%, var(--npc-spawn-glow) 0%, var(--npc-spawn-glow-soft) 30%, transparent 66%);
    filter: blur(14px);
    opacity: 1;
    animation: npc-spawn-pulse 1550ms ease-in-out infinite;
    transition: opacity 280ms ease;
}

.npc-spawn-frame-flying .npc-spawn-aura {
    opacity: 0;
    animation: none;
}

.npc-spawn-card {
    position: relative;
    overflow: hidden;
    width: fit-content;
    max-width: calc(100vw - 3rem);
    border-radius: 0.65rem;
    border: 1px solid color-mix(in srgb, var(--npc-spawn-glow) 42%, rgba(251, 191, 36, 0.26));
    background:
        linear-gradient(180deg, rgba(28, 25, 23, 0.84), rgba(12, 10, 9, 0.94)),
        radial-gradient(circle at 50% 0%, var(--npc-spawn-glow-soft), transparent 66%);
    padding: 1rem;
    text-align: center;
    box-shadow:
        0 28px 100px rgba(0, 0, 0, 0.68),
        0 0 56px var(--npc-spawn-glow-soft),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transition:
        border-color 260ms ease,
        box-shadow 260ms ease;
}

.npc-spawn-frame-flying .npc-spawn-card {
    border-color: rgba(251, 191, 36, 0.16);
    box-shadow:
        0 20px 70px rgba(0, 0, 0, 0.56),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.npc-spawn-portrait {
    display: flex;
    width: min(20rem, calc(100vw - 5rem));
    max-height: min(68vh, 30rem);
    align-items: center;
    justify-content: center;
    place-items: center;
    overflow: hidden;
    margin: 0 auto;
    border-radius: 0.6rem;
    border: 1px solid rgba(251, 191, 36, 0.22);
    background: rgba(28, 25, 23, 0.78);
    color: #fef3c7;
    font-size: 4rem;
    font-weight: 700;
    text-shadow: 0 0 22px var(--npc-spawn-glow);
}

.npc-spawn-image {
    display: block;
    width: 100%;
    height: 100%;
    max-height: min(68vh, 30rem);
    object-fit: contain;
}

.npc-spawn-title-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    margin-top: 0.9rem;
    width: min(20rem, calc(100vw - 5rem));
    min-width: 0;
}

.npc-spawn-title-row h2 {
    min-width: 0;
    color: #fffbeb;
    font-size: clamp(1.25rem, 3.2vw, 1.85rem);
    font-weight: 700;
    line-height: 1.12;
    overflow-wrap: anywhere;
    text-shadow: 0 2px 18px rgba(0, 0, 0, 0.75);
}

.npc-spawn-badge {
    flex: 0 0 auto;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--npc-spawn-glow) 50%, rgba(251, 191, 36, 0.2));
    background: rgba(12, 10, 9, 0.68);
    padding: 0.22rem 0.56rem;
    color: #fffbeb;
    font-size: 0.78rem;
    font-weight: 800;
}

.npc-spawn-type {
    margin-top: 0.4rem;
    color: #d6d3d1;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.npc-spawn-shell-enter-active,
.npc-spawn-shell-leave-active {
    transition: opacity 220ms ease;
}

.npc-spawn-shell-enter-from,
.npc-spawn-shell-leave-to {
    opacity: 0;
}

@keyframes npc-spawn-pulse {
    0%, 100% {
        transform: scale(0.9);
        opacity: 0.7;
    }

    50% {
        transform: scale(1.14);
        opacity: 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .npc-spawn-frame,
    .npc-spawn-frame-holding,
    .npc-spawn-frame-flying,
    .npc-spawn-aura {
        animation: none;
        transition-duration: 1ms;
    }
}
</style>
