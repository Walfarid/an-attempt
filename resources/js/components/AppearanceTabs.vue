<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div class="d-surface inline-flex">
        <div class="grid w-full grid-cols-3">
            <button
                v-for="{ value, Icon, label } in tabs"
                :key="value"
                @click="updateAppearance(value)"
                :class="[
                    'flex items-center justify-center gap-2 px-4 py-3 font-mono text-xs tracking-wider uppercase transition-colors',
                    appearance === value
                        ? 'bg-accent text-accent-foreground'
                        : 'text-ink-soft hover:bg-accent-soft hover:text-ink',
                ]"
            >
                <component :is="Icon" class="h-4 w-4" />
                <span>{{ label }}</span>
            </button>
        </div>
    </div>
</template>
