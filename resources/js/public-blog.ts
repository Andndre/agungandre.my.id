import { mount, tick } from 'svelte';
import BlogControls from '@/components/BlogControls.svelte';
import { renderBlogMath } from '@/lib/blog-math';
import { initializeTheme } from '@/lib/theme.svelte';
import type { PublicNavigationLink } from '@/types/public-navigation';

initializeTheme();
void renderBlogMath();

const controls = document.getElementById('blog-controls');
const fallback = document.getElementById('blog-navigation-fallback');

if (controls && fallback) {
    try {
        const links = JSON.parse(
            controls.dataset.links ?? '[]',
        ) as PublicNavigationLink[];
        mount(BlogControls, { target: controls, props: { links } });
        await tick();
        controls.hidden = false;
        fallback.hidden = true;
    } catch (error) {
        console.error('Blog controls could not load.', error);
    }
}
