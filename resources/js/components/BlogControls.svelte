<script lang="ts">
    import Menu from 'lucide-svelte/icons/menu';
    import PublicNavigation from '@/components/PublicNavigation.svelte';
    import ThemePicker from '@/components/ThemePicker.svelte';
    import type { PublicNavigationLink } from '@/types/public-navigation';

    let { links }: { links: PublicNavigationLink[] } = $props();
    let mobileOpen = $state(false);
    let menuButton: HTMLButtonElement;
</script>

<div class="flex items-center gap-2">
    <ThemePicker />
    <button
        bind:this={menuButton}
        type="button"
        class="theme-option md:hidden"
        aria-label="Open navigation"
        aria-haspopup="dialog"
        aria-expanded={mobileOpen}
        onclick={() => (mobileOpen = true)}
    >
        <Menu class="size-5" />
    </button>
</div>
<PublicNavigation
    bind:open={mobileOpen}
    {links}
    onClosed={() => menuButton?.focus()}
/>
