<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    gameId: { type: Number, required: true },
});

const emit = defineEmits(['close']);

const notes = ref([]);
const activeNoteId = ref(null);
const search = ref('');
const isLoading = ref(false);
const isSaving = ref(false);
const isUploading = ref(false);
const saveState = ref('idle');
const errorMessage = ref('');
const sketchCanvas = ref(null);
let autosaveTimeout = null;
let isDrawing = false;
let loadedSketchUrl = null;
const noteEditVersions = new Map();

const activeNote = computed(() => notes.value.find((note) => note.id === activeNoteId.value) ?? null);
const filteredNotes = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) return notes.value;

    return notes.value.filter((note) => note.title.toLowerCase().includes(query));
});

const noteSummary = (note) => {
    const text = String(note.content ?? '').replace(/\s+/g, ' ').trim();

    return text || (note.attachments.length ? `${note.attachments.length} изображ.` : 'Пустая заметка');
};

const replaceNote = (updatedNote, options = {}) => {
    notes.value = notes.value.map((note) => {
        if (note.id !== updatedNote.id) return note;

        if (options.preserveEditableFields) {
            return {
                ...updatedNote,
                title: note.title,
                content: note.content,
            };
        }

        return updatedNote;
    });
};

const loadNotes = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.get(route('games.notes.index', props.gameId));
        notes.value = response.data.notes ?? [];
        activeNoteId.value = notes.value[0]?.id ?? null;
        await nextTick();
        loadSketch();
    } catch (error) {
        errorMessage.value = 'Не удалось загрузить заметки.';
    } finally {
        isLoading.value = false;
    }
};

const createNote = async () => {
    errorMessage.value = '';

    try {
        const response = await window.axios.post(route('games.notes.store', props.gameId), {
            title: 'Новая заметка',
        });
        notes.value = [response.data.note, ...notes.value];
        activeNoteId.value = response.data.note.id;
        await nextTick();
        clearSketch(false);
    } catch (error) {
        errorMessage.value = 'Не удалось создать заметку.';
    }
};

const selectNote = async (noteId) => {
    activeNoteId.value = noteId;
    saveState.value = 'idle';
    await nextTick();
    loadSketch();
};

const scheduleAutosave = () => {
    if (!activeNote.value) return;

    noteEditVersions.set(activeNote.value.id, (noteEditVersions.get(activeNote.value.id) ?? 0) + 1);
    saveState.value = 'dirty';
    if (autosaveTimeout) clearTimeout(autosaveTimeout);
    autosaveTimeout = setTimeout(saveActiveNote, 650);
};

const saveActiveNote = async (extraPayload = {}) => {
    if (!activeNote.value) return;

    isSaving.value = true;
    saveState.value = 'saving';
    const note = activeNote.value;
    const noteId = note.id;
    const requestEditVersion = noteEditVersions.get(noteId) ?? 0;

    try {
        const response = await window.axios.patch(route('games.notes.update', [props.gameId, noteId]), {
            title: note.title,
            content: note.content,
            ...extraPayload,
        });
        const currentEditVersion = noteEditVersions.get(noteId) ?? 0;
        const hasNewerLocalText = currentEditVersion !== requestEditVersion;
        replaceNote(response.data.note, { preserveEditableFields: hasNewerLocalText });

        if (activeNoteId.value === noteId && !hasNewerLocalText) {
            saveState.value = 'saved';
            setTimeout(() => {
                if (saveState.value === 'saved') saveState.value = 'idle';
            }, 1400);
        } else if (activeNoteId.value === noteId) {
            saveState.value = 'dirty';
        }
    } catch (error) {
        if (activeNoteId.value === noteId && (noteEditVersions.get(noteId) ?? 0) === requestEditVersion) {
            saveState.value = 'error';
        }
        errorMessage.value = 'Не удалось сохранить заметку.';
    } finally {
        isSaving.value = false;
    }
};

const deleteNote = async () => {
    if (!activeNote.value || !confirm('Удалить заметку?')) return;

    const noteId = activeNote.value.id;

    try {
        await window.axios.delete(route('games.notes.destroy', [props.gameId, noteId]));
        notes.value = notes.value.filter((note) => note.id !== noteId);
        activeNoteId.value = notes.value[0]?.id ?? null;
        await nextTick();
        loadSketch();
    } catch (error) {
        errorMessage.value = 'Не удалось удалить заметку.';
    }
};

const uploadAttachment = async (event) => {
    if (!activeNote.value || !event.target.files?.[0]) return;

    const noteId = activeNote.value.id;
    const requestEditVersion = noteEditVersions.get(noteId) ?? 0;
    const formData = new FormData();
    formData.append('image', event.target.files[0]);
    isUploading.value = true;

    try {
        const response = await window.axios.post(
            route('games.notes.attachments.store', [props.gameId, noteId]),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } },
        );
        replaceNote(response.data.note, {
            preserveEditableFields: (noteEditVersions.get(noteId) ?? 0) !== requestEditVersion,
        });
    } catch (error) {
        errorMessage.value = 'Не удалось прикрепить изображение.';
    } finally {
        event.target.value = '';
        isUploading.value = false;
    }
};

const deleteAttachment = async (attachment) => {
    if (!activeNote.value) return;

    const noteId = activeNote.value.id;
    const requestEditVersion = noteEditVersions.get(noteId) ?? 0;

    try {
        const response = await window.axios.delete(route('games.notes.attachments.destroy', [
            props.gameId,
            noteId,
            attachment.id,
        ]));
        replaceNote(response.data.note, {
            preserveEditableFields: (noteEditVersions.get(noteId) ?? 0) !== requestEditVersion,
        });
    } catch (error) {
        errorMessage.value = 'Не удалось удалить изображение.';
    }
};

const canvasContext = () => {
    const canvas = sketchCanvas.value;
    if (!canvas) return null;

    const context = canvas.getContext('2d');
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.lineWidth = 3;
    context.strokeStyle = '#fbbf24';

    return context;
};

const prepareCanvas = () => {
    const canvas = sketchCanvas.value;
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.max(1, Math.floor(rect.width * ratio));
    canvas.height = Math.max(1, Math.floor(rect.height * ratio));
    const context = canvasContext();
    context.scale(ratio, ratio);
    context.fillStyle = 'rgba(12, 10, 9, 0.94)';
    context.fillRect(0, 0, rect.width, rect.height);
};

const loadSketch = () => {
    prepareCanvas();
    const note = activeNote.value;

    if (!note?.sketch_url || note.sketch_url === loadedSketchUrl) {
        loadedSketchUrl = note?.sketch_url ?? null;
        return;
    }

    loadedSketchUrl = note.sketch_url;
    const canvas = sketchCanvas.value;
    const context = canvasContext();
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.onload = () => {
        const rect = canvas.getBoundingClientRect();
        context.drawImage(image, 0, 0, rect.width, rect.height);
    };
    image.src = note.sketch_url;
};

const pointerPoint = (event) => {
    const rect = sketchCanvas.value.getBoundingClientRect();

    return {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
    };
};

const startDrawing = (event) => {
    if (!activeNote.value) return;
    isDrawing = true;
    const context = canvasContext();
    const point = pointerPoint(event);
    context.beginPath();
    context.moveTo(point.x, point.y);
};

const draw = (event) => {
    if (!isDrawing) return;
    const context = canvasContext();
    const point = pointerPoint(event);
    context.lineTo(point.x, point.y);
    context.stroke();
};

const stopDrawing = () => {
    isDrawing = false;
};

const saveSketch = async () => {
    if (!activeNote.value || !sketchCanvas.value) return;

    await saveActiveNote({ sketch_data: sketchCanvas.value.toDataURL('image/png') });
    loadedSketchUrl = activeNote.value?.sketch_url ?? null;
};

const clearSketch = async (persist = true) => {
    prepareCanvas();
    loadedSketchUrl = null;

    if (persist && activeNote.value) {
        await saveActiveNote({ clear_sketch: true });
    }
};

watch(() => props.show, async (isOpen) => {
    if (isOpen) {
        await loadNotes();
    } else if (autosaveTimeout) {
        clearTimeout(autosaveTimeout);
        autosaveTimeout = null;
    }
});

watch(activeNoteId, async () => {
    await nextTick();
    loadSketch();
});
</script>

<template>
    <Modal :show="show" max-width="7xl" @close="emit('close')">
        <div class="notes-modal-shell">
            <header class="flex flex-col gap-3 border-b border-amber-300/15 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Личный блокнот</p>
                    <h2 class="text-2xl font-semibold text-amber-50">Заметки</h2>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-stone-400">
                        <template v-if="saveState === 'saving' || isSaving">Сохранение...</template>
                        <template v-else-if="saveState === 'saved'">Сохранено</template>
                        <template v-else-if="saveState === 'dirty'">Есть изменения</template>
                        <template v-else-if="saveState === 'error'">Ошибка сохранения</template>
                    </span>
                    <SecondaryButton @click="emit('close')">Закрыть</SecondaryButton>
                </div>
            </header>

            <div v-if="errorMessage" class="gm-alert gm-alert-danger mx-5 mt-4">
                {{ errorMessage }}
            </div>

            <div class="grid min-h-[38rem] lg:grid-cols-[19rem_minmax(0,1fr)]">
                <aside class="theme-panel border-r p-4">
                    <div class="flex gap-2">
                        <input v-model="search" type="search" class="fantasy-input block min-w-0 flex-1" placeholder="Поиск" />
                        <PrimaryButton @click="createNote">+</PrimaryButton>
                    </div>

                    <div class="mt-4 max-h-[32rem] space-y-2 overflow-y-auto pr-1">
                        <button
                            v-for="note in filteredNotes"
                            :key="note.id"
                            type="button"
                            class="theme-list-row block w-full px-3 py-3 text-left"
                            :class="activeNote?.id === note.id ? 'theme-list-row-active' : ''"
                            @click="selectNote(note.id)"
                        >
                            <p class="truncate font-semibold text-amber-50">{{ note.title }}</p>
                            <p class="mt-1 line-clamp-2 text-xs leading-5 text-stone-400">{{ noteSummary(note) }}</p>
                        </button>
                        <p v-if="!isLoading && filteredNotes.length === 0" class="theme-empty p-4 text-sm">
                            Заметок пока нет.
                        </p>
                    </div>
                </aside>

                <section v-if="activeNote" class="grid gap-4 p-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div class="space-y-4">
                        <input
                            v-model="activeNote.title"
                            type="text"
                            maxlength="160"
                            class="fantasy-input block w-full text-lg font-semibold"
                            placeholder="Название"
                            @input="scheduleAutosave"
                        />
                        <textarea
                            v-model="activeNote.content"
                            class="fantasy-textarea min-h-[25rem] w-full resize-none text-sm leading-6"
                            placeholder="Пишите заметки по игре..."
                            @input="scheduleAutosave"
                        />
                        <div class="flex justify-between gap-3">
                            <SecondaryButton :disabled="isSaving" @click="saveActiveNote()">Сохранить</SecondaryButton>
                            <DangerButton @click="deleteNote">Удалить заметку</DangerButton>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <section class="notes-side-panel">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Изображения</h3>
                                <label class="gm-button cursor-pointer px-3 py-2 text-xs font-semibold">
                                    {{ isUploading ? 'Загрузка...' : 'Прикрепить' }}
                                    <input type="file" accept="image/*" class="hidden" :disabled="isUploading" @change="uploadAttachment" />
                                </label>
                            </div>
                            <div class="mt-3 grid gap-3">
                                <article v-for="attachment in activeNote.attachments" :key="attachment.id" class="theme-media">
                                    <img :src="attachment.url" :alt="attachment.name ?? 'attachment'" class="h-32 w-full object-contain" />
                                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                                        <p class="truncate text-xs text-stone-300">{{ attachment.name ?? 'image' }}</p>
                                        <button type="button" class="text-xs font-semibold text-red-200 hover:text-red-100" @click="deleteAttachment(attachment)">Удалить</button>
                                    </div>
                                </article>
                                <p v-if="activeNote.attachments.length === 0" class="text-sm text-stone-500">Изображений пока нет.</p>
                            </div>
                        </section>

                        <section class="notes-side-panel">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-200">Зарисовка</h3>
                            <canvas
                                ref="sketchCanvas"
                                class="mt-3 h-56 w-full touch-none rounded border border-amber-300/20 bg-transparent"
                                @pointerdown.prevent="startDrawing"
                                @pointermove.prevent="draw"
                                @pointerup.prevent="stopDrawing"
                                @pointerleave="stopDrawing"
                            />
                            <div class="mt-3 flex flex-wrap gap-2">
                                <SecondaryButton @click="saveSketch">Сохранить рисунок</SecondaryButton>
                                <SecondaryButton @click="clearSketch(true)">Очистить</SecondaryButton>
                            </div>
                        </section>
                    </div>
                </section>

                <section v-else class="grid place-items-center p-8 text-center text-stone-400">
                    <div>
                        <p class="text-lg font-semibold text-amber-50">Блокнот пуст</p>
                        <p class="mt-2 text-sm">Создайте первую заметку для этой игры.</p>
                        <PrimaryButton class="mt-5" @click="createNote">Создать заметку</PrimaryButton>
                    </div>
                </section>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.notes-modal-shell {
    overflow: hidden;
    border: 1px solid rgba(251, 191, 36, 0.14);
    background:
        radial-gradient(circle at 12% 0%, rgba(251, 191, 36, 0.12), transparent 24rem),
        radial-gradient(circle at 92% 12%, rgba(45, 212, 191, 0.08), transparent 22rem),
        linear-gradient(180deg, rgba(28, 25, 23, 0.98), rgba(12, 10, 9, 0.98));
    color: #e7e5e4;
}

.notes-side-panel {
    border-radius: 0.65rem;
    border: 1px solid rgba(120, 113, 108, 0.4);
    background: rgba(12, 10, 9, 0.52);
    padding: 1rem;
}
</style>
