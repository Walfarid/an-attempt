<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header
        :class="
            variant === 'small'
                ? 'border-b border-(--rule) pb-4'
                : 'mb-6 space-y-1'
        "
    >
        <div class="flex items-baseline gap-3">
            <h2
                :class="
                    variant === 'small'
                        ? 'font-display text-sm font-semibold tracking-tight'
                        : 'font-display text-xl font-bold tracking-tight'
                "
            >
                {{ title }}
            </h2>
        </div>
        <p v-if="description" class="text-sm text-(--ink-soft)">
            {{ description }}
        </p>
    </header>
</template>
