<script lang="ts">
import { Dialog } from 'bits-ui';
import type { Snippet } from 'svelte';
let { children }: { children?: Snippet } = $props();
</script>
<Dialog.Description class="text-sm text-muted-foreground">{@render children?.()}</Dialog.Description>