<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" class="mt-1 font-mono text-xs text-destructive">
        <span class="text-destructive">※</span> {{ message }}
    </div>
</template>
