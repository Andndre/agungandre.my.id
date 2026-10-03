<script lang="ts">
import { Checkbox } from 'bits-ui';
import Check from 'lucide-svelte/icons/check';
import { cn } from '@/lib/utils';
let { checked = $bindable(false), disabled = false, class: className = '', id, name, value = '1', ...rest }: { checked?: boolean; disabled?: boolean; class?: string; id?: string; name?: string; value?: string; [key: string]: unknown } = $props();
</script>
<Checkbox.Root bind:checked {disabled} {id} {name} {value} {...rest} class={cn('peer grid size-6 shrink-0 place-items-center rounded-sm border border-input data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50', className)}>
{#if checked}<Check class="size-4" />{/if}
</Checkbox.Root>