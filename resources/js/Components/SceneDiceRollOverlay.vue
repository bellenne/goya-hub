<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';

const props = defineProps({
    animation: { type: Object, default: null },
});

const canvasHost = ref(null);
const renderer = shallowRef(null);
const scene = shallowRef(null);
const camera = shallowRef(null);
const diceMeshes = [];
let THREE = null;
let startToken = 0;
const clockState = {
    frame: null,
    startedAt: 0,
    width: 1,
    height: 1,
};

const ROLL_DURATION = 3000;
const SHOW_VALUES_AT = 2360;

const sourceLabel = computed(() => {
    if (!props.animation?.randomSource) return '';

    return props.animation.randomSource === 'random_org'
        ? 'RANDOM.ORG'
        : 'server fallback';
});

const visibleTotal = ref(false);

const palette = {
    d4: { color: 0x9f372f, emissive: 0x3f1110 },
    d6: { color: 0xb36b24, emissive: 0x3d2109 },
    d8: { color: 0x2d7f67, emissive: 0x0b2d25 },
    d10: { color: 0x2f5f9f, emissive: 0x0a1f3d },
    d12: { color: 0x7660a8, emissive: 0x20183d },
    d20: { color: 0xa89036, emissive: 0x3b310d },
};

const seeded = (seed) => {
    let value = Math.sin(seed * 999.91) * 10000;

    return value - Math.floor(value);
};

const dieSeed = (die, salt = 0) => (
    Number(String(props.animation?.id ?? '').replace(/\D/g, '').slice(-6) || 7)
    + die.index * 97
    + die.value * 31
    + salt
);

const disposeObject = (object) => {
    object.traverse((child) => {
        if (child.geometry) child.geometry.dispose();

        if (child.material) {
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                if (material.map) material.map.dispose();
                material.dispose();
            });
        }
    });
};

const clearScene = () => {
    startToken++;

    if (clockState.frame) {
        cancelAnimationFrame(clockState.frame);
        clockState.frame = null;
    }

    diceMeshes.splice(0).forEach((entry) => {
        scene.value?.remove(entry.group);
        disposeObject(entry.group);
    });

    renderer.value?.dispose();
    renderer.value = null;
    scene.value = null;
    camera.value = null;
};

const makeD10Geometry = () => {
    const vertices = [];
    const faces = [];
    const radius = 1;
    const upperY = 0.36;
    const lowerY = -0.36;

    vertices.push(new THREE.Vector3(0, 1.42, 0));
    vertices.push(new THREE.Vector3(0, -1.42, 0));

    for (let index = 0; index < 5; index++) {
        const angle = (Math.PI * 2 * index) / 5;
        vertices.push(new THREE.Vector3(Math.cos(angle) * radius, upperY, Math.sin(angle) * radius));
    }

    for (let index = 0; index < 5; index++) {
        const angle = (Math.PI * 2 * index) / 5 + Math.PI / 5;
        vertices.push(new THREE.Vector3(Math.cos(angle) * radius, lowerY, Math.sin(angle) * radius));
    }

    for (let index = 0; index < 5; index++) {
        const next = (index + 1) % 5;
        const upper = 2 + index;
        const upperNext = 2 + next;
        const lower = 7 + index;

        faces.push(0, upper, lower);
        faces.push(0, lower, upperNext);
        faces.push(1, lower, upper);
        faces.push(1, upperNext, lower);
    }

    const geometry = new THREE.BufferGeometry().setFromPoints(vertices);
    geometry.setIndex(faces);
    geometry.computeVertexNormals();

    return geometry;
};

const geometryFor = (diceType) => {
    switch (diceType) {
        case 'd4':
            return new THREE.TetrahedronGeometry(1.2, 0);
        case 'd6':
            return new THREE.BoxGeometry(1.65, 1.65, 1.65, 1, 1, 1);
        case 'd8':
            return new THREE.OctahedronGeometry(1.3, 0);
        case 'd10':
            return makeD10Geometry();
        case 'd12':
            return new THREE.DodecahedronGeometry(1.25, 0);
        case 'd20':
            return new THREE.IcosahedronGeometry(1.28, 0);
        default:
            return new THREE.IcosahedronGeometry(1.28, 0);
    }
};

const finalRotationFor = (diceType, value) => {
    const sides = Number(diceType.replace('d', '')) || 20;
    const angle = ((value - 1) / sides) * Math.PI * 2;

    return new THREE.Euler(
        0.18 + (value % 3) * 0.11,
        angle,
        -0.12 + (value % 5) * 0.05,
    );
};

const makeTextSprite = (text, size = 96) => {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 256;
    const context = canvas.getContext('2d');
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = 'rgba(12, 10, 9, 0.72)';
    context.beginPath();
    context.arc(128, 128, 92, 0, Math.PI * 2);
    context.fill();
    context.strokeStyle = 'rgba(251, 191, 36, 0.72)';
    context.lineWidth = 8;
    context.stroke();
    context.fillStyle = '#fffbeb';
    context.font = `800 ${size}px ui-sans-serif, system-ui, sans-serif`;
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText(String(text), 128, 132);

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({
        map: texture,
        transparent: true,
        opacity: 0,
        depthTest: false,
    }));
    sprite.scale.set(1.05, 1.05, 1.05);

    return sprite;
};

const setupRenderer = () => {
    if (!canvasHost.value) return;

    clearScene();
    canvasHost.value.replaceChildren();

    const nextScene = new THREE.Scene();
    const nextCamera = new THREE.PerspectiveCamera(38, 1, 0.1, 80);
    nextCamera.position.set(0, 6.8, 13.5);
    nextCamera.lookAt(0, 0.2, 0);

    const nextRenderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    nextRenderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    nextRenderer.shadowMap.enabled = true;
    nextRenderer.shadowMap.type = THREE.PCFShadowMap;
    canvasHost.value.appendChild(nextRenderer.domElement);

    const ambient = new THREE.HemisphereLight(0xfff1c9, 0x111827, 1.35);
    nextScene.add(ambient);

    const key = new THREE.DirectionalLight(0xffd38a, 3.2);
    key.position.set(-4, 8, 6);
    key.castShadow = true;
    key.shadow.mapSize.set(1024, 1024);
    nextScene.add(key);

    const rim = new THREE.DirectionalLight(0x93c5fd, 1.5);
    rim.position.set(5, 3, -4);
    nextScene.add(rim);

    const floor = new THREE.Mesh(
        new THREE.CircleGeometry(7.8, 64),
        new THREE.MeshStandardMaterial({
            color: 0x120f0c,
            roughness: 0.86,
            metalness: 0.05,
            transparent: true,
            opacity: 0.68,
        }),
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.y = -1.06;
    floor.receiveShadow = true;
    nextScene.add(floor);

    renderer.value = nextRenderer;
    scene.value = nextScene;
    camera.value = nextCamera;
};

const resizeRenderer = () => {
    if (!canvasHost.value || !renderer.value || !camera.value) return;

    const rect = canvasHost.value.getBoundingClientRect();
    clockState.width = Math.max(1, rect.width);
    clockState.height = Math.max(1, rect.height);
    renderer.value.setSize(clockState.width, clockState.height, false);
    camera.value.aspect = clockState.width / clockState.height;
    camera.value.updateProjectionMatrix();
};

const createDice = () => {
    if (!scene.value || !props.animation) return;

    const count = props.animation.dice.length;
    const spread = Math.min(2.25, 7.2 / Math.max(1, count));

    props.animation.dice.forEach((die, index) => {
        const typePalette = palette[props.animation.diceType] ?? palette.d20;
        const geometry = geometryFor(props.animation.diceType);
        const material = new THREE.MeshStandardMaterial({
            color: typePalette.color,
            emissive: typePalette.emissive,
            emissiveIntensity: 0.22,
            metalness: 0.18,
            roughness: 0.48,
            flatShading: true,
        });
        const mesh = new THREE.Mesh(geometry, material);
        mesh.castShadow = true;
        mesh.receiveShadow = true;

        const edges = new THREE.LineSegments(
            new THREE.EdgesGeometry(geometry, 12),
            new THREE.LineBasicMaterial({
                color: 0xfff2c2,
                transparent: true,
                opacity: 0.32,
            }),
        );

        const label = makeTextSprite(die.value, props.animation.diceType === 'd100' ? 78 : 96);
        label.position.set(0, 1.75, 0);

        const group = new THREE.Group();
        group.add(mesh);
        group.add(edges);
        group.add(label);

        const lane = index - (count - 1) / 2;
        const seed = dieSeed(die);
        const startX = -5.6 + seeded(seed) * 1.4;
        const startZ = -1.8 + seeded(seed + 2) * 3.6;
        const finalX = lane * spread + (seeded(seed + 4) - 0.5) * 0.44;
        const finalZ = (seeded(seed + 5) - 0.5) * 2.5;
        const arc = 4.4 + seeded(seed + 7) * 1.8;
        const delay = index * 125;
        const spin = new THREE.Vector3(
            8.2 + seeded(seed + 10) * 4.5,
            10.5 + seeded(seed + 11) * 5.2,
            7.4 + seeded(seed + 12) * 4.8,
        );
        const finalRotation = finalRotationFor(props.animation.diceType, die.value);

        group.position.set(startX, 2.8 + index * 0.16, startZ);
        scene.value.add(group);

        diceMeshes.push({
            group,
            mesh,
            edges,
            label,
            start: new THREE.Vector3(startX, 2.8 + index * 0.16, startZ),
            final: new THREE.Vector3(finalX, -0.1, finalZ),
            arc,
            delay,
            spin,
            finalRotation,
            bouncePhase: seeded(seed + 15) * Math.PI,
            scale: 0.92 + seeded(seed + 16) * 0.14,
        });

        group.scale.setScalar(diceMeshes[diceMeshes.length - 1].scale);
    });
};

const easeOutCubic = (value) => 1 - Math.pow(1 - value, 3);
const easeOutBack = (value) => {
    const c1 = 1.70158;
    const c3 = c1 + 1;

    return 1 + c3 * Math.pow(value - 1, 3) + c1 * Math.pow(value - 1, 2);
};

const animateDice = (timestamp) => {
    if (!renderer.value || !scene.value || !camera.value || !props.animation) {
        return;
    }

    const elapsed = timestamp - clockState.startedAt;
    visibleTotal.value = elapsed >= SHOW_VALUES_AT;

    diceMeshes.forEach((entry) => {
        const local = Math.max(0, elapsed - entry.delay);
        const t = Math.min(1, local / ROLL_DURATION);
        const travel = easeOutCubic(Math.min(1, t / 0.78));
        const settle = Math.max(0, (t - 0.72) / 0.28);

        const x = THREE.MathUtils.lerp(entry.start.x, entry.final.x, travel);
        const z = THREE.MathUtils.lerp(entry.start.z, entry.final.z, travel);
        const energy = Math.max(0, 1 - t);
        const ballistic = Math.sin(Math.min(1, t) * Math.PI) * entry.arc * energy;
        const bounce = Math.abs(Math.sin((t * 5.6 + entry.bouncePhase) * Math.PI)) * energy * 0.9;
        const y = entry.final.y + ballistic + bounce;

        entry.group.position.set(x, y, z);

        const wobble = Math.sin(t * Math.PI * 8 + entry.bouncePhase) * energy * 0.28;
        entry.group.rotation.set(
            entry.spin.x * t + wobble,
            entry.spin.y * t - wobble * 0.7,
            entry.spin.z * t + wobble * 0.45,
        );

        if (settle > 0) {
            const easedSettle = easeOutBack(Math.min(1, settle));
            entry.group.rotation.x = THREE.MathUtils.lerp(entry.group.rotation.x, entry.finalRotation.x, easedSettle);
            entry.group.rotation.y = THREE.MathUtils.lerp(entry.group.rotation.y, entry.finalRotation.y, easedSettle);
            entry.group.rotation.z = THREE.MathUtils.lerp(entry.group.rotation.z, entry.finalRotation.z, easedSettle);
            entry.group.position.y = THREE.MathUtils.lerp(entry.group.position.y, entry.final.y, easedSettle);
        }

        const labelOpacity = THREE.MathUtils.clamp((elapsed - SHOW_VALUES_AT - entry.delay) / 260, 0, 1);
        entry.label.material.opacity = labelOpacity;
        entry.label.position.y = 1.72 + Math.sin(timestamp / 520 + entry.delay) * 0.04;
    });

    renderer.value.render(scene.value, camera.value);
    clockState.frame = requestAnimationFrame(animateDice);
};

const startAnimation = async () => {
    const token = ++startToken;

    if (typeof window === 'undefined') {
        return;
    }

    if (!props.animation) {
        clearScene();
        return;
    }

    if (!THREE) {
        THREE = await import('three');
    }

    visibleTotal.value = false;
    await nextTick();
    if (token !== startToken || !props.animation || !canvasHost.value) {
        return;
    }

    setupRenderer();
    resizeRenderer();
    createDice();
    clockState.startedAt = performance.now();
    clockState.frame = requestAnimationFrame(animateDice);
};

watch(() => props.animation?.id, startAnimation, { immediate: true });

onMounted(() => {
    window.addEventListener('resize', resizeRenderer);
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', resizeRenderer);
    }

    clearScene();
});
</script>

<template>
    <div class="pointer-events-none fixed inset-0 z-[72] overflow-hidden">
        <Transition name="dice-roll-shell">
            <section
                v-if="animation"
                :key="animation.id"
                class="dice-roll-stage"
            >
                <div class="dice-roll-glow" />
                <div ref="canvasHost" class="dice-roll-canvas" />

                <article class="dice-roll-summary">
                    <p class="dice-roll-actor">{{ animation.actorName || 'Roll' }}</p>
                    <div class="dice-roll-total-row">
                        <span>{{ animation.formula }}</span>
                        <strong v-if="visibleTotal">{{ animation.total }}</strong>
                    </div>
                    <p v-if="visibleTotal" class="dice-roll-values">
                        {{ animation.rollValues.join(' + ') }}<span v-if="animation.modifier"> {{ animation.modifier > 0 ? '+' : '' }} {{ animation.modifier }}</span>
                    </p>
                    <p v-if="sourceLabel && visibleTotal" class="dice-roll-source">{{ sourceLabel }}</p>
                </article>
            </section>
        </Transition>
    </div>
</template>

<style scoped>
.dice-roll-stage {
    position: fixed;
    inset: 0;
    display: grid;
    place-items: center;
}

.dice-roll-glow {
    position: absolute;
    left: 50%;
    top: 48%;
    width: min(38rem, 78vw);
    height: min(26rem, 56vh);
    transform: translate(-50%, -50%);
    border-radius: 999px;
    background:
        radial-gradient(circle at 50% 44%, rgba(251, 191, 36, 0.2), transparent 58%),
        radial-gradient(circle at 34% 62%, rgba(45, 212, 191, 0.12), transparent 42%);
    filter: blur(12px);
}

.dice-roll-canvas {
    position: absolute;
    inset: 0;
}

.dice-roll-canvas :deep(canvas) {
    display: block;
    width: 100%;
    height: 100%;
}

.dice-roll-summary {
    position: absolute;
    bottom: 5.5rem;
    left: 50%;
    z-index: 2;
    width: min(30rem, calc(100vw - 2.5rem));
    transform: translateX(-50%);
    border-radius: 0.65rem;
    border: 1px solid rgba(251, 191, 36, 0.24);
    background: rgba(12, 10, 9, 0.72);
    padding: 0.9rem 1.2rem;
    text-align: center;
    box-shadow: 0 1.4rem 4rem rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(10px);
}

.dice-roll-actor {
    color: #fde68a;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.dice-roll-total-row {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 1rem;
    margin-top: 0.28rem;
    color: #e7e5e4;
    font-weight: 800;
}

.dice-roll-total-row span {
    font-size: 1rem;
    text-transform: uppercase;
}

.dice-roll-total-row strong {
    color: #fffbeb;
    font-size: 2.5rem;
    line-height: 1;
}

.dice-roll-values,
.dice-roll-source {
    margin-top: 0.25rem;
    color: #d6d3d1;
    font-size: 0.82rem;
}

.dice-roll-source {
    color: #a7f3d0;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.dice-roll-shell-enter-active,
.dice-roll-shell-leave-active {
    transition: opacity 220ms ease;
}

.dice-roll-shell-enter-from,
.dice-roll-shell-leave-to {
    opacity: 0;
}

@media (max-width: 640px) {
    .dice-roll-summary {
        bottom: 8rem;
    }
}
</style>
