<script lang="ts">
import { Dialog } from 'bits-ui';
import type { Snippet } from 'svelte';
let { asChild = false, children }: { asChild?: boolean; children?: Snippet<[Record<string, any>]> } = $props();
</script>
<Dialog.Trigger>
{#snippet child({ props })}
{#if asChild}{@render children?.({ ...props, onClick: props.onclick })}
{:else}<button {...props} type="button">{@render children?.({})}</button>{/if}
{/snippet}
</Dialog.Trigger>