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

<span
    role="link"
    aria-disabled="true"
    aria-current="page"
    class={cn('font-medium text-foreground', className)}
    {...rest}
>
    {@render children?.()}
</span>
