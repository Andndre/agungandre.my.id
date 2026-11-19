<script lang="ts">
    import { cn } from '@/lib/utils';
    import LoaderCircle from 'lucide-svelte/icons/loader-circle';

    let {
        class: className = '',
        label = 'Memuat',
        ...rest
    }: {
        class?: string;
        label?: string;
        [key: string]: unknown;
    } = $props();
</script>

<LoaderCircle
    role="status"
    aria-label={label}
    class={cn('animate-spin', className)}
    {...rest}
/>
