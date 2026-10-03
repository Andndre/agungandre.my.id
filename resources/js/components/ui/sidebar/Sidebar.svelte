<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { Sheet, SheetContent, SheetTitle, SheetDescription } from '@/components/ui/sheet';

    import { cn } from '@/lib/utils';
    import {
        SIDEBAR_CONTEXT,

        type SidebarContext,
    } from './context';

    let {
        side = 'left',
        variant = 'sidebar',
        collapsible = 'offcanvas',
        class: className = '',
        children,
    }: {
        side?: 'left' | 'right';
        variant?: 'sidebar' | 'floating' | 'inset';
        collapsible?: 'offcanvas' | 'icon' | 'none';
        class?: string;
        children?: Snippet;
    } = $props();

    const { isMobile, state, openMobile, setOpenMobile } = getContext<SidebarContext>(SIDEBAR_CONTEXT);
</script>

{#if collapsible === 'none'}
    <div
        data-slot="sidebar"
        class={cn('bg-sidebar text-sidebar-foreground flex h-full w-(--sidebar-width) flex-col', className)}
    >
        {@render children?.()}
    </div>
{:else if $isMobile}
<Sheet open={$openMobile} onOpenChange={setOpenMobile}><SheetContent {side} class="p-3">
<SheetTitle>Ruang kerja</SheetTitle><SheetDescription>Navigasi pengelolaan portfolio.</SheetDescription>
<div class="flex min-h-0 flex-1 flex-col" onclick={(event) => { if ((event.target as HTMLElement).closest('a')) setOpenMobile(false); }} role="presentation">{@render children?.()}</div>
</SheetContent></Sheet>
{:else}
    <div
        class="group peer text-sidebar-foreground hidden md:block"
        data-slot="sidebar"
        data-state={$state}
        data-collapsible={$state === 'collapsed' ? collapsible : ''}
        data-variant={variant}
        data-side={side}
    >
        <div
            class={cn(
                'relative w-(--sidebar-width) bg-transparent transition-[width] duration-200 ease-linear',
                'group-data-[collapsible=offcanvas]:w-0',
                'group-data-[side=right]:rotate-180',
                variant === 'floating' || variant === 'inset'
                    ? 'group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4)))]'
                    : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon)',
            )}
        ></div>
        <div
            class={cn(
                'fixed inset-y-0 z-10 hidden h-svh w-(--sidebar-width) transition-[left,right,width] duration-200 ease-linear md:flex',
                side === 'left'
                    ? 'left-0 group-data-[collapsible=offcanvas]:-left-(--sidebar-width)'
                    : 'right-0 group-data-[collapsible=offcanvas]:-right-(--sidebar-width)',
                variant === 'floating' || variant === 'inset'
                    ? 'p-2 group-data-[collapsible=icon]:w-[calc(var(--sidebar-width-icon)+(--spacing(4))+2px)]'
                    : 'group-data-[collapsible=icon]:w-(--sidebar-width-icon) group-data-[side=left]:border-r group-data-[side=right]:border-l',
                className,
            )}
        >
            <div
                data-sidebar="sidebar"
                class="bg-sidebar group-data-[variant=floating]:border-sidebar-border flex h-full w-full flex-col group-data-[variant=floating]:rounded-lg group-data-[variant=floating]:border group-data-[variant=floating]:shadow-sm"
            >
                {@render children?.()}
            </div>
        </div>
    </div>
{/if}
