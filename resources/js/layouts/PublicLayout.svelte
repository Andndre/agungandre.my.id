<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';

    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import Menu from 'lucide-svelte/icons/menu';
    import type { Snippet } from 'svelte';
    import { onDestroy } from 'svelte';
    import type PublicNavigation from '@/components/PublicNavigation.svelte';
    import ThemePicker from '@/components/ThemePicker.svelte';
    import { home } from '@/routes';
    import { dashboard } from '@/routes/admin';
    import { index as writing } from '@/routes/blog';
    let { children }: { children?: Snippet } = $props();
    let mobileOpen = $state(false);
    let opening = $state(false);
    let navigationError = $state('');
    let Navigation = $state<typeof PublicNavigation>();
    let menuButton: HTMLButtonElement;
    let active = true;
    onDestroy(() => (active = false));
    async function openNavigation(): Promise<void> {
        opening = true;
        navigationError = '';
        const url = page.url;

        try {
            const module = await import('@/components/PublicNavigation.svelte');

            if (active && page.url === url) {
                Navigation = module.default;
                mobileOpen = true;
            }
        } catch {
            navigationError = 'Navigation could not load. Please try again.';
        } finally {
            opening = false;
        }
    }
    const links = $derived([
        { label: 'Work', href: home().url + '#work' },
        { label: 'About', href: home().url + '#about' },
        { label: 'Writing', href: writing().url, documentNavigation: true },
        { label: 'Contact', href: home().url + '#contact' },
    ]);
    $effect(() => {
        void page.url;
        mobileOpen = false;
    });
</script>

<a
    href="#main-content"
    class="fixed top-2 left-2 z-50 -translate-y-24 rounded-lg bg-primary p-3 text-primary-foreground focus:translate-y-0"
    >Skip to content</a
>

<header class="studio-header">
    <div class="studio-shell studio-nav">
        <Link href={home()} class="wordmark" aria-label="Andre — home"
            ><span class="wordmark-symbol" aria-hidden="true">a.</span
            >andre<span class="text-primary">.</span></Link
        >
        <nav
            class="hidden items-center gap-7 text-sm md:flex"
            aria-label="Main navigation"
        >
            {#each links as link (link.label)}
                {#if link.documentNavigation}
                    <a
                        href={link.href}
                        class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground"
                        >{link.label}</a
                    >
                {:else}<Link
                        href={link.href}
                        class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground"
                        >{link.label}</Link
                    >{/if}
            {/each}
        </nav>
        <div class="flex items-center gap-2">
            <ThemePicker />
            <button
                bind:this={menuButton}
                type="button"
                class="theme-option md:hidden"
                aria-label="Open navigation"
                aria-expanded={mobileOpen}
                aria-busy={opening}
                onclick={openNavigation}><Menu class="size-5" /></button
            >
        </div>
    </div>
</header>
{#if Navigation}<Navigation
        bind:open={mobileOpen}
        {links}
        onClosed={() => menuButton?.focus()}
    />{/if}
{#if navigationError}<p
        role="alert"
        class="studio-shell py-3 text-sm text-destructive"
    >
        {navigationError}
    </p>{/if}

<main id="main-content" tabindex="-1">{@render children?.()}</main>
<footer class="studio-shell studio-footer">
    <span>© {new Date().getFullYear()} Anak Agung Gede Andre Kusuma</span>
    <div class="flex flex-wrap gap-5">
        <a href="https://github.com/Andndre" class="hover:text-primary"
            >GitHub <ArrowUpRight class="size-4" aria-hidden="true" /></a
        ><a href="https://linkedin.com/in/andndre" class="hover:text-primary"
            >LinkedIn <ArrowUpRight class="size-4" aria-hidden="true" /></a
        >{#if page.props.auth.user}<Link
                href={dashboard()}
                class="hover:text-primary"
                >Manage portfolio <ArrowUpRight
                    class="size-4"
                    aria-hidden="true"
                /></Link
            >{/if}
    </div>
</footer>
