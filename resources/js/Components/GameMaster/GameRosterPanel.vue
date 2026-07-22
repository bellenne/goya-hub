<script setup>
import InputError from '@/Components/InputError.vue';
import FantasyBadge from '@/Components/Fantasy/FantasyBadge.vue';
import FantasyButton from '@/Components/Fantasy/FantasyButton.vue';
import FantasyPanel from '@/Components/Fantasy/FantasyPanel.vue';
import FantasySelect from '@/Components/Fantasy/FantasySelect.vue';

defineProps({
    game: {
        type: Object,
        required: true,
    },
    roleOptions: {
        type: Array,
        required: true,
    },
    formForMember: {
        type: Function,
        required: true,
    },
});

defineEmits(['update-role']);
</script>

<template>
    <FantasyPanel title="Текущая партия" kicker="Состав игры" icon="/storage/ui/icons/players.png" dense>
        <template #actions>
            <FantasyBadge>{{ game.members.length }} участников</FantasyBadge>
        </template>

        <div class="space-y-3">
            <article v-for="member in game.members" :key="member.id" class="gm-roster-row">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="gm-roster-avatar">
                        <img src="/storage/ui/icons/Profile.png" alt="" />
                    </span>
                    <span class="min-w-0">
                        <strong class="block truncate text-base text-[#fff1c8]">{{ member.user.name }}</strong>
                        <span class="block truncate text-sm text-[#a99674]">{{ member.user.email }}</span>
                    </span>
                </div>

                <div class="flex flex-col gap-3 lg:items-end">
                    <FantasyBadge tone="muted">{{ member.role_label }}</FantasyBadge>

                    <div
                        v-if="game.can_manage_member_roles && member.user.id !== game.owner.id"
                        class="grid w-full gap-2 sm:grid-cols-[minmax(0,1fr)_auto] lg:w-[28rem]"
                    >
                        <div>
                            <label class="gm-kicker">Роль</label>
                            <FantasySelect v-model="formForMember(member).role" class="mt-2 w-full">
                                <option v-for="option in roleOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </FantasySelect>
                            <InputError class="mt-2" :message="formForMember(member).errors.role" />
                        </div>

                        <FantasyButton :disabled="formForMember(member).processing" @click="$emit('update-role', member)">
                            Сохранить
                        </FantasyButton>
                    </div>
                </div>
            </article>
        </div>
    </FantasyPanel>
</template>
