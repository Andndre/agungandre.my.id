<script lang="ts">
import { DropdownMenu } from 'bits-ui';
import type { Snippet } from 'svelte';
let { open = $bindable(false), class: className = '', children }: { open?: boolean; class?: string; children?: Snippet } = $props();
</script>
<div class={className}><DropdownMenu.Root bind:open>{@render children?.()}</DropdownMenu.Root></div>