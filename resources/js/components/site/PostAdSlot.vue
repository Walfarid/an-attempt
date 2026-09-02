<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    id: number;
    className?: string;
}>();

const el = ref<HTMLElement | null>(null);

/** Empty meaning no ad content or iframe filled the slot (blocked/not served). */
const isEmpty = ref(true);

const visible = computed(() => {
    const el = document.querySelector('.ezoic-ad-slot:not([data-empty])');
    return !!el;
});

function showAd() {
    const slot = el.value;

    if (!slot || typeof window === 'undefined') {
        return;
    }

    const ezstandalone = (window as unknown as { ezstandalone?: { cmd?: unknown[] } })
        .ezstandalone;

    if (!ezstandalone?.cmd) {
        return;
    }

    ezstandalone.cmd.push(() => {
        const api = (
            window as unknown as {
                ezstandalone?: { showAds?: (id: number) => void };
            }
        ).ezstandalone;

        api?.showAds?.(props.id);

        // Ezoic fills the slot asynchronously; wait a beat for the first
        // ad element/iframe before deciding the slot stayed empty.
        window.setTimeout(() => {
            const filled = slot.querySelector('iframe, ins, img, [id^="ezoic-pub-ad-placeholder-"] iframe, [class*="ad"]');

            if (filled) {
                isEmpty.value = false;
            }
        }, 2500);
    });
}

onMounted(showAd);
</script>

<template>
    <aside
        ref="el"
        :class="[
            'ezoic-ad-slot',
            'd-surface flex min-h-20 items-center justify-center border border-dashed p-3',
            className,
        ]"
        :data-empty="isEmpty"
    >
        <p v-if="isEmpty" class="text-xs text-(--ink-soft)" data-ad-note>
            Please allow ads to support me
        </p>
        <div v-else class="hidden" aria-hidden="true" />
    </aside>
</template>