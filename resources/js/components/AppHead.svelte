<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title = '',
        description = '',
        children,
    }: {
        title?: string;
        description?: string;
        children?: Snippet;
    } = $props();

    const appName = 'Agung Andre';
    const fullTitle = $derived(title ? `${title} - ${appName}` : appName);
</script>

<svelte:head>
    <title>{fullTitle}</title>
    {#if description}<meta name="description" content={description} />{/if}
    {@render children?.()}
</svelte:head>
