<script lang="ts">
import { Dialog } from 'bits-ui';
import X from 'lucide-svelte/icons/x';
import type { Snippet } from 'svelte';
import { cn } from '@/lib/utils';
let { side = 'right', class: className = '', children, onCloseAutoFocus, closeLabel = 'Tutup' }: { side?: 'right'|'left'|'top'|'bottom'; class?: string; children?: Snippet; onCloseAutoFocus?: (event: Event) => void; closeLabel?: string } = $props();
const positions = { right: 'inset-y-0 right-0 w-[min(88vw,24rem)]', left: 'inset-y-0 left-0 w-[min(88vw,24rem)]', top: 'inset-x-0 top-0', bottom: 'inset-x-0 bottom-0' };
</script>
<Dialog.Portal>
<Dialog.Overlay class="fixed inset-0 z-50 bg-black/50" />
<Dialog.Content {onCloseAutoFocus} class={cn('fixed z-50 flex flex-col gap-4 overflow-y-auto bg-popover p-6 text-popover-foreground shadow-xl focus:outline-none', positions[side], className)}>
{@render children?.()}
<Dialog.Close class="absolute top-2 right-2 grid size-11 place-items-center rounded-xl hover:bg-accent" aria-label={closeLabel}><X class="size-5" /></Dialog.Close>
</Dialog.Content>
</Dialog.Portal>
