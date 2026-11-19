<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        children,
        ...rest
    }: {
        class?: string;
        children?: Snippet;
        [key: string]: unknown;
    } = $props();
</script>

<li
    role="presentation"
    aria-hidden="true"
    class={cn('text-muted-foreground select-none', className)}
    {...rest}
>
    {#if children}
        {@render children()}
    {:else}
        /
    {/if}
</li>
