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

<ol class={cn('flex flex-wrap items-center gap-1.5 break-words text-sm text-muted-foreground sm:gap-2.5', className)} {...rest}>
    {@render children?.()}
</ol>
