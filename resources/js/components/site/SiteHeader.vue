<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Menu, Moon, Sun, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        links?: {
            href: string;
            label: string;
            external?: boolean;
            inertia?: boolean;
        }[];
    }>(),
    {
        links: () => [
            { href: '/#experience', label: 'Experience' },
            { href: '/#projects', label: 'Projects' },
            { href: '/#skills', label: 'Skills' },
            { href: '/posts', label: 'Writing', inertia: true },
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

const { url } = usePage();

/**
 * Active nav state. Hash anchors on the landing page are scroll-driven,
 * so only real routes are marked (currently the Writing index).
 */
function isActive(href: string) {
    return href === '/posts' && url.startsWith('/posts');
}

const mobileOpen = ref(false);
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-(--rule) bg-(--paper)/90 backdrop-blur-sm"
    >
        <nav
            class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6"
            aria-label="Primary"
        >
            <Link
                href="/"
                class="flex items-center gap-2"
                aria-label="Walfa — back to top"
            >
                <span
                    class="flex size-8 items-center justify-center border border-(--rule) bg-(--surface) text-sm font-bold"
                    aria-hidden="true"
                    >W</span
                >
                <span class="font-display text-base font-bold tracking-tight"
                    >Walfa</span
                >
            </Link>

            <!-- Desktop nav -->
            <ul class="hidden items-center gap-0.5 md:flex">
                <li v-for="item in links" :key="item.href">
                    <Link
                        v-if="item.external"
                        :href="item.href"
                        class="inline-flex min-h-10 items-center px-3 text-[13px] font-medium no-underline transition-colors"
                        :class="
                            isActive(item.href)
                                ? 'text-(--accent)'
                                : 'text-(--ink-soft) hover:text-(--ink)'
                        "
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </Link>
                    <Link
                        v-else-if="item.inertia"
                        :href="item.href"
                        prefetch
                        cache-for="10s"
                        class="inline-flex min-h-10 items-center px-3 text-[13px] font-medium no-underline transition-colors"
                        :class="
                            isActive(item.href)
                                ? 'text-(--accent)'
                                : 'text-(--ink-soft) hover:text-(--ink)'
                        "
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </Link>
                    <a
                        v-else
                        :href="item.href"
                        class="inline-flex min-h-10 items-center px-3 text-[13px] font-medium no-underline transition-colors"
                        :class="
                            isActive(item.href)
                                ? 'text-(--accent)'
                                : 'text-(--ink-soft) hover:text-(--ink)'
                        "
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </a>
                </li>
            </ul>

            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    class="d-press inline-flex size-9 items-center justify-center border border-(--rule) bg-(--surface) transition-colors hover:bg-(--accent-soft)"
                    :aria-label="themeLabel"
                    @click="toggleTheme"
                >
                    <Sun v-if="isDark" class="size-4" aria-hidden="true" />
                    <Moon v-else class="size-4" aria-hidden="true" />
                </button>

                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="d-press hidden min-h-9 items-center gap-1.5 bg-(--accent) px-3.5 py-1.5 text-xs font-semibold text-(--paper) no-underline transition-colors hover:bg-(--accent-hover) sm:inline-flex"
                >
                    Dashboard
                    <ArrowUpRight class="size-3.5" aria-hidden="true" />
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="d-press hidden min-h-9 items-center border border-(--rule) bg-(--surface) px-3.5 py-1.5 text-xs font-semibold no-underline transition-colors hover:bg-(--accent-soft) sm:inline-flex"
                >
                    Log in
                </Link>

                <!-- Mobile menu toggle -->
                <button
                    type="button"
                    class="d-press inline-flex size-9 items-center justify-center border border-(--rule) bg-(--surface) md:hidden"
                    :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
                    :aria-expanded="mobileOpen"
                    aria-controls="mobile-nav"
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" class="size-4" aria-hidden="true" />
                    <Menu v-else class="size-4" aria-hidden="true" />
                </button>
            </div>
        </nav>

        <!-- Mobile nav -->
        <Transition name="menu">
            <div
                v-if="mobileOpen"
                id="mobile-nav"
                class="border-t border-(--rule) bg-(--paper) md:hidden"
            >
                <ul class="flex flex-col px-4 py-3">
                    <li v-for="item in links" :key="item.href">
                        <Link
                            v-if="item.external"
                            :href="item.href"
                            class="block min-h-11 py-2 text-sm font-medium no-underline"
                            :class="
                                isActive(item.href)
                                    ? 'text-(--accent)'
                                    : 'text-(--ink-soft)'
                            "
                            @click="mobileOpen = false"
                        >
                            {{ item.label }}
                        </Link>
                        <Link
                            v-else-if="item.inertia"
                            :href="item.href"
                            prefetch
                            cache-for="10s"
                            class="block min-h-11 py-2 text-sm font-medium no-underline"
                            :class="
                                isActive(item.href)
                                    ? 'text-(--accent)'
                                    : 'text-(--ink-soft)'
                            "
                            @click="mobileOpen = false"
                        >
                            {{ item.label }}
                        </Link>
                        <a
                            v-else
                            :href="item.href"
                            class="block min-h-11 py-2 text-sm font-medium no-underline"
                            :class="
                                isActive(item.href)
                                    ? 'text-(--accent)'
                                    : 'text-(--ink-soft)'
                            "
                            @click="mobileOpen = false"
                        >
                            {{ item.label }}
                        </a>
                    </li>
                    <li
                        v-if="!$page.props.auth.user"
                        class="mt-2 border-t border-(--rule) pt-2"
                    >
                        <Link
                            :href="login()"
                            class="block min-h-11 py-2 text-sm font-semibold no-underline"
                            @click="mobileOpen = false"
                        >
                            Log in
                        </Link>
                    </li>
                    <li v-else class="mt-2 border-t border-(--rule) pt-2">
                        <Link
                            :href="dashboard()"
                            class="block min-h-11 py-2 text-sm font-semibold no-underline"
                            @click="mobileOpen = false"
                        >
                            Dashboard
                        </Link>
                    </li>
                </ul>
            </div>
        </Transition>
    </header>
</template>
