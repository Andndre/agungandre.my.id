import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                local('Instrument Sans', {
                    variants: [400, 500, 600, 700].map((weight) => ({
                        src:
                            'resources/fonts/instrument-sans-' +
                            weight +
                            '-latin.woff2',
                        weight,
                    })),
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        svelte(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
