import { computed, onBeforeUnmount, ref } from 'vue';

const ENTER_MS = 0;
const ROLL_MS = 3000;
const SETTLE_MS = 450;
const REVEAL_MS = 1700;
const GAP_MS = 160;

const diceOffsets = (count) => {
    const spacing = count > 5 ? 4.6 : 5.4;
    const center = (count - 1) / 2;

    return Array.from({ length: count }, (_, index) => ({
        x: (index - center) * spacing,
        y: Math.abs(index - center) * 0.5,
        delay: index * 90,
    }));
};

const makeAnimation = (roll) => {
    const offsets = diceOffsets(roll.roll_values.length);

    return {
        id: `dice-roll-${roll.id}`,
        phase: 'entering',
        actorName: roll.actor_name ?? roll.user?.name ?? '',
        diceType: roll.dice_type,
        diceCount: roll.dice_count,
        formula: `${roll.dice_count}${roll.dice_type}`,
        rollValues: roll.roll_values,
        total: roll.total,
        modifier: Number(roll.modifier ?? 0),
        randomSource: roll.random_source,
        dice: roll.roll_values.map((value, index) => ({
            id: `${roll.id}-${index}`,
            value,
            index,
            offset: offsets[index],
        })),
    };
};

export function useDiceRollAnimationQueue() {
    const queue = ref([]);
    const active = ref(null);
    const timeouts = new Set();

    const hasActiveAnimation = computed(() => active.value !== null);

    const setManagedTimeout = (callback, delay) => {
        const timeout = setTimeout(() => {
            timeouts.delete(timeout);
            callback();
        }, delay);

        timeouts.add(timeout);
    };

    const runNext = () => {
        if (active.value || queue.value.length === 0) {
            return;
        }

        const [nextAnimation, ...remaining] = queue.value;
        queue.value = remaining;
        active.value = nextAnimation;

        setManagedTimeout(() => {
            if (active.value?.id === nextAnimation.id) {
                active.value = { ...active.value, phase: 'rolling' };
            }
        }, ENTER_MS);

        setManagedTimeout(() => {
            if (active.value?.id === nextAnimation.id) {
                active.value = { ...active.value, phase: 'settling' };
            }
        }, ENTER_MS + ROLL_MS);

        setManagedTimeout(() => {
            if (active.value?.id === nextAnimation.id) {
                active.value = { ...active.value, phase: 'revealed' };
            }
        }, ENTER_MS + ROLL_MS + SETTLE_MS);

        setManagedTimeout(() => {
            if (active.value?.id === nextAnimation.id) {
                active.value = null;
            }

            setManagedTimeout(runNext, GAP_MS);
        }, ENTER_MS + ROLL_MS + SETTLE_MS + REVEAL_MS);
    };

    const enqueue = (roll) => {
        if (!roll?.id || !Array.isArray(roll.roll_values)) {
            return;
        }

        queue.value = [
            ...queue.value,
            makeAnimation(roll),
        ];
        runNext();
    };

    const clear = () => {
        timeouts.forEach((timeout) => clearTimeout(timeout));
        timeouts.clear();
        queue.value = [];
        active.value = null;
    };

    onBeforeUnmount(clear);

    return {
        activeDiceRollAnimation: active,
        hasActiveDiceRollAnimation: hasActiveAnimation,
        enqueueDiceRollAnimation: enqueue,
        clearDiceRollAnimations: clear,
    };
}
