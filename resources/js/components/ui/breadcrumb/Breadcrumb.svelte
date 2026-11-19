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

<nav aria-label="breadcrumb" class={cn('text-sm', className)} {...rest}>
    {@render children?.()}
</nav>
