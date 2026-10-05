<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import {
        Sheet,
        SheetContent,
        SheetTitle,
        SheetDescription,
    } from '@/components/ui/sheet';
    import type { PublicNavigationLink } from '@/types/public-navigation';
    let {
        open = $bindable(false),
        links,
        onClosed,
    }: {
        open?: boolean;
        links: PublicNavigationLink[];
        onClosed: () => void;
    } = $props();
</script>

<Sheet bind:open
    ><SheetContent
        closeLabel="Close navigation"
        onCloseAutoFocus={(event) => {
            event.preventDefault();
            onClosed();
        }}
    >
        <SheetTitle>Explore</SheetTitle><SheetDescription
            >Work, ideas, and ways to connect.</SheetDescription
        >
        <nav class="mt-6 flex flex-col gap-2" aria-label="Mobile navigation">
            {#each links as link (link.label)}
                {#if link.documentNavigation}
                    <a
                        href={link.href}
                        class="studio-link"
                        onclick={() => (open = false)}
                        >{link.label}<ArrowUpRight
                            class="size-4"
                            aria-hidden="true"
                        /></a
                    >
                {:else}<Link
                        href={link.href}
                        class="studio-link"
                        onclick={() => (open = false)}
                        >{link.label}<ArrowUpRight
                            class="size-4"
                            aria-hidden="true"
                        /></Link
                    >{/if}
            {/each}
        </nav>
    </SheetContent></Sheet
>
