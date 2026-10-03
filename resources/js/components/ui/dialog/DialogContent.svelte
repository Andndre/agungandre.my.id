<script lang="ts">
import { Dialog } from 'bits-ui';
import X from 'lucide-svelte/icons/x';
import type { Snippet } from 'svelte';
import { cn } from '@/lib/utils';
let { class: className = '', children, onCloseAutoFocus, closeLabel = 'Tutup' }: { class?: string; children?: Snippet; onCloseAutoFocus?: (event: Event) => void; closeLabel?: string } = $props();
</script>
<Dialog.Portal>
<Dialog.Overlay class="fixed inset-0 z-50 bg-black/50" />
<Dialog.Content {onCloseAutoFocus} class={cn('fixed top-1/2 left-1/2 z-50 max-h-[90svh] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-[20px] border bg-popover p-6 text-popover-foreground shadow-xl focus:outline-none', className)}>
{@render children?.()}
<Dialog.Close class="absolute top-2 right-2 grid size-11 place-items-center rounded-xl hover:bg-accent" aria-label={closeLabel}><X class="size-4" /></Dialog.Close>
</Dialog.Content>
</Dialog.Portal>
