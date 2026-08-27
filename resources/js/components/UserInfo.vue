<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute the avatar image URL - use DiceBear generated avatar as default
const avatarUrl = computed(() => {
    if (props.user.avatar && props.user.avatar !== '') {
        return props.user.avatar;
    }

    // Generate a default avatar using DiceBear
    const seed = encodeURIComponent(props.user.name || props.user.email);

    return `https://api.dicebear.com/9.2/initials/svg?seed=${seed}&backgroundColor=b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf&fontSize=36&fontFamily=Arial&fontColor=263238`;
});
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden border border-(--rule)">
        <AvatarImage :src="avatarUrl" :alt="user.name" />
        <AvatarFallback
            class="bg-(--accent-soft) font-mono text-xs text-(--ink)"
        >
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-mono text-[11px] font-medium">{{
            user.name
        }}</span>
        <span
            v-if="showEmail"
            class="truncate font-mono text-[10px] text-(--ink-soft)"
            >{{ user.email }}</span
        >
    </div>
</template>
