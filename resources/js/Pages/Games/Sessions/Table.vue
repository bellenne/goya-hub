<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useGmSessionPresence } from '@/Composables/useGmSessionPresence';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    session: { type: Object, required: true },
    scene: { type: Object, required: true },
    rolls: { type: Object, required: true },
    inventory: { type: Object, required: true },
    can_manage_sessions: { type: Boolean, required: true },
});

const page = usePage();
const normalizeBoolean = (value, fallback = false) => {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value !== 0;
    }

    const normalized = String(value).toLowerCase();

    if (['1', 'true', 'on', 'yes'].includes(normalized)) {
        return true;
    }

    if (['0', 'false', 'off', 'no'].includes(normalized)) {
        return false;
    }

    return fallback;
};
const diceOptions = ['d4', 'd6', 'd8', 'd10', 'd12', 'd20'];
let sceneChannel = null;
let rollsChannel = null;
let inventoryChannel = null;
let animationTimeout = null;
let rollResultTimeout = null;
let contextMenuTimeout = null;
let lifecycleNoticeTimeout = null;
const rollLogTimeouts = new Map();

const sceneForm = useForm({
    background_id: props.scene.controls.current_background_id ?? '',
    visible_npc_ids: props.scene.controls.visible_npc_ids ?? [],
    encountered_npc_ids: props.scene.controls.encountered_npc_ids ?? props.scene.controls.visible_npc_ids ?? [],
    present_npc_ids: props.scene.controls.present_npc_ids ?? props.scene.controls.visible_npc_ids ?? [],
    hidden_character_ids: props.scene.controls.hidden_character_ids ?? [],
    npc_scene_quantities: props.scene.controls.npc_scene_quantities ?? {},
    speaker_type: props.scene.speaker?.type ?? '',
    speaker_id: props.scene.speaker?.id ?? '',
});

const diceForm = useForm({
    dice_count: 1,
    dice_type: 'd20',
    modifier: 0,
    source_type: '',
    source_id: '',
    attribute_key: '',
});

const makeInventoryForms = (characters) => Object.fromEntries(characters.map((character) => [
    character.id,
    useForm({ item_id: '', quantity: 1, back_to_session_id: props.session.id }),
]));

const makeCustomForms = (characters) => Object.fromEntries(characters.map((character) => [
    character.id,
    useForm({
        custom_name: '',
        custom_description: '',
        custom_image: null,
        quantity: 1,
        back_to_session_id: props.session.id,
    }),
]));

const makeQuantityForms = (characters) => {
    const forms = {};
    characters.forEach((character) => {
        character.inventory_items.forEach((item) => {
            forms[item.id] = useForm({ quantity: item.quantity, back_to_session_id: props.session.id });
        });
    });
    return forms;
};

const inventoryForms = ref(makeInventoryForms(props.inventory.characters));
const customInventoryForms = ref(makeCustomForms(props.inventory.characters));
const quantityForms = ref(makeQuantityForms(props.inventory.characters));
const rollingText = ref('');
const highlightedRollId = ref(null);
const showCharacterModal = ref(false);
const characterModalTab = ref('stats');
const selectedCharacter = ref(props.scene.own_character);
const showDiceModal = ref(false);
const selectedDiceType = ref('d20');
const rollResultOverlay = ref(null);
const rollLogItems = ref([]);
const showNpcLibraryModal = ref(false);
const showBackgroundUploadModal = ref(false);
const backgroundSearch = ref('');
const npcSearch = ref('');
const hoveredContext = ref(null);
const showImagePreviewModal = ref(false);
const previewImageEntity = ref(null);
const chatItems = ref([]);
const chatMessage = ref('');
const isChatExpanded = ref(false);
const isChatHovered = ref(false);
const isChatInputFocused = ref(false);
const chatScroll = ref(null);
const npcAddQuantity = ref(1);
const npcAddGroup = ref(false);
const backgroundForm = useForm({
    title: '',
    image: null,
    back_to_session_id: props.session.id,
    apply_to_session: true,
});
const gmSheetForm = useForm({
    attribute_values: {},
    skill_values: {},
    extra_field_values: {},
    back_to_session_id: props.session.id,
});
const lifecycleNotice = ref(props.session.status === 'gm_disconnected_grace'
    ? {
        tone: 'warning',
        title: 'GM вышел из session page',
        text: 'Если GM не вернётся в течение 5 минут, сессия завершится.',
        gm_grace_ends_at: props.session.gm_grace_ends_at,
    }
    : null);

const allSpeakers = computed(() => [
    ...props.scene.controls.speakers.characters.map((speaker) => ({ ...speaker, type: 'character', label: `${speaker.name} · персонаж` })),
    ...props.scene.controls.speakers.npcs.map((speaker) => ({ ...speaker, type: 'npc', label: `${speaker.name} · NPC` })),
]);

const currentSpeakerValue = computed(() => (
    sceneForm.speaker_type && sceneForm.speaker_id ? `${sceneForm.speaker_type}:${sceneForm.speaker_id}` : ''
));

const latestRoll = computed(() => props.rolls.items[0] ?? null);
const presentSceneNpcs = computed(() => props.scene.present_npcs ?? props.scene.visible_npcs ?? []);
const encounteredSceneNpcs = computed(() => props.scene.encountered_npcs ?? props.scene.visible_npcs ?? []);
const alliedNpcs = computed(() => presentSceneNpcs.value.filter((npc) => npc.type === 'ally'));
const encounteredNpcs = computed(() => encounteredSceneNpcs.value.filter((npc) => !(npc.type === 'ally' && npc.is_present)));
const enemyNpcs = computed(() => presentSceneNpcs.value.filter((npc) => npc.type === 'enemy'));
const topEncounteredNpcs = computed(() => encounteredSceneNpcs.value.filter((npc) => npc.type !== 'enemy' && !(npc.type === 'ally' && npc.is_present)));
const filteredBackgrounds = computed(() => props.scene.controls.backgrounds.filter((background) => (
    background.title.toLowerCase().includes(backgroundSearch.value.toLowerCase())
)));
const filteredNpcs = computed(() => props.scene.controls.npcs.filter((npc) => (
    npc.name.toLowerCase().includes(npcSearch.value.toLowerCase())
)));
const npcSceneQuantities = computed(() => props.scene.controls.npc_scene_quantities ?? {});
const ownInventoryItems = computed(() => props.inventory.own_character?.inventory_items ?? []);
const activeCharacter = computed(() => selectedCharacter.value ?? props.scene.own_character);
const activeCharacterInventory = computed(() => {
    if (!activeCharacter.value) return [];
    return props.inventory.characters.find((character) => character.id === activeCharacter.value.id)?.inventory_items
        ?? ownInventoryItems.value;
});
const ownCharacterTemplate = computed(() => activeCharacter.value?.template ?? null);
const isSpeaking = (type, id, sceneNpcId = null) => {
    if (props.scene.speaker?.type !== type) return false;
    if (type === 'npc' && sceneNpcId !== null) {
        return props.scene.speaker?.scene_npc_id === sceneNpcId;
    }

    return props.scene.speaker?.id === id;
};

const templateItems = (section) => ownCharacterTemplate.value?.[section]?.items ?? [];
const skillTemplateItems = computed(() => templateItems('skills').flatMap((skill) => [skill, ...(skill.subskills ?? [])]));
const extraTemplateItems = computed(() => ownCharacterTemplate.value?.extra_fields ?? []);
const playerRollActor = computed(() => {
    if (!props.scene.own_character) return null;

    return {
        source_type: 'character',
        source_id: props.scene.own_character.id,
        name: props.scene.own_character.name,
        attribute_values: props.scene.own_character.attribute_values ?? {},
        rollable_attributes: props.scene.own_character.rollable_attributes ?? [],
    };
});
const visibleSceneRollActors = computed(() => {
    const actors = new Map();

    [...encounteredSceneNpcs.value, ...presentSceneNpcs.value].forEach((npc) => {
        actors.set(Number(npc.id), {
            source_type: 'scene_npc',
            source_id: npc.id,
            name: npc.name,
            attribute_values: npc.attribute_values ?? {},
            rollable_attributes: npc.rollable_attributes ?? [],
            has_character_sheet: npc.has_character_sheet,
        });
    });

    return [...actors.values()];
});
const gmRollActors = computed(() => [
    ...(playerRollActor.value ? [playerRollActor.value] : []),
    ...visibleSceneRollActors.value,
]);
const availableRollActors = computed(() => props.can_manage_sessions ? gmRollActors.value : (playerRollActor.value ? [playerRollActor.value] : []));
const currentRollSourceValue = computed(() => (
    diceForm.source_type && diceForm.source_id ? `${diceForm.source_type}:${diceForm.source_id}` : ''
));
const selectedRollActor = computed(() => availableRollActors.value.find((actor) => (
    actor.source_type === diceForm.source_type && Number(actor.source_id) === Number(diceForm.source_id)
)) ?? null);
const selectedRollAttribute = computed(() => selectedRollActor.value?.rollable_attributes?.find((item) => item.key === diceForm.attribute_key) ?? null);
const selectedAttributeModifier = computed(() => {
    if (!selectedRollActor.value || !selectedRollAttribute.value) return 0;

    return Number(
        selectedRollActor.value.attribute_values?.[selectedRollAttribute.value.key]
        ?? selectedRollAttribute.value.default
        ?? 0,
    );
});
const finalRollModifier = computed(() => Number(diceForm.modifier || 0) + selectedAttributeModifier.value);
const selectedRollAttributeLabel = computed(() => {
    if (!selectedRollAttribute.value) return 'Без характеристики';

    const modifier = selectedAttributeModifier.value;
    return `${selectedRollAttribute.value.label} (${modifier >= 0 ? '+' : ''}${modifier})`;
});
const selectedRollAttributes = computed(() => selectedRollActor.value?.rollable_attributes ?? []);
const currentUser = computed(() => page.props.auth?.user ?? null);
const characterValue = (values, item) => values?.[item.key] ?? item.default ?? '';
const skillValue = (values, item) => normalizeBoolean(values?.[item.key], normalizeBoolean(item.default)) ? 'Есть' : 'Нет';
const numberIds = (ids) => [...new Set((ids ?? []).map(Number))];
const hasNpcState = (ids, npcId) => numberIds(ids).includes(Number(npcId));
const isCharacterHidden = (characterId) => numberIds(sceneForm.hidden_character_ids).includes(Number(characterId));
const signedNumber = (value) => `${Number(value) >= 0 ? '+' : ''}${Number(value)}`;
const baseRollNotation = (roll) => `${roll.dice_count}${roll.dice_type}`;
const rollFormulaText = (roll) => {
    const parts = [baseRollNotation(roll)];

    if (roll.attribute_label) {
        parts.push(`+ (х-ка ${Number(roll.attribute_modifier ?? 0)})`);
    }

    if (Number(roll.manual_modifier ?? 0) !== 0) {
        parts.push(`+ (М ${Number(roll.manual_modifier ?? 0)})`);
    }

    return parts.join(' ');
};
const rollActorName = (roll) => roll.actor_name ?? roll.user.name;
const rollPerformedByLabel = (roll) => (
    roll.actor_name && roll.actor_name !== roll.user.name ? `Бросок выполнил ${roll.user.name}` : ''
);
const rollAttributeText = (roll) => (
    roll.attribute_label ? `${roll.attribute_label} ${signedNumber(roll.attribute_modifier ?? 0)}` : ''
);
const rollManualModifierText = (roll) => (
    Number(roll.manual_modifier ?? 0) !== 0 ? `Ручной модификатор ${signedNumber(roll.manual_modifier ?? 0)}` : ''
);
const rollBreakdownText = (roll) => {
    const parts = [`${rollFormulaText(roll)} -> значения: ${roll.roll_values.join(', ')}`];

    if (roll.attribute_label) {
        parts.push(rollAttributeText(roll));
    }

    if (Number(roll.manual_modifier ?? 0) !== 0) {
        parts.push(rollManualModifierText(roll));
    }

    return parts.join(' · ');
};

const applyDefaultRollSource = () => {
    if (props.can_manage_sessions) {
        if (!diceForm.source_type && playerRollActor.value) {
            diceForm.source_type = playerRollActor.value.source_type;
            diceForm.source_id = playerRollActor.value.source_id;
        }
    } else if (playerRollActor.value) {
        diceForm.source_type = playerRollActor.value.source_type;
        diceForm.source_id = playerRollActor.value.source_id;
    }

    if (!selectedRollActor.value) {
        diceForm.attribute_key = '';
    }
};

const updateRollSource = (value) => {
    if (!value) {
        diceForm.source_type = '';
        diceForm.source_id = '';
        diceForm.attribute_key = '';
        return;
    }

    const [sourceType, sourceId] = value.split(':');
    diceForm.source_type = sourceType;
    diceForm.source_id = Number(sourceId);
    diceForm.attribute_key = '';
};

const openCharacterModal = (tab = 'stats', character = props.scene.own_character) => {
    selectedCharacter.value = character;
    characterModalTab.value = tab;
    if (props.can_manage_sessions && character) {
        gmSheetForm.attribute_values = Object.fromEntries(templateItems('attributes').map((item) => [
            item.key,
            character.attribute_values?.[item.key] ?? item.default ?? 0,
        ]));
        gmSheetForm.skill_values = Object.fromEntries(skillTemplateItems.value.map((item) => [
            item.key,
            normalizeBoolean(character.skill_values?.[item.key], normalizeBoolean(item.default)),
        ]));
        gmSheetForm.extra_field_values = Object.fromEntries(extraTemplateItems.value.map((item) => [
            item.key,
            character.extra_field_values?.[item.key] ?? item.default ?? (item.type === 'number' ? 0 : ''),
        ]));
    }
    showCharacterModal.value = true;
};

const showContextMenu = (event, kind, entity, viewerOnly = false) => {
    const rect = event.currentTarget.getBoundingClientRect();
    hoveredContext.value = {
        kind,
        entity,
        viewerOnly,
        x: kind === 'enemy' ? rect.left - 190 : rect.right + 8,
        y: Math.min(rect.top, window.innerHeight - 220),
    };
};

const hideContextMenu = () => {
    if (contextMenuTimeout) clearTimeout(contextMenuTimeout);
    contextMenuTimeout = setTimeout(() => {
        hoveredContext.value = null;
    }, 120);
};

const keepContextMenu = () => {
    if (contextMenuTimeout) clearTimeout(contextMenuTimeout);
};

const canPreviewImage = (entity) => Boolean(entity?.avatar_url);

const openImagePreview = (entity) => {
    if (!canPreviewImage(entity)) return;

    previewImageEntity.value = entity;
    showImagePreviewModal.value = true;
    hoveredContext.value = null;
};

const scrollChatToBottom = () => {
    nextTick(() => {
        if (!chatScroll.value) return;
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
    });
};

const appendChatMessage = (payload) => {
    if (!payload?.id || !payload?.user_name || !payload?.message) {
        return;
    }

    if (chatItems.value.some((item) => item.id === payload.id)) {
        return;
    }

    chatItems.value = [
        ...chatItems.value,
        {
            id: payload.id,
            user_name: payload.user_name,
            message: payload.message,
            sent_at: payload.sent_at ?? new Date().toISOString(),
        },
    ].slice(-80);

    scrollChatToBottom();
};

const sendChatMessage = () => {
    const message = String(chatMessage.value ?? '').trim().slice(0, 1000);

    if (!message || !currentUser.value) {
        return;
    }

    const payload = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`,
        user_name: currentUser.value.name,
        message,
        sent_at: new Date().toISOString(),
    };

    appendChatMessage(payload);
    sceneChannel?.whisper?.('session-chat', payload);
    chatMessage.value = '';
    isChatExpanded.value = true;
};

const handleChatMouseEnter = () => {
    isChatHovered.value = true;
};

const handleChatMouseLeave = () => {
    isChatHovered.value = false;

    if (!isChatInputFocused.value) {
        isChatExpanded.value = false;
    }
};

const handleChatInputFocus = () => {
    isChatInputFocused.value = true;
    isChatExpanded.value = true;
};

const handleChatInputBlur = () => {
    isChatInputFocused.value = false;

    if (!isChatHovered.value) {
        isChatExpanded.value = false;
    }
};

const openDiceModal = (diceType) => {
    selectedDiceType.value = diceType;
    diceForm.dice_type = diceType;
    diceForm.dice_count = 1;
    diceForm.modifier = 0;
    diceForm.attribute_key = '';
    applyDefaultRollSource();
    showDiceModal.value = true;
};

const syncInventoryForms = (characters) => {
    inventoryForms.value = makeInventoryForms(characters);
    customInventoryForms.value = makeCustomForms(characters);
    quantityForms.value = makeQuantityForms(characters);
};

const animateRoll = (roll) => {
    if (!roll) return;
    highlightedRollId.value = roll.id;
    rollingText.value = `${roll.actor_name ?? roll.user.name} бросает ${rollFormulaText(roll)}`;
    rollResultOverlay.value = roll;
    rollLogItems.value = [
        roll,
        ...rollLogItems.value.filter((item) => item.id !== roll.id),
    ].slice(0, 8);

    if (animationTimeout) clearTimeout(animationTimeout);
    animationTimeout = setTimeout(() => {
        highlightedRollId.value = null;
        rollingText.value = '';
    }, 1500);

    if (rollResultTimeout) clearTimeout(rollResultTimeout);
    if (contextMenuTimeout) clearTimeout(contextMenuTimeout);
    rollResultTimeout = setTimeout(() => {
        rollResultOverlay.value = null;
    }, 10000);

    if (rollLogTimeouts.has(roll.id)) clearTimeout(rollLogTimeouts.get(roll.id));
    rollLogTimeouts.set(roll.id, setTimeout(() => {
        rollLogItems.value = rollLogItems.value.filter((item) => item.id !== roll.id);
        rollLogTimeouts.delete(roll.id);
    }, 30000));
};

const refreshScene = () => router.reload({ only: ['scene', 'session', 'can_manage_sessions'], preserveScroll: true });
const refreshRolls = () => router.reload({
    only: ['rolls'],
    preserveScroll: true,
    onSuccess: (pageData) => animateRoll(pageData.props.rolls?.items?.[0] ?? null),
});
const refreshInventory = () => router.reload({
    only: ['inventory'],
    preserveScroll: true,
    onSuccess: (pageData) => syncInventoryForms(pageData.props.inventory?.characters ?? []),
});

const handleLifecycle = (payload) => {
    if (lifecycleNoticeTimeout) {
        clearTimeout(lifecycleNoticeTimeout);
        lifecycleNoticeTimeout = null;
    }

    if (['connected', 'heartbeat'].includes(payload.event) && payload.status !== 'gm_disconnected_grace' && payload.status !== 'ended') {
        if (lifecycleNotice.value?.tone === 'warning') {
            lifecycleNotice.value = null;
        }

        return;
    }

    if (payload.event === 'gm_disconnected' || payload.status === 'gm_disconnected_grace') {
        lifecycleNotice.value = {
            tone: 'warning',
            title: 'GM вышел из session page',
            text: 'Если GM не вернётся в течение 5 минут, сессия завершится.',
            gm_grace_ends_at: payload.gm_grace_ends_at,
        };
        router.reload({ only: ['session'], preserveScroll: true });
        return;
    }

    if (payload.event === 'gm_returned') {
        lifecycleNotice.value = {
            tone: 'success',
            title: 'GM вернулся',
            text: 'Сессия продолжается.',
        };
        lifecycleNoticeTimeout = setTimeout(() => {
            lifecycleNotice.value = null;
        }, 5000);
        router.reload({ only: ['session'], preserveScroll: true });
        return;
    }

    if (payload.event === 'ended' || payload.status === 'ended') {
        lifecycleNotice.value = {
            tone: 'danger',
            title: 'Сессия завершена',
            text: 'GM не вернулся за 5 минут. Вы будете перенаправлены к списку сессий.',
        };
        setTimeout(() => router.visit(route('games.sessions.index', props.game.id)), 2500);
    }
};

useGmSessionPresence({
    enabled: props.can_manage_sessions && props.session.status !== 'ended',
    gameId: props.game.id,
    sessionId: props.session.id,
    onStatus: handleLifecycle,
});

const submitScene = () => {
    sceneForm.transform((data) => ({
        ...data,
        background_id: data.background_id || null,
        visible_npc_ids: numberIds(data.present_npc_ids ?? data.visible_npc_ids),
        present_npc_ids: numberIds(data.present_npc_ids ?? data.visible_npc_ids),
        encountered_npc_ids: numberIds([
            ...(data.encountered_npc_ids ?? []),
            ...(data.present_npc_ids ?? data.visible_npc_ids ?? []),
        ]),
        hidden_character_ids: numberIds(data.hidden_character_ids),
        npc_scene_quantities: data.npc_scene_quantities ?? {},
        speaker_type: data.speaker_type || null,
        speaker_id: data.speaker_id || null,
    })).patch(route('games.sessions.scene.update', [props.game.id, props.session.id]), {
        preserveScroll: true,
        preserveState: true,
    });
};

const updateSceneQuick = (overrides = {}) => {
    Object.assign(sceneForm, overrides);
    submitScene();
};

const patchSceneOnly = (payload) => {
    router.patch(route('games.sessions.scene.update', [props.game.id, props.session.id]), payload, {
        preserveScroll: true,
        preserveState: true,
        only: ['scene'],
    });
};

const setSceneBackground = (backgroundId) => {
    sceneForm.background_id = backgroundId ?? '';
    patchSceneOnly({ background_id: backgroundId || null });
};

const setBackgroundImage = (event) => {
    backgroundForm.image = event.target.files[0] ?? null;
};

const submitBackground = () => {
    backgroundForm.post(route('games.backgrounds.store', props.game.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        only: ['scene'],
        onSuccess: () => {
            showBackgroundUploadModal.value = false;
            backgroundForm.reset('title', 'image');
        },
    });
};

const toggleEncounteredNpc = (npcId) => {
    const encounteredIds = numberIds(sceneForm.encountered_npc_ids);
    const presentIds = numberIds(sceneForm.present_npc_ids);
    const nextEncounteredIds = encounteredIds.includes(npcId)
        ? encounteredIds.filter((id) => id !== npcId)
        : [...encounteredIds, npcId];
    const nextPresentIds = nextEncounteredIds.includes(npcId)
        ? presentIds
        : presentIds.filter((id) => id !== npcId);

    updateSceneQuick({
        encountered_npc_ids: nextEncounteredIds,
        present_npc_ids: nextPresentIds,
        visible_npc_ids: nextPresentIds,
    });
};

const togglePresentNpc = (npcId) => {
    const encounteredIds = numberIds(sceneForm.encountered_npc_ids);
    const presentIds = numberIds(sceneForm.present_npc_ids);
    const nextPresentIds = presentIds.includes(npcId)
        ? presentIds.filter((id) => id !== npcId)
        : [...presentIds, npcId];

    updateSceneQuick({
        encountered_npc_ids: [...new Set([...encounteredIds, ...nextPresentIds])],
        present_npc_ids: nextPresentIds,
        visible_npc_ids: nextPresentIds,
    });
};

const setSceneSpeaker = (type, id, sceneNpcId = null) => {
    sceneForm.speaker_type = type;
    sceneForm.speaker_id = id;
    patchSceneOnly({ speaker_type: type, speaker_id: id, speaker_scene_npc_id: sceneNpcId });
};

const clearSceneSpeaker = () => {
    sceneForm.speaker_type = '';
    sceneForm.speaker_id = '';
    patchSceneOnly({ speaker_type: null, speaker_id: null, speaker_scene_npc_id: null });
};

const toggleCharacterVisibility = (characterId) => {
    const hiddenIds = numberIds(sceneForm.hidden_character_ids);
    const nextHiddenIds = hiddenIds.includes(Number(characterId))
        ? hiddenIds.filter((id) => id !== Number(characterId))
        : [...hiddenIds, Number(characterId)];

    sceneForm.hidden_character_ids = nextHiddenIds;
    patchSceneOnly({ hidden_character_ids: nextHiddenIds });
};

const updateNpcType = (npc, type) => {
    router.patch(route('games.npcs.type.update', [props.game.id, npc.id]), {
        type,
        back_to_session_id: props.session.id,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: ['scene'],
    });
};

const setNpcSceneState = (npc, { present = true, encountered = true, sceneType = null } = {}) => {
    router.patch(route('games.sessions.scene-npcs.update', [props.game.id, props.session.id, npc.id]), {
        is_present: present,
        is_encountered: encountered,
        scene_type: sceneType ?? npc.type,
    }, {
        preserveScroll: true,
        preserveState: true,
        only: ['scene'],
    });
};

const moveNpcTo = (npc, type) => {
    setNpcSceneState(npc, { present: true, encountered: true, sceneType: type });
};

const addNpcToScene = (npc, type) => {
    const quantity = Math.max(1, Math.min(99, Number(npcAddQuantity.value || 1)));

    router.post(route('games.sessions.scene-npcs.store', [props.game.id, props.session.id]), {
        npc_id: npc.id,
        scene_type: type,
        quantity,
        create_group: Boolean(npcAddGroup.value),
    }, {
        preserveScroll: true,
        preserveState: true,
        only: ['scene'],
        onSuccess: () => {
            showNpcLibraryModal.value = false;
            npcAddQuantity.value = 1;
            npcAddGroup.value = false;
        },
    });
};

const updateGmCharacterSheet = () => {
    if (!selectedCharacter.value) return;
    gmSheetForm.patch(route('games.characters.sheet.update', [props.game.id, selectedCharacter.value.id]), {
        preserveScroll: true,
        preserveState: true,
        only: ['scene', 'inventory'],
    });
};

const submitRoll = () => {
    applyDefaultRollSource();

    diceForm.transform((data) => ({
        ...data,
        modifier: Number(data.modifier || 0),
        source_type: data.source_type || null,
        source_id: data.source_id || null,
        attribute_key: data.attribute_key || null,
    }))
        .post(route('games.sessions.dice-rolls.store', [props.game.id, props.session.id]), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (pageData) => {
                showDiceModal.value = false;
                diceForm.attribute_key = '';
                animateRoll(pageData.props.rolls?.items?.[0] ?? null);
            },
        });
};

const submitCatalogItem = (characterId) => {
    inventoryForms.value[characterId].post(route('games.characters.inventory.store', [props.game.id, characterId]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => inventoryForms.value[characterId].reset('item_id', 'quantity'),
    });
};

const submitCustomItem = (characterId) => {
    customInventoryForms.value[characterId].post(route('games.characters.inventory.store', [props.game.id, characterId]), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => customInventoryForms.value[characterId].reset('custom_name', 'custom_description', 'custom_image', 'quantity'),
    });
};

const setCustomImage = (characterId, event) => {
    customInventoryForms.value[characterId].custom_image = event.target.files[0] ?? null;
};

const updateInventoryQuantity = (characterId, itemId) => {
    quantityForms.value[itemId].patch(route('games.characters.inventory.update', [props.game.id, characterId, itemId]), {
        preserveScroll: true,
        preserveState: true,
    });
};

const removeInventoryItem = (characterId, itemId) => {
    useForm({ back_to_session_id: props.session.id }).delete(route('games.characters.inventory.destroy', [props.game.id, characterId, itemId]), {
        preserveScroll: true,
        preserveState: true,
    });
};

const selectSpeaker = (event) => {
    const [type, id] = event.target.value ? event.target.value.split(':') : ['', ''];
    sceneForm.speaker_type = type;
    sceneForm.speaker_id = id ? Number(id) : '';
};

watch(() => latestRoll.value?.id, (newId, oldId) => {
    if (newId && oldId && newId !== oldId) animateRoll(latestRoll.value);
});

watch(() => props.inventory.characters, (characters) => syncInventoryForms(characters));

watch(() => props.scene, (scene) => {
    sceneForm.background_id = scene.controls.current_background_id ?? '';
    sceneForm.visible_npc_ids = scene.controls.visible_npc_ids ?? [];
    sceneForm.encountered_npc_ids = scene.controls.encountered_npc_ids ?? scene.controls.visible_npc_ids ?? [];
    sceneForm.present_npc_ids = scene.controls.present_npc_ids ?? scene.controls.visible_npc_ids ?? [];
    sceneForm.hidden_character_ids = scene.controls.hidden_character_ids ?? [];
    sceneForm.npc_scene_quantities = scene.controls.npc_scene_quantities ?? {};
    sceneForm.speaker_type = scene.speaker?.type ?? '';
    sceneForm.speaker_id = scene.speaker?.id ?? '';
    applyDefaultRollSource();
});

watch(availableRollActors, () => {
    applyDefaultRollSource();

    if (
        diceForm.attribute_key
        && !selectedRollActor.value?.rollable_attributes?.some((item) => item.key === diceForm.attribute_key)
    ) {
        diceForm.attribute_key = '';
    }
}, { deep: true });

watch(showNpcLibraryModal, (isOpen) => {
    if (!isOpen) {
        npcAddQuantity.value = 1;
        npcAddGroup.value = false;
    }
});

onMounted(() => {
    if (!window.Echo) return;
    sceneChannel = window.Echo.private(props.scene.channel)
        .listen('.session.scene.updated', refreshScene)
        .listen('.session.lifecycle.updated', handleLifecycle)
        .listenForWhisper('session-chat', appendChatMessage);
    rollsChannel = window.Echo.private(props.rolls.channel).listen('.session.dice.rolled', refreshRolls);
    inventoryChannel = window.Echo.private(props.inventory.channel).listen('.session.inventory.updated', refreshInventory);
});

onBeforeUnmount(() => {
    if (animationTimeout) clearTimeout(animationTimeout);
    if (rollResultTimeout) clearTimeout(rollResultTimeout);
    if (lifecycleNoticeTimeout) clearTimeout(lifecycleNoticeTimeout);
    rollLogTimeouts.forEach((timeout) => clearTimeout(timeout));
    if (!window.Echo) return;
    if (sceneChannel) window.Echo.leave(props.scene.channel);
    if (rollsChannel) window.Echo.leave(props.rolls.channel);
    if (inventoryChannel) window.Echo.leave(props.inventory.channel);
});
</script>

<template>
    <Head :title="`${session.title} Table`" />

    <AuthenticatedLayout>
        <div
            v-if="lifecycleNotice"
            class="fixed left-1/2 top-5 z-[80] w-[min(34rem,calc(100vw-2rem))] -translate-x-1/2 rounded-xl border px-5 py-4 text-sm shadow-[0_20px_80px_rgba(0,0,0,0.45)] backdrop-blur-md"
            :class="{
                'border-amber-400/40 bg-amber-500/15 text-amber-50': lifecycleNotice.tone === 'warning',
                'border-emerald-400/40 bg-emerald-500/15 text-emerald-50': lifecycleNotice.tone === 'success',
                'border-red-400/40 bg-red-500/15 text-red-50': lifecycleNotice.tone === 'danger',
            }"
        >
            <p class="font-semibold">{{ lifecycleNotice.title }}</p>
            <p class="mt-1">{{ lifecycleNotice.text }}</p>
            <p v-if="lifecycleNotice.gm_grace_ends_at" class="mt-1 text-xs opacity-80">
                Grace до {{ new Date(lifecycleNotice.gm_grace_ends_at).toLocaleTimeString() }}
            </p>
        </div>

        <template #header>
            <div v-if="can_manage_sessions" class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="fantasy-kicker">{{ can_manage_sessions ? 'GM table' : 'Player table' }}</p>
                    <h1 class="fantasy-title">{{ session.title }}</h1>
                    <p class="fantasy-subtitle mt-1">{{ game.name }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="fantasy-chip">{{ session.status_label }}</span>
                    <span class="fantasy-chip-muted">{{ session.participants.length }} at table</span>
                    <Link :href="route('games.sessions.index', game.id)">
                        <SecondaryButton>К списку сессий</SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div v-if="!can_manage_sessions" class="fixed inset-0 z-50 overflow-hidden bg-stone-950 text-stone-100">
            <img v-if="scene.background?.image_url" :src="scene.background.image_url" :alt="scene.background.title" class="absolute inset-0 h-full w-full object-cover" />
            <div v-else class="absolute inset-0 bg-[radial-gradient(circle_at_50%_36%,rgba(245,158,11,0.20),transparent_18rem),linear-gradient(135deg,rgba(68,64,60,0.96),rgba(15,23,20,0.98))]" />
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0,rgba(0,0,0,0.12)_38%,rgba(0,0,0,0.68)_100%)]" />
            <div class="absolute inset-0 bg-gradient-to-b from-stone-950/60 via-transparent to-stone-950/82" />

            <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex justify-center px-8 pt-5">
                <div class="pointer-events-auto flex max-w-5xl gap-3 overflow-hidden rounded-lg border border-amber-300/20 bg-stone-950/45 px-4 py-3 shadow-[0_18px_60px_rgba(0,0,0,0.42)] backdrop-blur-md">
                    <article v-for="npc in topEncounteredNpcs" :key="`hud-met-${npc.id}`" class="flex w-28 flex-col items-center gap-2 rounded-lg border border-stone-600/35 bg-stone-950/45 p-2 text-center" :class="isSpeaking('npc', npc.npc_id, npc.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'npc', npc, true)" @mouseleave="hideContextMenu">
                        <div v-if="npc.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-amber-300/20 bg-stone-900/60 p-1">
                            <img :src="npc.avatar_url" :alt="npc.name" class="max-h-[4.5rem] max-w-[4.5rem] rounded-md object-contain" />
                        </div>
                        <div v-else class="grid h-[4.5rem] w-[4.5rem] place-items-center rounded-lg border border-amber-300/20 bg-stone-900 text-sm font-semibold text-amber-100">{{ npc.name?.charAt(0) }}</div>
                        <p class="line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ npc.name }}</p>
                    </article>
                    <p v-if="topEncounteredNpcs.length === 0" class="px-4 py-3 text-sm text-stone-400">Встреченных NPC пока нет.</p>
                </div>
            </div>

            <aside class="pointer-events-none absolute bottom-32 left-5 top-28 z-10 flex w-32 flex-col justify-center gap-3 xl:left-8 xl:w-36">
                <article v-if="scene.own_character" class="pointer-events-auto rounded-lg border border-amber-300/25 bg-stone-950/55 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.38)] backdrop-blur-md" :class="[isSpeaking('character', scene.own_character.id) ? 'ring-2 ring-amber-300/70' : '', isCharacterHidden(scene.own_character.id) ? 'opacity-65' : '']" @mouseenter="showContextMenu($event, 'character', scene.own_character, true)" @mouseleave="hideContextMenu">
                    <div v-if="scene.own_character.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-amber-300/20 bg-stone-900/60 p-1">
                        <img :src="scene.own_character.avatar_url" :alt="scene.own_character.name" class="max-h-[5.5rem] max-w-[5.5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.75rem] w-[5.75rem] place-items-center rounded-lg border border-amber-300/20 bg-stone-900 text-xl font-semibold text-amber-100">{{ scene.own_character.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ scene.own_character.name }}</p>
                    <p v-if="isCharacterHidden(scene.own_character.id)" class="mt-1 text-[10px] uppercase tracking-[0.18em] text-stone-400">hidden for players</p>
                </article>
                <article v-for="teammate in scene.teammates" :key="`hud-party-${teammate.id}`" class="pointer-events-auto rounded-lg border border-stone-600/35 bg-stone-950/50 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.32)] backdrop-blur-md" :class="isSpeaking('character', teammate.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'character', teammate, true)" @mouseleave="hideContextMenu">
                    <div v-if="teammate.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-amber-300/20 bg-stone-900/60 p-1">
                        <img :src="teammate.avatar_url" :alt="teammate.name" class="max-h-[5rem] max-w-[5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.25rem] w-[5.25rem] place-items-center rounded-lg border border-amber-300/20 bg-stone-900 text-lg font-semibold text-amber-100">{{ teammate.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ teammate.name }}</p>
                </article>
                <article v-for="npc in alliedNpcs" :key="`hud-ally-${npc.id}`" class="pointer-events-auto rounded-lg border border-emerald-300/30 bg-emerald-950/35 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.32)] backdrop-blur-md" :class="isSpeaking('npc', npc.npc_id, npc.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'npc', npc, true)" @mouseleave="hideContextMenu">
                    <div v-if="npc.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-emerald-300/20 bg-stone-900/60 p-1">
                        <img :src="npc.avatar_url" :alt="npc.name" class="max-h-[5rem] max-w-[5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.25rem] w-[5.25rem] place-items-center rounded-lg border border-emerald-300/20 bg-stone-900 text-lg font-semibold text-emerald-100">{{ npc.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ npc.name }}</p>
                </article>
            </aside>

            <aside class="pointer-events-none absolute bottom-32 right-5 top-28 z-10 flex w-32 flex-col justify-center gap-3 xl:right-8 xl:w-36">
                <article v-for="npc in enemyNpcs" :key="`hud-enemy-${npc.id}`" class="pointer-events-auto rounded-lg border border-red-300/30 bg-red-950/35 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.34)] backdrop-blur-md" :class="isSpeaking('npc', npc.npc_id, npc.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'enemy', npc, true)" @mouseleave="hideContextMenu">
                    <div v-if="npc.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-red-300/20 bg-stone-900/60 p-1">
                        <img :src="npc.avatar_url" :alt="npc.name" class="max-h-[5.5rem] max-w-[5.5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.75rem] w-[5.75rem] place-items-center rounded-lg border border-red-300/20 bg-stone-900 text-xl font-semibold text-red-100">{{ npc.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ npc.name }}</p>
                </article>
            </aside>

            <div class="pointer-events-none absolute inset-0 z-20 grid place-items-center">
                <div v-if="rollResultOverlay" class="pointer-events-auto min-w-80 rounded-lg border border-amber-300/35 bg-stone-950/70 p-6 text-center shadow-[0_24px_90px_rgba(0,0,0,0.62)] ring-1 ring-white/10 backdrop-blur-md">
                    <p class="fantasy-kicker">{{ rollActorName(rollResultOverlay) }}</p>
                    <p class="mt-2 text-5xl font-bold text-amber-100">{{ rollResultOverlay.total }}</p>
                    <p class="mt-2 text-sm font-semibold uppercase tracking-[0.18em] text-stone-300">{{ rollFormulaText(rollResultOverlay) }}</p>
                    <p v-if="rollAttributeText(rollResultOverlay)" class="mt-2 text-xs uppercase tracking-[0.16em] text-amber-200">{{ rollAttributeText(rollResultOverlay) }}</p>
                    <p v-if="rollPerformedByLabel(rollResultOverlay)" class="mt-2 text-xs text-stone-400">{{ rollPerformedByLabel(rollResultOverlay) }}</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <span v-for="(value, index) in rollResultOverlay.roll_values" :key="`roll-die-${rollResultOverlay.id}-${index}`" class="grid h-12 w-12 place-items-center rounded-lg border border-amber-300/30 bg-amber-300/10 text-lg font-semibold text-amber-50">{{ value }}</span>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-6 left-6 z-30 flex gap-3">
                <button type="button" class="rounded-lg border border-amber-300/30 bg-stone-950/60 px-6 py-4 text-sm font-semibold uppercase tracking-[0.16em] text-amber-100 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md transition hover:border-amber-200 hover:bg-amber-300/10" @click="openCharacterModal('inventory')">
                    Инвентарь
                </button>
                <button type="button" class="rounded-lg border border-amber-300/30 bg-stone-950/60 px-6 py-4 text-sm font-semibold uppercase tracking-[0.16em] text-amber-100 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md transition hover:border-amber-200 hover:bg-amber-300/10" @click="openCharacterModal('stats')">
                    Характеристики
                </button>
            </div>

            <div class="absolute bottom-6 left-1/2 z-30 flex -translate-x-1/2 gap-2 rounded-lg border border-amber-300/20 bg-stone-950/55 px-4 py-3 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md">
                <button v-for="diceType in diceOptions" :key="`hud-${diceType}`" type="button" class="grid h-14 w-14 place-items-center rounded-lg border border-stone-500/50 bg-stone-900/70 text-sm font-bold uppercase text-amber-100 transition hover:border-amber-300 hover:bg-amber-300/12" @click="openDiceModal(diceType)">
                    {{ diceType }}
                </button>
            </div>

            <div class="group absolute bottom-6 right-6 z-30 w-80">
                <div class="max-h-28 overflow-hidden rounded-lg border border-transparent bg-stone-950/20 p-3 text-sm text-stone-200 backdrop-blur-sm transition group-hover:max-h-96 group-hover:border-amber-300/20 group-hover:bg-stone-950/60">
                    <p class="mb-2 hidden text-xs font-semibold uppercase tracking-[0.18em] text-amber-300 group-hover:block">Roll log</p>
                    <article v-for="roll in rollLogItems" :key="`hud-log-${roll.id}`" class="mb-2 rounded-md bg-stone-950/35 px-3 py-2 shadow-sm group-hover:hidden">
                        <span class="font-semibold text-amber-100">{{ rollActorName(roll) }}</span>
                        <span class="text-stone-300"> {{ rollFormulaText(roll) }} -> </span>
                        <span class="font-semibold text-amber-50">{{ roll.total }}</span>
                    </article>
                    <article v-for="roll in rolls.items.slice(0, 12)" :key="`hud-full-log-${roll.id}`" class="mb-2 hidden rounded-md bg-stone-950/35 px-3 py-2 shadow-sm group-hover:block">
                        <span class="font-semibold text-amber-100">{{ rollActorName(roll) }}</span>
                        <span class="text-stone-300"> {{ rollFormulaText(roll) }} -> </span>
                        <span class="font-semibold text-amber-50">{{ roll.total }}</span>
                    </article>
                    <p v-if="rollLogItems.length === 0 && rolls.items.length === 0" class="text-sm text-stone-400">Бросков пока нет.</p>
                </div>
            </div>
        </div>

        <div v-if="can_manage_sessions" class="fixed inset-0 z-50 overflow-hidden bg-stone-950 text-stone-100">
            <img v-if="scene.background?.image_url" :src="scene.background.image_url" :alt="scene.background.title" class="absolute inset-0 h-full w-full object-cover" />
            <div v-else class="absolute inset-0 bg-[radial-gradient(circle_at_50%_36%,rgba(245,158,11,0.20),transparent_18rem),linear-gradient(135deg,rgba(68,64,60,0.96),rgba(15,23,20,0.98))]" />
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0,rgba(0,0,0,0.14)_38%,rgba(0,0,0,0.72)_100%)]" />
            <div class="absolute inset-0 bg-gradient-to-b from-stone-950/62 via-transparent to-stone-950/84" />

            <div class="pointer-events-none absolute inset-x-0 top-0 z-10 flex justify-center px-8 pt-5">
                <div class="pointer-events-auto flex max-w-5xl gap-3 overflow-visible rounded-lg border border-amber-300/20 bg-stone-950/45 px-4 py-3 shadow-[0_18px_60px_rgba(0,0,0,0.42)] backdrop-blur-md">
                    <article v-for="npc in topEncounteredNpcs" :key="`gm-met-${npc.id}`" class="group relative flex w-28 flex-col items-center gap-2 rounded-lg border border-stone-600/35 bg-stone-950/45 p-2 text-center" :class="isSpeaking('npc', npc.npc_id, npc.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'npc', npc)" @mouseleave="hideContextMenu" @click="setSceneSpeaker('npc', npc.npc_id, npc.id)">
                        <div v-if="npc.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-amber-300/20 bg-stone-900/60 p-1">
                            <img :src="npc.avatar_url" :alt="npc.name" class="max-h-[4.5rem] max-w-[4.5rem] rounded-md object-contain" />
                        </div>
                        <div v-else class="grid h-[4.5rem] w-[4.5rem] place-items-center rounded-lg border border-amber-300/20 bg-stone-900 text-sm font-semibold text-amber-100">{{ npc.name?.charAt(0) }}</div>
                        <p class="line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ npc.name }}<span v-if="npc.quantity > 1"> x{{ npc.quantity }}</span></p></article>
                    <p v-if="topEncounteredNpcs.length === 0" class="px-4 py-3 text-sm text-stone-400">Встреченных NPC пока нет.</p>
                </div>
            </div>

            <div class="absolute right-6 top-6 z-40">
                <div class="group relative">
                    <button type="button" class="grid h-14 w-14 place-items-center rounded-lg border border-amber-300/30 bg-stone-950/65 text-2xl text-amber-100 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md transition hover:border-amber-200">▣</button>
                    <div class="absolute right-0 top-full hidden w-80 rounded-lg border border-amber-300/20 bg-stone-950/95 p-4 shadow-2xl group-hover:block">
                        <p class="fantasy-kicker">Backgrounds</p>
                        <input v-model="backgroundSearch" type="search" class="fantasy-input mt-3 block w-full" placeholder="Search background" />
                        <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                            <button type="button" class="block w-full rounded-lg border border-stone-600/40 bg-stone-900/70 px-3 py-2 text-left text-sm text-stone-200 hover:border-amber-300/50" :class="!scene.background ? 'ring-2 ring-amber-300/50' : ''" @click="setSceneBackground('')">No background</button>
                            <button v-for="background in filteredBackgrounds" :key="`gm-bg-${background.id}`" type="button" class="block w-full overflow-hidden rounded-lg border border-stone-600/40 bg-stone-900/70 text-left hover:border-amber-300/50" :class="scene.background?.id === background.id ? 'ring-2 ring-amber-300/50' : ''" @click="setSceneBackground(background.id)">
                                <img v-if="background.image_url" :src="background.image_url" :alt="background.title" class="h-20 w-full object-cover" />
                                <span class="block px-3 py-2 text-sm text-amber-50">{{ background.title }}</span>
                            </button>
                        </div>
                        <button type="button" class="mt-3 w-full rounded-lg border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-sm font-semibold text-amber-100 hover:bg-amber-300/15" @click="showBackgroundUploadModal = true">Загрузить фон</button>
                    </div>
                </div>
            </div>

            <aside class="pointer-events-none absolute bottom-32 left-5 top-28 z-10 flex w-32 flex-col justify-center gap-3 xl:left-8 xl:w-36">
                <article v-for="character in inventory.characters" :key="`gm-party-${character.id}`" class="pointer-events-auto group relative rounded-lg border border-amber-300/25 bg-stone-950/55 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.38)] backdrop-blur-md" :class="[isSpeaking('character', character.id) ? 'ring-2 ring-amber-300/70' : '', isCharacterHidden(character.id) ? 'opacity-65' : '']" @mouseenter="showContextMenu($event, 'character', character)" @mouseleave="hideContextMenu" @click="setSceneSpeaker('character', character.id)">
                    <div v-if="character.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-amber-300/20 bg-stone-900/60 p-1">
                        <img :src="character.avatar_url" :alt="character.name" class="max-h-[5rem] max-w-[5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.25rem] w-[5.25rem] place-items-center rounded-lg border border-amber-300/20 bg-stone-900 text-lg font-semibold text-amber-100">{{ character.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ character.name }}</p>
                    <p v-if="isCharacterHidden(character.id)" class="mt-1 text-[10px] uppercase tracking-[0.18em] text-stone-400">hidden for players</p></article>
                <article v-for="npc in alliedNpcs" :key="`gm-ally-${npc.id}`" class="pointer-events-auto group relative rounded-lg border border-emerald-300/30 bg-emerald-950/35 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.32)] backdrop-blur-md" :class="isSpeaking('npc', npc.npc_id, npc.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'npc', npc)" @mouseleave="hideContextMenu" @click="setSceneSpeaker('npc', npc.npc_id, npc.id)">
                    <div v-if="npc.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-emerald-300/20 bg-stone-900/60 p-1">
                        <img :src="npc.avatar_url" :alt="npc.name" class="max-h-[5rem] max-w-[5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.25rem] w-[5.25rem] place-items-center rounded-lg border border-emerald-300/20 bg-stone-900 text-lg font-semibold text-emerald-100">{{ npc.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ npc.name }}<span v-if="npc.quantity > 1"> x{{ npc.quantity }}</span></p></article>
            </aside>

            <aside class="pointer-events-none absolute bottom-32 right-5 top-28 z-10 flex w-32 flex-col justify-center gap-3 xl:right-8 xl:w-36">
                <article v-for="npc in enemyNpcs" :key="`gm-enemy-${npc.id}`" class="pointer-events-auto group relative rounded-lg border border-red-300/30 bg-red-950/35 p-2 text-center shadow-[0_18px_50px_rgba(0,0,0,0.34)] backdrop-blur-md" :class="isSpeaking('npc', npc.npc_id, npc.id) ? 'ring-2 ring-amber-300/70' : ''" @mouseenter="showContextMenu($event, 'enemy', npc)" @mouseleave="hideContextMenu" @click="setSceneSpeaker('npc', npc.npc_id, npc.id)">
                    <div v-if="npc.avatar_url" class="mx-auto inline-flex items-center justify-center overflow-hidden rounded-lg border border-red-300/20 bg-stone-900/60 p-1">
                        <img :src="npc.avatar_url" :alt="npc.name" class="max-h-[5.5rem] max-w-[5.5rem] rounded-md object-contain" />
                    </div>
                    <div v-else class="mx-auto grid h-[5.75rem] w-[5.75rem] place-items-center rounded-lg border border-red-300/20 bg-stone-900 text-xl font-semibold text-red-100">{{ npc.name?.charAt(0) }}</div>
                    <p class="mt-2 line-clamp-2 text-xs font-semibold leading-4 text-amber-50">{{ npc.name }}<span v-if="npc.quantity > 1"> x{{ npc.quantity }}</span></p></article>
            </aside>

            <div v-if="hoveredContext" class="fixed z-[90] w-48 rounded-lg border border-amber-300/20 bg-stone-950/95 p-2 text-left shadow-2xl backdrop-blur-md" :style="{ left: `${hoveredContext.x}px`, top: `${hoveredContext.y}px` }" @mouseenter="keepContextMenu" @mouseleave="hideContextMenu">
                <template v-if="hoveredContext.kind === 'character'">
                    <button
                        class="block w-full rounded-md px-2 py-2 text-left text-xs transition"
                        :class="canPreviewImage(hoveredContext.entity) ? 'text-stone-200 hover:bg-amber-300/10' : 'cursor-not-allowed text-stone-500'"
                        :disabled="!canPreviewImage(hoveredContext.entity)"
                        @click="openImagePreview(hoveredContext.entity)"
                    >
                        Посмотреть изображение
                    </button>
                    <template v-if="!hoveredContext.viewerOnly">
                    <button class="block w-full rounded-md px-2 py-2 text-left text-xs text-stone-200 hover:bg-amber-300/10" @click="openCharacterModal('inventory', hoveredContext.entity); hideContextMenu()">Инвентарь</button>
                    <button class="block w-full rounded-md px-2 py-2 text-left text-xs text-stone-200 hover:bg-amber-300/10" @click="openCharacterModal('stats', hoveredContext.entity); hideContextMenu()">Статы</button>
                    <button class="block w-full rounded-md px-2 py-2 text-left text-xs text-stone-200 hover:bg-amber-300/10" @click="toggleCharacterVisibility(hoveredContext.entity.id); hideContextMenu()">{{ isCharacterHidden(hoveredContext.entity.id) ? 'Показать игрокам' : 'Скрыть со сцены' }}</button>
                    </template>
                </template>
                <template v-else>
                    <button
                        class="block w-full rounded-md px-2 py-2 text-left text-xs transition"
                        :class="canPreviewImage(hoveredContext.entity) ? 'text-stone-200 hover:bg-amber-300/10' : 'cursor-not-allowed text-stone-500'"
                        :disabled="!canPreviewImage(hoveredContext.entity)"
                        @click="openImagePreview(hoveredContext.entity)"
                    >
                        Посмотреть изображение
                    </button>
                    <template v-if="!hoveredContext.viewerOnly">
                    <button class="block w-full rounded-md px-2 py-1 text-left text-xs text-stone-200 hover:bg-amber-300/10" @click="setNpcSceneState(hoveredContext.entity, { present: false, encountered: true }); hideContextMenu()">Удалить со сцены</button>
                    <button class="block w-full rounded-md px-2 py-1 text-left text-xs text-stone-200 hover:bg-amber-300/10" @click="setNpcSceneState(hoveredContext.entity, { present: false, encountered: false }); hideContextMenu()">Скрыть</button>
                    <button v-if="hoveredContext.entity.type !== 'ally'" class="block w-full rounded-md px-2 py-1 text-left text-xs text-emerald-100 hover:bg-emerald-300/10" @click="moveNpcTo(hoveredContext.entity, 'ally'); hideContextMenu()">В союзники</button>
                    <button v-if="hoveredContext.entity.type !== 'neutral'" class="block w-full rounded-md px-2 py-1 text-left text-xs text-sky-100 hover:bg-sky-300/10" @click="moveNpcTo(hoveredContext.entity, 'neutral'); hideContextMenu()">В нейтралы</button>
                    <button v-if="hoveredContext.entity.type !== 'enemy'" class="block w-full rounded-md px-2 py-1 text-left text-xs text-red-100 hover:bg-red-300/10" @click="moveNpcTo(hoveredContext.entity, 'enemy'); hideContextMenu()">Во враги</button>
                    </template>
                </template>
            </div>

            <div class="pointer-events-none absolute inset-0 z-20 grid place-items-center">
                <div v-if="rollResultOverlay" class="pointer-events-auto min-w-80 rounded-lg border border-amber-300/35 bg-stone-950/70 p-6 text-center shadow-[0_24px_90px_rgba(0,0,0,0.62)] ring-1 ring-white/10 backdrop-blur-md">
                    <p class="fantasy-kicker">{{ rollActorName(rollResultOverlay) }}</p>
                    <p class="mt-2 text-5xl font-bold text-amber-100">{{ rollResultOverlay.total }}</p>
                    <p class="mt-2 text-sm font-semibold uppercase tracking-[0.18em] text-stone-300">{{ rollFormulaText(rollResultOverlay) }}</p>
                    <p v-if="rollAttributeText(rollResultOverlay)" class="mt-2 text-xs uppercase tracking-[0.16em] text-amber-200">{{ rollAttributeText(rollResultOverlay) }}</p>
                    <p v-if="rollPerformedByLabel(rollResultOverlay)" class="mt-2 text-xs text-stone-400">{{ rollPerformedByLabel(rollResultOverlay) }}</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <span v-for="(value, index) in rollResultOverlay.roll_values" :key="`gm-roll-die-${rollResultOverlay.id}-${index}`" class="grid h-12 w-12 place-items-center rounded-lg border border-amber-300/30 bg-amber-300/10 text-lg font-semibold text-amber-50">{{ value }}</span>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-6 left-6 z-30 flex gap-3">
                <button type="button" class="rounded-lg border border-amber-300/30 bg-stone-950/60 px-8 py-4 text-sm font-semibold uppercase tracking-[0.16em] text-amber-100 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md transition hover:border-amber-200 hover:bg-amber-300/10" @click="showNpcLibraryModal = true">
                    NPC
                </button>
            </div>

            <div class="absolute bottom-6 left-1/2 z-30 flex -translate-x-1/2 gap-2 rounded-lg border border-amber-300/20 bg-stone-950/55 px-4 py-3 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md">
                <button v-for="diceType in diceOptions" :key="`gm-hud-${diceType}`" type="button" class="grid h-14 w-14 place-items-center rounded-lg border border-stone-500/50 bg-stone-900/70 text-sm font-bold uppercase text-amber-100 transition hover:border-amber-300 hover:bg-amber-300/12" @click="openDiceModal(diceType)">
                    {{ diceType }}
                </button>
            </div>

            <div class="group absolute bottom-6 right-6 z-30 w-80">
                <div class="max-h-28 overflow-hidden rounded-lg border border-transparent bg-stone-950/20 p-3 text-sm text-stone-200 backdrop-blur-sm transition group-hover:max-h-96 group-hover:border-amber-300/20 group-hover:bg-stone-950/60">
                    <p class="mb-2 hidden text-xs font-semibold uppercase tracking-[0.18em] text-amber-300 group-hover:block">Roll log</p>
                    <article v-for="roll in rollLogItems" :key="`gm-hud-log-${roll.id}`" class="mb-2 rounded-md bg-stone-950/35 px-3 py-2 shadow-sm group-hover:hidden">
                        <span class="font-semibold text-amber-100">{{ rollActorName(roll) }}</span>
                        <span class="text-stone-300"> {{ rollFormulaText(roll) }} -> </span>
                        <span class="font-semibold text-amber-50">{{ roll.total }}</span>
                    </article>
                    <article v-for="roll in rolls.items.slice(0, 12)" :key="`gm-hud-full-log-${roll.id}`" class="mb-2 hidden rounded-md bg-stone-950/35 px-3 py-2 shadow-sm group-hover:block">
                        <span class="font-semibold text-amber-100">{{ rollActorName(roll) }}</span>
                        <span class="text-stone-300"> {{ rollFormulaText(roll) }} -> </span>
                        <span class="font-semibold text-amber-50">{{ roll.total }}</span>
                    </article>
                    <p v-if="rollLogItems.length === 0 && rolls.items.length === 0" class="text-sm text-stone-400">Бросков пока нет.</p>
                </div>
            </div>
        </div>

        <div v-if="hoveredContext && !can_manage_sessions" class="fixed z-[90] w-48 rounded-lg border border-amber-300/20 bg-stone-950/95 p-2 text-left shadow-2xl backdrop-blur-md" :style="{ left: `${hoveredContext.x}px`, top: `${hoveredContext.y}px` }" @mouseenter="keepContextMenu" @mouseleave="hideContextMenu">
            <template v-if="hoveredContext.kind === 'character'">
                <button
                    class="block w-full rounded-md px-2 py-2 text-left text-xs transition"
                    :class="canPreviewImage(hoveredContext.entity) ? 'text-stone-200 hover:bg-amber-300/10' : 'cursor-not-allowed text-stone-500'"
                    :disabled="!canPreviewImage(hoveredContext.entity)"
                    @click="openImagePreview(hoveredContext.entity)"
                >
                    Посмотреть изображение
                </button>
            </template>
            <template v-else>
                <button
                    class="block w-full rounded-md px-2 py-2 text-left text-xs transition"
                    :class="canPreviewImage(hoveredContext.entity) ? 'text-stone-200 hover:bg-amber-300/10' : 'cursor-not-allowed text-stone-500'"
                    :disabled="!canPreviewImage(hoveredContext.entity)"
                    @click="openImagePreview(hoveredContext.entity)"
                >
                    Посмотреть изображение
                </button>
            </template>
        </div>

        <div
            class="fixed bottom-6 transition-all duration-200"
            :class="isChatExpanded ? 'right-6 z-[80] w-[min(48rem,calc(100vw-12rem))]' : 'right-[25rem] z-[70] w-80'"
            @mouseenter="handleChatMouseEnter"
            @mouseleave="handleChatMouseLeave"
        >
            <div class="pointer-events-auto rounded-lg border border-amber-300/20 bg-stone-950/45 p-3 text-sm text-stone-200 shadow-[0_18px_52px_rgba(0,0,0,0.42)] backdrop-blur-md">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-300">Чат</p>
                <div
                    ref="chatScroll"
                    class="overflow-y-auto rounded-lg border border-transparent bg-stone-950/20 pr-1 select-text"
                    :class="isChatExpanded ? 'mb-3 h-64' : 'mb-3 h-24'"
                >
                    <article
                        v-for="item in chatItems"
                        :key="`chat-${item.id}`"
                        class="mb-2 rounded-md bg-stone-950/35 px-3 py-2 last:mb-0"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-100">{{ item.user_name }}</p>
                        <p class="mt-1 whitespace-pre-wrap break-words text-stone-200">{{ item.message }}</p>
                    </article>
                    <p v-if="chatItems.length === 0" class="px-1 py-2 text-sm text-stone-400">Сообщений пока нет.</p>
                </div>

                <form class="flex items-end gap-2" @submit.prevent="sendChatMessage">
                    <input
                        v-model="chatMessage"
                        type="text"
                        maxlength="1000"
                        class="fantasy-input block w-full"
                        placeholder="Написать сообщение..."
                        @focus="handleChatInputFocus"
                        @blur="handleChatInputBlur"
                        @keydown.enter.prevent="sendChatMessage"
                    />
                    <PrimaryButton :disabled="!chatMessage.trim()">Отправить</PrimaryButton>
                </form>
            </div>
        </div>

        <div v-if="false && can_manage_sessions" class="py-6">
            <div class="mx-auto max-w-[92rem] space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                    {{ page.props.flash.success }}
                </div>

                <section class="rounded-lg border border-amber-300/20 bg-stone-950/70 p-3 shadow-[0_28px_90px_rgba(0,0,0,0.55)] ring-1 ring-white/5">
                    <div class="grid gap-3 xl:grid-cols-[18rem_minmax(0,1fr)_24rem]">
                        <aside class="fantasy-panel p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="fantasy-kicker">Party</p>
                                    <h2 class="text-lg font-semibold text-amber-50">Участники сцены</h2>
                                </div>
                                <span class="fantasy-chip-muted">{{ scene.teammates.length + alliedNpcs.length + (scene.own_character ? 1 : 0) }}</span>
                            </div>

                            <div class="mt-4 space-y-3">
                                <article v-if="scene.own_character" class="rounded-lg border border-amber-300/20 bg-stone-950/70 p-3" :class="isSpeaking('character', scene.own_character.id) ? 'ring-2 ring-amber-300/70' : ''">
                                    <div class="flex gap-3">
                                        <img v-if="scene.own_character.avatar_url" :src="scene.own_character.avatar_url" :alt="scene.own_character.name" class="h-14 w-14 rounded-lg object-cover" />
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-semibold text-amber-50">{{ scene.own_character.name }}</p>
                                            <p class="text-xs text-stone-400">{{ scene.own_character.user_name }}</p>
                                            <p v-if="isSpeaking('character', scene.own_character.id)" class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-amber-200">speaking</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <SecondaryButton @click="openCharacterModal('stats')">Stats</SecondaryButton>
                                        <SecondaryButton @click="openCharacterModal('inventory')">Inventory</SecondaryButton>
                                    </div>
                                </article>
                                <p v-else class="fantasy-empty p-4">Персонаж пока не создан.</p>

                                <article v-for="teammate in scene.teammates" :key="`rail-character-${teammate.id}`" class="flex items-center gap-3 rounded-lg border border-stone-600/40 bg-stone-950/60 px-3 py-2" :class="isSpeaking('character', teammate.id) ? 'ring-2 ring-amber-300/60' : ''">
                                    <img v-if="teammate.avatar_url" :src="teammate.avatar_url" :alt="teammate.name" class="h-11 w-11 rounded-lg object-cover" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-amber-50">{{ teammate.name }}</p>
                                        <p class="truncate text-xs text-stone-400">{{ teammate.user_name }}</p>
                                    </div>
                                    <span v-if="isSpeaking('character', teammate.id)" class="text-xs text-amber-200">speaker</span>
                                </article>

                                <article v-for="npc in alliedNpcs" :key="`rail-ally-${npc.id}`" class="flex items-center gap-3 rounded-lg border border-emerald-300/30 bg-emerald-400/10 px-3 py-2" :class="isSpeaking('npc', npc.id) ? 'ring-2 ring-amber-300/60' : ''">
                                    <img v-if="npc.avatar_url" :src="npc.avatar_url" :alt="npc.name" class="h-11 w-11 rounded-lg object-cover" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-amber-50">{{ npc.name }}</p>
                                        <p class="text-xs text-emerald-100">ally NPC</p>
                                    </div>
                                    <span v-if="isSpeaking('npc', npc.id)" class="text-xs text-amber-200">speaker</span>
                                </article>
                            </div>
                        </aside>

                        <div class="overflow-hidden rounded-lg border border-amber-300/25 bg-stone-950 shadow-[inset_0_0_80px_rgba(0,0,0,0.45)]">
                        <div class="relative min-h-[34rem] lg:min-h-[42rem]">
                            <img v-if="scene.background?.image_url" :src="scene.background.image_url" :alt="scene.background.title" class="absolute inset-0 h-full w-full object-cover" />
                            <div v-else class="absolute inset-0 bg-[radial-gradient(circle_at_50%_35%,rgba(245,158,11,0.18),transparent_18rem),linear-gradient(135deg,rgba(68,64,60,0.92),rgba(28,25,23,0.98))]" />
                            <div class="absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/55 to-stone-950/15" />
                            <div class="relative flex min-h-[34rem] flex-col justify-between p-6">
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p class="fantasy-kicker">Сцена</p>
                                        <h2 class="mt-2 text-3xl font-semibold text-amber-50">{{ scene.background?.title ?? 'Фон не выбран' }}</h2>
                                        <p class="mt-2 max-w-xl text-sm leading-6 text-stone-300">Центральный стол сессии: фон, состав сцены и говорящий обновляются в реальном времени.</p>
                                    </div>
                                    <div v-if="scene.speaker" class="rounded-lg border border-amber-300/35 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">
                                        Говорящий: {{ scene.speaker.name }}
                                    </div>
                                </div>

                                <div class="mx-auto grid w-full max-w-4xl gap-3 md:grid-cols-3">
                                    <div v-if="scene.own_character" class="rounded-lg border border-amber-300/25 bg-stone-950/65 p-3 backdrop-blur" :class="isSpeaking('character', scene.own_character.id) ? 'ring-2 ring-amber-300/70' : ''">
                                        <p class="text-xs uppercase tracking-[0.18em] text-amber-200">You</p>
                                        <p class="mt-1 truncate font-semibold text-amber-50">{{ scene.own_character.name }}</p>
                                    </div>
                                    <div class="rounded-lg border border-stone-600/40 bg-stone-950/55 p-3 backdrop-blur">
                                        <p class="text-xs uppercase tracking-[0.18em] text-stone-400">Party</p>
                                        <p class="mt-1 font-semibold text-amber-50">{{ scene.teammates.length + alliedNpcs.length }} рядом</p>
                                    </div>
                                    <div class="rounded-lg border border-stone-600/40 bg-stone-950/55 p-3 backdrop-blur">
                                        <p class="text-xs uppercase tracking-[0.18em] text-stone-400">NPC</p>
                                        <p class="mt-1 font-semibold text-amber-50">{{ presentSceneNpcs.length }} на сцене</p>
                                    </div>
                                </div>

                                <div class="hidden">
                                    <article class="fantasy-card" :class="isSpeaking('character', scene.own_character?.id) ? 'ring-2 ring-amber-300/60' : ''">
                                        <p class="fantasy-kicker">Ваш персонаж</p>
                                        <div v-if="scene.own_character" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                                            <img v-if="scene.own_character.avatar_url" :src="scene.own_character.avatar_url" :alt="scene.own_character.name" class="h-20 w-20 rounded-lg object-cover" />
                                            <div class="min-w-0 flex-1">
                                                <p class="font-semibold text-amber-50">{{ scene.own_character.name }}</p>
                                                <p class="text-sm text-stone-400">{{ scene.own_character.user_name }}</p>
                                                <p v-if="scene.own_character.origin" class="mt-1 text-sm text-stone-300">{{ scene.own_character.origin }}</p>
                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    <SecondaryButton @click="openCharacterModal('stats')">Stats</SecondaryButton>
                                                    <SecondaryButton @click="openCharacterModal('inventory')">Inventory</SecondaryButton>
                                                </div>
                                            </div>
                                        </div>
                                        <p v-else class="mt-4 text-sm text-stone-400">Персонаж пока не создан.</p>
                                    </article>

                                    <article class="fantasy-card">
                                        <p class="fantasy-kicker">Команда</p>
                                        <div v-if="scene.teammates.length || alliedNpcs.length" class="mt-4 space-y-3">
                                            <div v-for="teammate in scene.teammates" :key="`character-${teammate.id}`" class="flex items-center gap-3 rounded-lg border border-stone-600/40 bg-stone-950/60 px-3 py-2" :class="isSpeaking('character', teammate.id) ? 'ring-2 ring-amber-300/60' : ''">
                                                <img v-if="teammate.avatar_url" :src="teammate.avatar_url" :alt="teammate.name" class="h-12 w-12 rounded-lg object-cover" />
                                                <div>
                                                    <p class="font-medium text-amber-50">{{ teammate.name }}</p>
                                                    <p class="text-sm text-stone-400">{{ teammate.user_name }}</p>
                                                </div>
                                            </div>
                                            <div v-for="npc in alliedNpcs" :key="`ally-${npc.id}`" class="flex items-center gap-3 rounded-lg border border-emerald-300/30 bg-emerald-400/10 px-3 py-2" :class="isSpeaking('npc', npc.id) ? 'ring-2 ring-amber-300/60' : ''">
                                                <img v-if="npc.avatar_url" :src="npc.avatar_url" :alt="npc.name" class="h-12 w-12 rounded-lg object-cover" />
                                                <div>
                                                    <p class="font-medium text-amber-50">{{ npc.name }}</p>
                                                    <p class="text-sm text-emerald-100">Ally NPC</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p v-else class="mt-4 text-sm text-stone-400">Других персонажей в сцене пока нет.</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <section class="fantasy-panel p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-amber-50">Броски кубиков</h2>
                                    <p class="fantasy-subtitle mt-1">Сервер считает результат и транслирует его всем участникам.</p>
                                </div>
                                <div v-if="rollingText" class="fantasy-chip animate-pulse">{{ rollingText }}</div>
                            </div>

                            <form class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-[0.8fr_0.8fr_1fr_1fr_auto] xl:items-end" @submit.prevent="submitRoll">
                                <div>
                                    <InputLabel for="dice-count" value="Количество" />
                                    <input id="dice-count" v-model.number="diceForm.dice_count" type="number" min="1" max="20" class="fantasy-input mt-2 block w-full" />
                                    <InputError class="mt-2" :message="diceForm.errors.dice_count" />
                                </div>
                                <div>
                                    <InputLabel for="dice-type" value="Кубик" />
                                    <select id="dice-type" v-model="diceForm.dice_type" class="fantasy-select mt-2 block w-full">
                                        <option v-for="diceType in diceOptions" :key="diceType" :value="diceType">{{ diceType }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel v-if="can_manage_sessions" for="dice-source" value="Источник броска" />
                                    <InputLabel v-else value="Источник броска" />
                                    <template v-if="can_manage_sessions">
                                        <select id="dice-source" :value="currentRollSourceValue" class="fantasy-select mt-2 block w-full" @change="updateRollSource($event.target.value)">
                                            <option value="">Без источника</option>
                                            <option v-for="actor in availableRollActors" :key="`${actor.source_type}:${actor.source_id}`" :value="`${actor.source_type}:${actor.source_id}`">
                                                {{ actor.name }}{{ actor.source_type === 'scene_npc' ? ' · NPC' : ' · Персонаж' }}
                                            </option>
                                        </select>
                                    </template>
                                    <div v-else class="mt-2 rounded-lg border border-amber-300/20 bg-stone-950/60 px-3 py-2 text-sm text-amber-50">
                                        {{ playerRollActor?.name ?? 'Без источника' }}
                                    </div>
                                    <InputError class="mt-2" :message="diceForm.errors.source_id" />
                                </div>
                                <div>
                                    <InputLabel for="dice-attribute" value="Характеристика" />
                                    <select id="dice-attribute" v-model="diceForm.attribute_key" class="fantasy-select mt-2 block w-full" :disabled="!selectedRollActor || selectedRollAttributes.length === 0">
                                        <option value="">Без характеристики</option>
                                        <option v-for="attribute in selectedRollAttributes" :key="attribute.key" :value="attribute.key">
                                            {{ attribute.label }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="diceForm.errors.attribute_key" />
                                    <p class="mt-2 text-xs text-stone-400">{{ selectedRollAttributeLabel }}</p>
                                </div>
                                <div>
                                    <InputLabel for="dice-modifier" value="Ручной модификатор" />
                                    <input id="dice-modifier" v-model.number="diceForm.modifier" type="number" min="-100" max="100" class="fantasy-input mt-2 block w-full" />
                                    <InputError class="mt-2" :message="diceForm.errors.modifier" />
                                    <p class="mt-2 text-xs text-stone-400">Итоговый модификатор: {{ signedNumber(finalRollModifier) }}</p>
                                </div>
                                <PrimaryButton :disabled="diceForm.processing">{{ diceForm.processing ? 'Бросаем...' : 'Бросить' }}</PrimaryButton>
                            </form>

                            <div class="mt-6 space-y-3">
                                <article v-for="roll in rolls.items" :key="roll.id" class="fantasy-card transition" :class="highlightedRollId === roll.id ? 'scale-[1.01] border-amber-300/50 bg-amber-300/10' : ''">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium text-amber-50">{{ rollActorName(roll) }}</p>
                                            <p v-if="rollPerformedByLabel(roll)" class="mt-1 text-xs text-stone-500">{{ rollPerformedByLabel(roll) }}</p>
                                            <p class="mt-1 text-sm text-stone-400">{{ rollBreakdownText(roll) }}</p>
                                        </div>
                                        <div class="rounded-lg border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-lg font-semibold text-amber-100">{{ roll.total }}</div>
                                    </div>
                                </article>
                                <p v-if="rolls.items.length === 0" class="text-sm text-stone-400">Лог бросков пока пуст.</p>
                            </div>
                        </section>

                        <section class="fantasy-panel p-4">
                            <h2 class="text-lg font-semibold text-amber-50">Encountered NPC</h2>
                            <p class="fantasy-subtitle mt-1">Met NPC stay here; present ally NPC are shown with the party.</p>
                            <div v-if="encounteredNpcs.length" class="mt-4 space-y-3">
                                <div v-for="npc in encounteredNpcs" :key="npc.id" class="flex items-center gap-3 rounded-lg border border-stone-600/40 bg-stone-950/60 px-4 py-3" :class="isSpeaking('npc', npc.id) ? 'ring-2 ring-amber-300/60' : ''">
                                    <img v-if="npc.avatar_url" :src="npc.avatar_url" :alt="npc.name" class="h-14 w-14 rounded-lg object-cover" />
                                    <div>
                                        <p class="font-medium text-amber-50">{{ npc.name }}</p>
                                        <p class="text-sm text-stone-400">{{ npc.type_label }}</p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="mt-4 text-sm text-stone-400">No encountered NPC yet.</p>
                        </section>

                        <section v-if="can_manage_sessions" class="fantasy-panel p-4">
                            <h2 class="text-lg font-semibold text-amber-50">Управление сценой</h2>
                            <form class="mt-6 space-y-5" @submit.prevent="submitScene">
                                <div>
                                    <InputLabel for="scene-background" value="Фон сцены" />
                                    <select id="scene-background" v-model="sceneForm.background_id" class="fantasy-select mt-2 block w-full">
                                        <option value="">Без фона</option>
                                        <option v-for="background in scene.controls.backgrounds" :key="background.id" :value="background.id">{{ background.title }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Видимые NPC" />
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        <label v-for="npc in scene.controls.npcs" :key="npc.id" class="flex items-center gap-3 rounded-lg border border-stone-600/40 bg-stone-950/60 px-3 py-2 text-sm text-stone-200">
                                            <input v-model="sceneForm.present_npc_ids" type="checkbox" :value="npc.id" class="rounded border-amber-300/20 bg-stone-950 text-amber-500" />
                                            <span>{{ npc.name }} · present</span>
                                        </label>
                                        <label v-for="npc in scene.controls.npcs" :key="`encountered-${npc.id}`" class="flex items-center gap-3 rounded-lg border border-stone-600/40 bg-stone-950/60 px-3 py-2 text-sm text-stone-200">
                                            <input v-model="sceneForm.encountered_npc_ids" type="checkbox" :value="npc.id" class="rounded border-amber-300/20 bg-stone-950 text-amber-500" />
                                            <span>{{ npc.name }} · encountered</span>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <InputLabel for="scene-speaker" value="Текущий говорящий" />
                                    <select id="scene-speaker" :value="currentSpeakerValue" class="fantasy-select mt-2 block w-full" @change="selectSpeaker">
                                        <option value="">Без говорящего</option>
                                        <option v-for="speaker in allSpeakers" :key="`${speaker.type}:${speaker.id}`" :value="`${speaker.type}:${speaker.id}`">{{ speaker.label }}</option>
                                    </select>
                                </div>
                                <PrimaryButton :disabled="sceneForm.processing">Обновить сцену</PrimaryButton>
                            </form>
                        </section>
                    </div>
                    </div>
                </section>

                <section v-if="can_manage_sessions" class="fantasy-panel">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="fantasy-kicker">GM control tray</p>
                            <h2 class="text-lg font-semibold text-amber-50">Мастерская панель за ширмой</h2>
                            <p class="fantasy-subtitle mt-1">Игроки, фон, NPC и говорящий управляются из одного места без выхода из активной сессии.</p>
                        </div>
                        <SecondaryButton :disabled="sceneForm.processing" @click="clearSceneSpeaker">Clear speaker</SecondaryButton>
                    </div>

                    <div class="mt-6 grid gap-4 xl:grid-cols-3">
                        <section class="rounded-lg border border-stone-600/40 bg-stone-950/45 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Players</h3>
                            <div class="mt-4 space-y-3">
                                <article v-for="character in inventory.characters" :key="character.id" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-3" :class="isSpeaking('character', character.id) ? 'ring-2 ring-amber-300/60' : ''">
                                    <div class="flex items-center gap-3">
                                        <img v-if="character.avatar_url" :src="character.avatar_url" :alt="character.name" class="h-12 w-12 rounded-lg object-cover" />
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-amber-50">{{ character.name }}</p>
                                            <p class="text-sm text-stone-400">{{ character.user_name }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <SecondaryButton @click="openCharacterModal('stats', character)">Stats</SecondaryButton>
                                        <SecondaryButton @click="openCharacterModal('inventory', character)">Inventory</SecondaryButton>
                                        <SecondaryButton :disabled="sceneForm.processing" @click="setSceneSpeaker('character', character.id)">Speaker</SecondaryButton>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="rounded-lg border border-stone-600/40 bg-stone-950/45 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Backgrounds</h3>
                            <div class="mt-4 grid gap-3">
                                <button type="button" class="rounded-lg border border-stone-600/40 bg-stone-950/60 px-3 py-2 text-left text-sm text-stone-200 transition hover:border-amber-300/40" :class="!scene.background ? 'ring-2 ring-amber-300/50' : ''" @click="setSceneBackground('')">
                                    No background
                                </button>
                                <button v-for="background in scene.controls.backgrounds" :key="background.id" type="button" class="overflow-hidden rounded-lg border border-stone-600/40 bg-stone-950/60 text-left transition hover:border-amber-300/40" :class="scene.background?.id === background.id ? 'ring-2 ring-amber-300/50' : ''" @click="setSceneBackground(background.id)">
                                    <img v-if="background.image_url" :src="background.image_url" :alt="background.title" class="h-24 w-full object-cover" />
                                    <span class="block px-3 py-2 text-sm text-amber-50">{{ background.title }}</span>
                                </button>
                            </div>
                        </section>

                        <section class="rounded-lg border border-stone-600/40 bg-stone-950/45 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">NPC Control</h3>
                            <div class="mt-4 space-y-3">
                                <article v-for="npc in scene.controls.npcs" :key="npc.id" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-3" :class="isSpeaking('npc', npc.id) ? 'ring-2 ring-amber-300/60' : ''">
                                    <div class="flex items-center gap-3">
                                        <img v-if="npc.avatar_url" :src="npc.avatar_url" :alt="npc.name" class="h-12 w-12 rounded-lg object-cover" />
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-amber-50">{{ npc.name }}</p>
                                            <p class="text-sm" :class="npc.type === 'ally' ? 'text-emerald-200' : 'text-stone-400'">
                                                {{ npc.type_label }}{{ npc.type === 'ally' ? ' · party ally' : '' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p v-if="npc.description" class="mt-2 line-clamp-2 text-sm text-stone-400">{{ npc.description }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                        <span v-if="hasNpcState(sceneForm.encountered_npc_ids, npc.id)" class="rounded-md border border-sky-300/30 bg-sky-400/10 px-2 py-1 text-sky-100">encountered</span>
                                        <span v-if="hasNpcState(sceneForm.present_npc_ids, npc.id)" class="rounded-md border border-amber-300/30 bg-amber-400/10 px-2 py-1 text-amber-100">present</span>
                                        <span v-if="npc.type === 'ally'" class="rounded-md border border-emerald-300/30 bg-emerald-400/10 px-2 py-1 text-emerald-100">party ally</span>
                                        <span v-if="npc.has_character_sheet" class="rounded-md border border-stone-500/50 bg-stone-900 px-2 py-1 text-stone-200">лист подключён</span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <SecondaryButton :disabled="sceneForm.processing" @click="toggleEncounteredNpc(npc.id)">
                                            {{ hasNpcState(sceneForm.encountered_npc_ids, npc.id) ? 'Forget' : 'Mark met' }}
                                        </SecondaryButton>
                                        <SecondaryButton :disabled="sceneForm.processing" @click="togglePresentNpc(npc.id)">
                                            {{ hasNpcState(sceneForm.present_npc_ids, npc.id) ? 'Leave scene' : 'Bring in' }}
                                        </SecondaryButton>
                                        <SecondaryButton :disabled="sceneForm.processing" @click="setSceneSpeaker('npc', npc.id)">Speaker</SecondaryButton>
                                        <SecondaryButton v-if="npc.type !== 'ally'" @click="updateNpcType(npc, 'ally')">Mark ally</SecondaryButton>
                                        <SecondaryButton v-else @click="updateNpcType(npc, 'neutral')">Unmark ally</SecondaryButton>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </section>

                <section class="fantasy-panel">
                    <h2 class="text-lg font-semibold text-amber-50">Инвентарь</h2>
                    <p class="fantasy-subtitle mt-1">{{ can_manage_sessions ? 'Управление инвентарем персонажей в активной сессии.' : 'Ваш инвентарь обновляется в реальном времени.' }}</p>

                    <div v-if="can_manage_sessions" class="mt-6 grid gap-4 xl:grid-cols-2">
                        <article v-for="character in inventory.characters" :key="character.id" class="fantasy-card">
                            <h3 class="text-base font-semibold text-amber-50">{{ character.name }}</h3>
                            <p class="text-sm text-stone-400">{{ character.user_name }}</p>

                            <form class="mt-5 rounded-lg border border-stone-600/40 bg-stone-950/50 p-4" @submit.prevent="submitCatalogItem(character.id)">
                                <h4 class="text-sm font-semibold text-amber-50">Выдать предмет из каталога</h4>
                                <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_8rem_auto] sm:items-end">
                                    <div>
                                        <InputLabel :for="`catalog-item-${character.id}`" value="Предмет" />
                                        <select :id="`catalog-item-${character.id}`" v-model="inventoryForms[character.id].item_id" class="fantasy-select mt-2 block w-full">
                                            <option value="">Выберите предмет</option>
                                            <option v-for="item in inventory.catalog_items" :key="item.id" :value="item.id">{{ item.name }}{{ item.category ? ` (${item.category})` : '' }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel :for="`catalog-quantity-${character.id}`" value="Кол-во" />
                                        <input :id="`catalog-quantity-${character.id}`" v-model.number="inventoryForms[character.id].quantity" type="number" min="1" class="fantasy-input mt-2 block w-full" />
                                    </div>
                                    <PrimaryButton :disabled="inventoryForms[character.id].processing">Выдать</PrimaryButton>
                                </div>
                            </form>

                            <details class="mt-4 rounded-lg border border-stone-600/40 bg-stone-950/50 p-4">
                                <summary class="cursor-pointer text-sm font-semibold text-amber-50">Кастомный предмет</summary>
                                <form class="mt-4 space-y-4" @submit.prevent="submitCustomItem(character.id)">
                                    <TextInput v-model="customInventoryForms[character.id].custom_name" class="block w-full" placeholder="Название" />
                                    <textarea v-model="customInventoryForms[character.id].custom_description" class="fantasy-textarea block w-full" placeholder="Описание" />
                                    <input type="file" accept="image/*" class="fantasy-file block w-full" @change="setCustomImage(character.id, $event)" />
                                    <input v-model.number="customInventoryForms[character.id].quantity" type="number" min="1" class="fantasy-input block w-32" />
                                    <PrimaryButton :disabled="customInventoryForms[character.id].processing">Выдать кастомный</PrimaryButton>
                                </form>
                            </details>

                            <div v-if="character.inventory_items.length" class="mt-5 space-y-3">
                                <div v-for="item in character.inventory_items" :key="item.id" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="flex gap-4">
                                            <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-16 w-16 rounded-lg object-cover" />
                                            <div>
                                                <p class="font-medium text-amber-50">{{ item.name }}</p>
                                                <p class="mt-1 text-sm text-stone-400">Количество: {{ item.quantity }}</p>
                                                <p v-if="item.description" class="mt-2 text-sm text-stone-300">{{ item.description }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <form class="flex items-center gap-2" @submit.prevent="updateInventoryQuantity(character.id, item.id)">
                                                <input v-model.number="quantityForms[item.id].quantity" type="number" min="1" class="fantasy-input w-24" />
                                                <PrimaryButton :disabled="quantityForms[item.id].processing">Обновить</PrimaryButton>
                                            </form>
                                            <form @submit.prevent="removeInventoryItem(character.id, item.id)">
                                                <DangerButton>Удалить</DangerButton>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="mt-5 text-sm text-stone-400">Инвентарь пуст.</p>
                        </article>
                    </div>

                    <div v-else>
                        <div v-if="ownInventoryItems.length" class="mt-6 grid gap-3 md:grid-cols-2">
                            <article v-for="item in ownInventoryItems" :key="item.id" class="fantasy-card">
                                <div class="flex gap-4">
                                    <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-16 w-16 rounded-lg object-cover" />
                                    <div>
                                        <p class="font-medium text-amber-50">{{ item.name }}</p>
                                        <p class="mt-1 text-sm text-stone-400">Количество: {{ item.quantity }}</p>
                                        <p v-if="item.description" class="mt-2 text-sm text-stone-300">{{ item.description }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <p v-else class="mt-6 text-sm text-stone-400">Ваш инвентарь пока пуст.</p>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showNpcLibraryModal" max-width="2xl" @close="showNpcLibraryModal = false">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="fantasy-kicker">NPC library</p>
                        <h2 class="text-xl font-semibold text-amber-50">Добавить NPC на сцену</h2>
                    </div>
                    <SecondaryButton @click="showNpcLibraryModal = false">Close</SecondaryButton>
                </div>
                <input v-model="npcSearch" type="search" class="fantasy-input mt-5 block w-full" placeholder="Search NPC" />
                <div class="mt-5 rounded-xl border border-amber-300/20 bg-stone-950/50 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div class="grid gap-4 sm:grid-cols-[minmax(0,10rem)_auto] sm:items-end">
                            <label class="block">
                                <span class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">Количество</span>
                                <input v-model.number="npcAddQuantity" type="number" min="1" max="99" class="fantasy-input w-full" />
                            </label>
                            <label class="flex h-11 items-center gap-2 rounded-lg border border-stone-600/40 bg-stone-900/70 px-3 text-sm text-stone-200">
                                <input v-model="npcAddGroup" type="checkbox" class="rounded border-amber-300/20 bg-stone-950 text-amber-500" />
                                Добавить как группу
                            </label>
                        </div>
                        <p class="max-w-md text-sm text-stone-400">Эти параметры применяются ко всем трём кнопкам: в команду, в нейтралы и во враги.</p>
                    </div>
                </div>
                <div class="mt-5 max-h-[28rem] space-y-3 overflow-y-auto pr-1">
                    <article v-for="npc in filteredNpcs" :key="`npc-library-${npc.id}`" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <img v-if="npc.avatar_url" :src="npc.avatar_url" :alt="npc.name" class="h-12 w-12 rounded-lg object-cover" />
                                <div class="min-w-0">
                                    <p class="font-semibold text-amber-50">{{ npc.name }}</p>
                                    <p class="text-sm text-stone-400">{{ npc.type_label }}</p>
                                </div>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <SecondaryButton @click="addNpcToScene(npc, 'enemy')">Во враги</SecondaryButton>
                                <SecondaryButton @click="addNpcToScene(npc, 'neutral')">В нейтралы</SecondaryButton>
                                <SecondaryButton @click="addNpcToScene(npc, 'ally')">В команду</SecondaryButton>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </Modal>

        <Modal :show="showBackgroundUploadModal" max-width="lg" @close="showBackgroundUploadModal = false">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="fantasy-kicker">Scene background</p>
                        <h2 class="text-xl font-semibold text-amber-50">Загрузить фон</h2>
                    </div>
                    <SecondaryButton @click="showBackgroundUploadModal = false">Close</SecondaryButton>
                </div>
                <form class="mt-6 space-y-5" @submit.prevent="submitBackground">
                    <div>
                        <InputLabel for="background-title" value="Название" />
                        <TextInput id="background-title" v-model="backgroundForm.title" class="mt-2 block w-full" />
                        <InputError class="mt-2" :message="backgroundForm.errors.title" />
                    </div>
                    <div>
                        <InputLabel for="background-image" value="Изображение" />
                        <input id="background-image" type="file" accept="image/*" class="fantasy-file mt-2 block w-full" @change="setBackgroundImage" />
                        <InputError class="mt-2" :message="backgroundForm.errors.image" />
                    </div>
                    <label class="flex items-center gap-3 text-sm text-stone-300">
                        <input v-model="backgroundForm.apply_to_session" type="checkbox" class="rounded border-amber-300/20 bg-stone-950 text-amber-500" />
                        Сразу применить к сцене
                    </label>
                    <PrimaryButton :disabled="backgroundForm.processing">{{ backgroundForm.processing ? 'Загрузка...' : 'Загрузить фон' }}</PrimaryButton>
                </form>
            </div>
        </Modal>

        <Modal :show="showDiceModal" max-width="lg" @close="showDiceModal = false">
            <div class="border border-amber-300/10 bg-[linear-gradient(135deg,rgba(251,191,36,0.08),transparent_32%),linear-gradient(180deg,rgba(28,25,23,0.98),rgba(12,10,9,0.98))] p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="fantasy-kicker">Бросок кубика</p>
                        <h2 class="text-2xl font-semibold uppercase tracking-[0.12em] text-amber-50">{{ selectedDiceType }}</h2>
                        <p class="mt-2 text-sm text-stone-400">Выберите источник броска, характеристику и ручной модификатор. Если характеристика не нужна, оставьте режим "Без характеристики".</p>
                    </div>
                    <SecondaryButton @click="showDiceModal = false">Закрыть</SecondaryButton>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitRoll">
                    <div>
                        <InputLabel for="hud-dice-count" value="Количество кубиков" />
                        <input id="hud-dice-count" v-model.number="diceForm.dice_count" type="number" min="1" max="20" class="fantasy-input mt-2 block w-full text-lg" />
                        <InputError class="mt-2" :message="diceForm.errors.dice_count" />
                    </div>
                    <div>
                        <InputLabel v-if="can_manage_sessions" for="hud-dice-source" value="Источник броска" />
                        <InputLabel v-else value="Источник броска" />
                        <template v-if="can_manage_sessions">
                            <select id="hud-dice-source" :value="currentRollSourceValue" class="fantasy-select mt-2 block w-full text-lg" @change="updateRollSource($event.target.value)">
                                <option value="">Без источника</option>
                                <option v-for="actor in availableRollActors" :key="`hud-${actor.source_type}:${actor.source_id}`" :value="`${actor.source_type}:${actor.source_id}`">
                                    {{ actor.name }}{{ actor.source_type === 'scene_npc' ? ' · NPC' : ' · Персонаж' }}
                                </option>
                            </select>
                        </template>
                        <div v-else class="mt-2 rounded-lg border border-amber-300/20 bg-stone-950/60 px-4 py-3 text-sm text-amber-50">
                            {{ playerRollActor?.name ?? 'Без источника' }}
                        </div>
                        <InputError class="mt-2" :message="diceForm.errors.source_id" />
                    </div>
                    <div>
                        <InputLabel for="hud-dice-attribute" value="Характеристика" />
                        <select id="hud-dice-attribute" v-model="diceForm.attribute_key" class="fantasy-select mt-2 block w-full text-lg" :disabled="!selectedRollActor || selectedRollAttributes.length === 0">
                            <option value="">Без характеристики</option>
                            <option v-for="attribute in selectedRollAttributes" :key="`hud-attr-${attribute.key}`" :value="attribute.key">
                                {{ attribute.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="diceForm.errors.attribute_key" />
                        <p class="mt-2 text-sm text-stone-400">{{ selectedRollAttributeLabel }}</p>
                    </div>
                    <div>
                        <InputLabel for="hud-dice-modifier" value="Ручной модификатор" />
                        <input id="hud-dice-modifier" v-model.number="diceForm.modifier" type="number" min="-100" max="100" class="fantasy-input mt-2 block w-full text-lg" />
                        <InputError class="mt-2" :message="diceForm.errors.modifier" />
                        <p class="mt-2 text-sm text-stone-400">Итоговый модификатор к броску: {{ signedNumber(finalRollModifier) }}</p>
                    </div>
                    <PrimaryButton :disabled="diceForm.processing" class="w-full justify-center">
                        {{ diceForm.processing ? 'Бросаем...' : 'Бросить' }}
                    </PrimaryButton>
                </form>
            </div>
        </Modal>

        <Modal :show="showCharacterModal" max-width="2xl" @close="showCharacterModal = false">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="fantasy-kicker">Character</p>
                        <h2 class="text-xl font-semibold text-amber-50">{{ activeCharacter?.name }}</h2>
                        <p v-if="activeCharacter?.origin" class="mt-1 text-sm text-stone-400">{{ activeCharacter.origin }}</p>
                    </div>
                    <SecondaryButton @click="showCharacterModal = false">Close</SecondaryButton>
                </div>

                <div class="mt-6 flex gap-2">
                    <button type="button" class="rounded-md px-4 py-2 text-sm font-semibold" :class="characterModalTab === 'stats' ? 'bg-amber-500 text-stone-950' : 'bg-stone-950 text-stone-300'" @click="characterModalTab = 'stats'">
                        Stats
                    </button>
                    <button type="button" class="rounded-md px-4 py-2 text-sm font-semibold" :class="characterModalTab === 'inventory' ? 'bg-amber-500 text-stone-950' : 'bg-stone-950 text-stone-300'" @click="characterModalTab = 'inventory'">
                        Inventory
                    </button>
                </div>

                <form v-if="characterModalTab === 'stats' && can_manage_sessions" class="mt-6 space-y-6" @submit.prevent="updateGmCharacterSheet">
                    <section>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Attributes</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <label v-for="item in templateItems('attributes')" :key="`gm-attr-${item.key}`" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                <span class="text-sm text-stone-400">{{ item.label }}</span>
                                <input v-model.number="gmSheetForm.attribute_values[item.key]" type="number" class="fantasy-input mt-2 block w-full" />
                            </label>
                        </div>
                    </section>
                    <section>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Skills</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label v-for="item in skillTemplateItems" :key="`gm-skill-${item.key}`" class="flex items-center justify-between gap-3 rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                <span class="text-sm text-stone-400">{{ item.label }}</span>
                                <span class="inline-flex items-center gap-3 rounded-full border border-white/8 bg-white/[0.04] px-3 py-2 text-sm text-stone-200">
                                    <input v-model="gmSheetForm.skill_values[item.key]" type="checkbox" class="peer sr-only" />
                                    <span class="relative h-6 w-11 rounded-full bg-stone-700/80 transition after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:bg-teal-500/70 peer-checked:after:translate-x-5" />
                                    {{ gmSheetForm.skill_values[item.key] ? 'Есть' : 'Нет' }}
                                </span>
                            </label>
                        </div>
                    </section>
                    <section v-if="extraTemplateItems.length">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Details</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label v-for="item in extraTemplateItems" :key="`gm-extra-${item.key}`" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                <span class="text-sm text-stone-400">{{ item.label }}</span>
                                <input v-model="gmSheetForm.extra_field_values[item.key]" :type="item.type === 'number' ? 'number' : 'text'" class="fantasy-input mt-2 block w-full" />
                            </label>
                        </div>
                    </section>
                    <PrimaryButton :disabled="gmSheetForm.processing">{{ gmSheetForm.processing ? 'Saving...' : 'Save stats' }}</PrimaryButton>
                </form>

                <div v-else-if="characterModalTab === 'stats'" class="mt-6 space-y-6">
                    <section>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Attributes</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <div v-for="item in templateItems('attributes')" :key="item.key" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                <p class="text-sm text-stone-400">{{ item.label }}</p>
                                <p class="mt-1 text-2xl font-semibold text-amber-50">{{ characterValue(activeCharacter?.attribute_values, item) }}</p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Skills</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <div v-for="item in skillTemplateItems" :key="item.key" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                <p class="text-sm text-stone-400">{{ item.label }}</p>
                                <p class="mt-1 text-2xl font-semibold text-amber-50">{{ skillValue(activeCharacter?.skill_values, item) }}</p>
                            </div>
                        </div>
                    </section>

                    <section v-if="extraTemplateItems.length">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Details</h3>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div v-for="item in extraTemplateItems" :key="item.key" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                                <p class="text-sm text-stone-400">{{ item.label }}</p>
                                <p class="mt-1 text-sm text-amber-50">{{ characterValue(activeCharacter?.extra_field_values, item) || '-' }}</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-else class="mt-6">
                    <div v-if="can_manage_sessions && activeCharacter" class="mb-6 space-y-4 rounded-lg border border-amber-300/15 bg-stone-950/45 p-4">
                        <form class="grid gap-3 sm:grid-cols-[1fr_7rem_auto] sm:items-end" @submit.prevent="submitCatalogItem(activeCharacter.id)">
                            <div>
                                <InputLabel :for="`modal-catalog-${activeCharacter.id}`" value="Предмет из базы" />
                                <select :id="`modal-catalog-${activeCharacter.id}`" v-model="inventoryForms[activeCharacter.id].item_id" class="fantasy-select mt-2 block w-full">
                                    <option value="">Выберите предмет</option>
                                    <option v-for="item in inventory.catalog_items" :key="item.id" :value="item.id">{{ item.name }}{{ item.category ? ` (${item.category})` : '' }}</option>
                                </select>
                            </div>
                            <input v-model.number="inventoryForms[activeCharacter.id].quantity" type="number" min="1" class="fantasy-input" />
                            <PrimaryButton :disabled="inventoryForms[activeCharacter.id].processing">Добавить</PrimaryButton>
                        </form>

                        <details class="rounded-lg border border-stone-600/40 bg-stone-950/50 p-4">
                            <summary class="cursor-pointer text-sm font-semibold text-amber-50">Кастомный предмет</summary>
                            <form class="mt-4 space-y-3" @submit.prevent="submitCustomItem(activeCharacter.id)">
                                <TextInput v-model="customInventoryForms[activeCharacter.id].custom_name" class="block w-full" placeholder="Название" />
                                <textarea v-model="customInventoryForms[activeCharacter.id].custom_description" class="fantasy-textarea block w-full" placeholder="Описание" />
                                <input type="file" accept="image/*" class="fantasy-file block w-full" @change="setCustomImage(activeCharacter.id, $event)" />
                                <input v-model.number="customInventoryForms[activeCharacter.id].quantity" type="number" min="1" class="fantasy-input block w-32" />
                                <PrimaryButton :disabled="customInventoryForms[activeCharacter.id].processing">Добавить кастомный</PrimaryButton>
                            </form>
                        </details>
                    </div>

                    <div v-if="activeCharacterInventory.length" class="grid gap-3 sm:grid-cols-2">
                        <article v-for="item in activeCharacterInventory" :key="item.id" class="rounded-lg border border-stone-600/40 bg-stone-950/60 p-4">
                            <div class="flex gap-4">
                                <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-14 w-14 rounded-lg object-cover" />
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-amber-50">{{ item.name }}</p>
                                    <p class="mt-1 text-sm text-stone-400">Qty: {{ item.quantity }}</p>
                                    <p v-if="item.description" class="mt-2 text-sm text-stone-300">{{ item.description }}</p>
                                </div>
                            </div>
                            <div v-if="can_manage_sessions && activeCharacter" class="mt-4 flex items-center gap-2">
                                <form class="flex items-center gap-2" @submit.prevent="updateInventoryQuantity(activeCharacter.id, item.id)">
                                    <input v-if="quantityForms[item.id]" v-model.number="quantityForms[item.id].quantity" type="number" min="1" class="fantasy-input w-20" />
                                    <PrimaryButton v-if="quantityForms[item.id]" :disabled="quantityForms[item.id].processing">Qty</PrimaryButton>
                                </form>
                                <form @submit.prevent="removeInventoryItem(activeCharacter.id, item.id)">
                                    <DangerButton>Удалить</DangerButton>
                                </form>
                            </div>
                        </article>
                    </div>
                    <p v-else class="text-sm text-stone-400">Inventory is empty.</p>
                </div>
            </div>
        </Modal>

        <Modal :show="showImagePreviewModal" max-width="fit" @close="showImagePreviewModal = false">
            <div class="relative flex flex-col items-center gap-5 bg-stone-950 p-6 text-stone-100">
                <SecondaryButton class="absolute right-6 top-6" @click="showImagePreviewModal = false">Закрыть</SecondaryButton>
                <h2 class="px-16 text-center text-xl font-semibold text-amber-50">{{ previewImageEntity?.name ?? 'Персонаж' }}</h2>
                <img
                    v-if="previewImageEntity?.avatar_url"
                    :src="previewImageEntity.avatar_url"
                    :alt="previewImageEntity.name"
                    class="h-[80vh] max-w-[90vw] rounded-xl object-contain"
                />
                <p v-else class="text-sm text-stone-400">Изображение не загружено.</p>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
