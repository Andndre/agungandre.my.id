<script lang="ts">
import { Dialog } from 'bits-ui';
import type { Snippet } from 'svelte';
let { children }: { children?: Snippet } = $props();
</script>
<Dialog.Title class="pr-10 text-lg font-semibold">{@render children?.()}</Dialog.Title>