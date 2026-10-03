<script lang="ts">
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import ThemePicker from '@/components/ThemePicker.svelte';
    import { SidebarTrigger } from '@/components/ui/sidebar';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();
</script>

<header
    class="flex min-h-18 shrink-0 flex-wrap items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear md:px-4"
>
    <div class="flex items-center gap-2">
        <SidebarTrigger class="-ml-1" />
        {#if breadcrumbs && breadcrumbs.length > 0}
            <Breadcrumbs {breadcrumbs} />
        {/if}
    </div>
    <ThemePicker language="id" />
</header>
