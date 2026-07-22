<script setup>
import FantasyPanel from '@/Components/Fantasy/FantasyPanel.vue';

defineProps({
    notes: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <FantasyPanel title="Журнал мастера" kicker="Рабочий сценарий" icon="/storage/ui/icons/Notes.png" dense>
        <div class="gm-journal-list">
            <div v-for="note in notes" :key="note">
                {{ note }}
            </div>
        </div>
    </FantasyPanel>
</template>
