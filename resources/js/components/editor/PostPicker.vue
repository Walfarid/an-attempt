<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, ref } from 'vue';
import type { PostOption } from '@/data/portfolio';

/**
 * Multiselect for linking posts to a guide: chips for the selected
 * posts plus an add-on-click list of the remaining titles. Mirrors
 * TagInput's chip idiom, but picks from a fixed option set instead of
 * free-form names. Backspace removes the last chip; click toggles.
 */
const props = withDefaults(
    defineProps<{
        modelValue: number[];
        options: PostOption[];
        max?: number;
    }>(),
    {
        max: 10,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const showingOptions = ref(false);

const selected = computed(() =>
    props.options.filter((o) => props.modelValue.includes(o.id)),
);

const remaining = computed(() =>
    props.options.filter((o) => !props.modelValue.includes(o.id)),
);

function toggle(id: number) {
    if (props.modelValue.includes(id)) {
        const next = props.modelValue.filter((v) => v !== id);
        emit('update:modelValue', next);

        return;
    }

    if (props.modelValue.length >= props.max) {
        return;
    }

    emit('update:modelValue', [...props.modelValue, id]);
}

function removeAt(index: number) {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
}

function onBackspace() {
    if (props.modelValue.length > 0) {
        removeAt(props.modelValue.length - 1);
    }
}
</script>

<template>
    <div
        class="relative flex flex-wrap items-center gap-1.5 rounded-md border border-input px-2 py-1.5 dark:bg-input/30"
    >
        <span
            v-for="(option, i) in selected"
            :key="option.id"
            class="inline-flex items-center gap-1 rounded-sm border bg-secondary px-1.5 py-0.5 text-xs font-medium text-secondary-foreground"
        >
            {{ option.title }}
            <button
                type="button"
                :aria-label="`Remove ${option.title}`"
                class="opacity-60 transition-opacity hover:opacity-100"
                @click="removeAt(i)"
            >
                <X class="size-3" />
            </button>
        </span>

        <div class="relative min-w-32 flex-1">
            <button
                type="button"
                class="w-full py-0.5 text-left text-sm outline-none"
                placeholder="Search posts…"
                aria-haspopup="listbox"
                :aria-expanded="showingOptions"
                @click="showingOptions = !showingOptions"
                @keydown.enter.prevent="showingOptions = !showingOptions"
                @keydown.backspace="onBackspace"
                @blur="showingOptions = false"
            >
                <span v-if="selected.length === 0" class="text-muted-foreground"
                    >Select posts…</span
                >
            </button>

            <ul
                v-if="showingOptions"
                role="listbox"
                class="absolute left-0 z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <li v-for="option in remaining" :key="option.id" role="option">
                    <button
                        type="button"
                        class="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                        @mousedown.prevent="toggle(option.id)"
                    >
                        {{ option.title }}
                    </button>
                </li>
                <li
                    v-if="remaining.length === 0"
                    class="px-2 py-1.5 text-sm text-muted-foreground"
                >
                    No more posts to link.
                </li>
            </ul>
        </div>
    </div>
</template>
