<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import {
        Sheet,
        SheetContent,
        SheetTitle,
        SheetDescription,
    } from '@/components/ui/sheet';
    let {
        open = $bindable(false),
        links,
        onClosed,
    }: {
        open?: boolean;
        links: { label: string; href: string }[];
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
            {#each links as link (link.label)}<Link
                    href={link.href}
                    class="studio-link"
                    onclick={() => (open = false)}
                    >{link.label}<ArrowUpRight class="size-4" /></Link
                >{/each}
        </nav>
    </SheetContent></Sheet
>
