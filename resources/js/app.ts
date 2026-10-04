import { createInertiaApp } from '@inertiajs/svelte';
import type { Component } from 'svelte';
import '@/lib/public-motion.svelte';
import { initializeFlashToast } from '@/lib/flash-toast';
import { initializeTheme } from '@/lib/theme.svelte';

const pages = import.meta.glob<{ default: Component; layout?: any }>(
    './pages/**/*.svelte',
);
let publicLayout: Component;
let authLayout: Component;
let appLayout: Component;
let settingsLayout: Component;

async function loadLayouts(name: string): Promise<void> {
    if (typeof document !== 'undefined') {
        document.documentElement.lang =
            name === 'Welcome' ||
            name.startsWith('blog/') ||
            name.startsWith('projects/')
                ? 'en'
                : 'id';
    }

    if (
        name === 'Welcome' ||
        name.startsWith('blog/') ||
        name.startsWith('projects/')
    ) {
        publicLayout ??= (await import('@/layouts/PublicLayout.svelte'))
            .default;
    } else if (name.startsWith('auth/')) {
        authLayout ??= (await import('@/layouts/AuthLayout.svelte')).default;
    } else {
        appLayout ??= (await import('@/layouts/AppLayout.svelte')).default;

        if (name.startsWith('settings/')) {
            settingsLayout ??= (
                await import('@/layouts/settings/Layout.svelte')
            ).default;
        }
    }
}
createInertiaApp({
    title: (title) => (title ? title + ' - Agung Andre' : 'Agung Andre'),
    resolve: async (name) => {
        const loader = pages['./pages/' + name + '.svelte'];

        if (!loader) {
            throw new Error('Unknown page: ' + name);
        }

        const [component] = await Promise.all([loader(), loadLayouts(name)]);

        return component;
    },
    layout: (name) => {
        if (
            name === 'Welcome' ||
            name.startsWith('blog/') ||
            name.startsWith('projects/')
        ) {
            return publicLayout;
        }

        if (name.startsWith('auth/')) {
            return authLayout;
        }

        return name.startsWith('settings/')
            ? [appLayout, settingsLayout]
            : appLayout;
    },
    progress: { color: '#6250d9' },
});
initializeTheme();
initializeFlashToast();
