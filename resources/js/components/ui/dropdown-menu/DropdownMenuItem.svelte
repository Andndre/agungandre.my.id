<script lang="ts">
import { DropdownMenu } from 'bits-ui';
import type { Snippet } from 'svelte';
import { cn } from '@/lib/utils';
let { asChild = false, class: className = '', children }: { asChild?: boolean; class?: string; children?: Snippet<[Record<string, any>]> } = $props();
</script>
<DropdownMenu.Item class={cn('flex min-h-11 w-full cursor-pointer items-center gap-2 rounded-lg px-3 text-sm outline-none data-highlighted:bg-accent', className)}>
{#snippet child({ props })}
{#if asChild}{@render children?.({ ...props, onClick: props.onclick })}
{:else}<div {...props}>{@render children?.({})}</div>{/if}
{/snippet}
</DropdownMenu.Item>