<script lang="ts">
import { Dialog } from 'bits-ui';
import type { Snippet } from 'svelte';
let { open = $bindable(false), onOpenChange, children }: { open?: boolean; onOpenChange?: (value: boolean) => void; children?: Snippet } = $props();
</script>
<Dialog.Root bind:open {onOpenChange}>{@render children?.()}</Dialog.Root>