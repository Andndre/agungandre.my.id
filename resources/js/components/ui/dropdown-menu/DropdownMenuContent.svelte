<script lang="ts">
import { DropdownMenu } from 'bits-ui';
import type { Snippet } from 'svelte';
import { cn } from '@/lib/utils';
let { align = 'start', side = 'bottom', sideOffset = 4, class: className = '', children }: { align?: 'start'|'center'|'end'; side?: 'top'|'right'|'bottom'|'left'; sideOffset?: number; class?: string; children?: Snippet } = $props();
</script>
<DropdownMenu.Portal><DropdownMenu.Content {align} {side} {sideOffset} class={cn('z-50 min-w-48 rounded-xl border bg-popover p-2 text-popover-foreground shadow-md outline-none', className)}>{@render children?.()}</DropdownMenu.Content></DropdownMenu.Portal>