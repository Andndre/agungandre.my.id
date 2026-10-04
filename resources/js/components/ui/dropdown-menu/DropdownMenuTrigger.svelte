<script lang="ts">
import { DropdownMenu } from 'bits-ui';
import type { Snippet } from 'svelte';
let { asChild = false, children }: { asChild?: boolean; children?: Snippet<[Record<string, any>]> } = $props();
</script>
<DropdownMenu.Trigger>
{#snippet child({ props })}
{#if asChild}{@render children?.(props)}
{:else}<button {...props} type="button">{@render children?.({})}</button>{/if}
{/snippet}
</DropdownMenu.Trigger>