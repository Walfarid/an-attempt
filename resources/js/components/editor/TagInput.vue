<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, ref } from 'vue';

/**
 * Free-form tag input for the dashboard post editor: chips with an
 * inline text field, suggestions drawn from tags already used on
 * other posts. Keyboard: Enter adds, Backspace removes the last chip.
 */
const props = withDefaults(
    defineProps<{
        modelValue: string[];
        suggestions?: string[];
        max?: number;
    }>(),
    {
        suggestions: () => [],
        max: 10,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const draft = ref('');
const showingSuggestions = ref(false);

const remaining = computed(() => {
    const used = props.modelValue.map((t) => t.toLowerCase());

    return props.suggestions.filter((s) => !used.includes(s.toLowerCase()));
});

function add(value: string) {
    const name = value.trim();

    if (
        name === '' ||
        props.modelValue.length >= props.max ||
        props.modelValue.some((t) => t.toLowerCase() === name.toLowerCase())
    ) {
        draft.value = '';

        return;
    }

    emit('update:modelValue', [...props.modelValue, name]);
    draft.value = '';
}

function removeAt(index: number) {
    const next = [...props.modelValue];
    next.splice(index, 1);
    emit('update:modelValue', next);
}

function onBackspace() {
    if (draft.value === '' && props.modelValue.length > 0) {
        removeAt(props.modelValue.length - 1);
    }
}
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-1.5 rounded-md border border-input px-2 py-1.5 dark:bg-input/30"
    >
        <span
            v-for="(tag, i) in modelValue"
            :key="tag"
            class="inline-flex items-center gap-1 rounded-sm border bg-secondary px-1.5 py-0.5 text-xs font-medium text-secondary-foreground"
        >
            {{ tag }}
            <button
                type="button"
                :aria-label="`Remove tag ${tag}`"
                class="opacity-60 transition-opacity hover:opacity-100"
                @click="removeAt(i)"
            >
                <X class="size-3" aria-hidden="true" />
            </button>
        </span>

        <input
            v-model="draft"
            type="text"
            class="min-w-24 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
            placeholder="Add a tag…"
            :aria-label="'Post tags'"
            @keydown.enter.prevent="add(draft)"
            @keydown.backspace="onBackspace"
            @focus="showingSuggestions = true"
            @blur="showingSuggestions = false"
        />

        <ul
            v-if="showingSuggestions && remaining.length && draft === ''"
            class="absolute z-10 mt-1 max-h-32 w-full overflow-y-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <li v-for="s in remaining.slice(0, 6)" :key="s">
                <button
                    type="button"
                    class="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                    @mousedown.prevent="add(s)"
                >
                    {{ s }}
                </button>
            </li>
        </ul>
    </div>
</template>
