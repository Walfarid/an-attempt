<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <path
            d="M3.6 5.8 7.8 18.2 12 6.6 16.2 18.2 20.4 5.8"
            stroke="currentColor"
            stroke-width="3.6"
            stroke-linecap="square"
            stroke-linejoin="miter"
        />
    </svg>
</template>
