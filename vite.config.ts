import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Bricolage Grotesque', {
                    weights: [400, 700],
                    subsets: ['latin', 'latin-ext'],
                }),
                bunny('JetBrains Mono', {
                    weights: [400, 600],
                    subsets: ['latin', 'latin-ext'],
                }),
            ],
        }),
        inertia({
            ssr: false,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    // Vue core + ecosystem
                    if (id.includes('node_modules/vue/') || id.includes('node_modules/@vueuse/')) {
                        return 'vue-vendor';
                    }
                    // Inertia
                    if (id.includes('node_modules/@inertiajs/')) {
                        return 'inertia';
                    }
                    // Animation library
                    if (id.includes('node_modules/gsap/')) {
                        return 'gsap';
                    }
                    // UI primitives
                    if (id.includes('node_modules/reka-ui/')) {
                        return 'reka-ui';
                    }
                    // Utilities
                    if (
                        id.includes('node_modules/clsx/') ||
                        id.includes('node_modules/tailwind-merge/') ||
                        id.includes('node_modules/class-variance-authority/')
                    ) {
                        return 'utils';
                    }
                    // Lucide icons - keep together with the factory
                    if (id.includes('node_modules/@lucide/')) {
                        return 'lucide';
                    }
                },
            },
        },
    },
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
});
