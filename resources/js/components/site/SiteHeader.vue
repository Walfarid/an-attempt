<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Moon, Sun } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        /** Section links; plain hrefs so same-page anchors scroll natively. */
        links?: { href: string; label: string; external?: boolean }[];
    }>(),
    {
        links: () => [
            { href: '/#experience', label: 'Experience' },
            { href: '/#projects', label: 'Projects' },
            { href: '/writing', label: 'Writing' },
            { href: '/#contact', label: 'Contact' },
        ],
    },
);

const { appearance, updateAppearance } = useAppearance();
const systemPrefersDark = ref(
    typeof window !== 'undefined' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches,
);

const isDark = computed(
    () =>
        appearance.value === 'dark' ||
        (appearance.value === 'system' && systemPrefersDark.value),
);

const themeLabel = computed(() =>
    isDark.value ? 'Switch to light theme' : 'Switch to dark theme',
);

function toggleTheme() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b-2 border-(--site-ink) bg-(--site-bg)"
    >
        <nav
            class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6"
            aria-label="Primary"
        >
            <Link
                href="/"
                class="flex items-center gap-2.5 rounded-none"
                aria-label="Walfa — back to top"
            >
                <span
                    class="flex size-9 items-center justify-center border-2 border-(--site-ink) bg-(--site-panel) font-mono text-base font-semibold"
                    aria-hidden="true"
                    >W</span
                >
                <span class="text-lg font-bold tracking-tight">Walfa</span>
            </Link>

            <ul class="hidden items-center gap-1 sm:flex">
                <li v-for="item in links" :key="item.href">
                    <Link
                        v-if="item.external"
                        :href="item.href"
                        class="b-label inline-flex min-h-11 items-center px-3 no-underline transition-colors hover:bg-(--site-panel-inset)"
                    >
                        {{ item.label }}
                    </Link>
                    <a
                        v-else
                        :href="item.href"
                        class="b-label inline-flex min-h-11 items-center px-3 no-underline transition-colors hover:bg-(--site-panel-inset)"
                    >
                        {{ item.label }}
                    </a>
                </li>
            </ul>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="b-panel b-shadow-sm inline-flex size-11 items-center justify-center transition-colors hover:bg-(--site-panel-inset)"
                    :aria-label="themeLabel"
                    @click="toggleTheme"
                >
                    <Sun v-if="isDark" class="size-5" aria-hidden="true" />
                    <Moon v-else class="size-5" aria-hidden="true" />
                </button>

                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="b-shadow-sm inline-flex min-h-11 items-center gap-1.5 border-2 border-(--site-ink) bg-(--site-accent) px-4 py-2 text-sm font-semibold text-(--site-on-accent) no-underline"
                >
                    Dashboard
                    <ArrowUpRight class="size-4" aria-hidden="true" />
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="b-shadow-sm inline-flex min-h-11 items-center border-2 border-(--site-ink) bg-(--site-panel) px-4 py-2 text-sm font-semibold no-underline"
                >
                    Log in
                </Link>
            </div>
        </nav>
    </header>
</template>
