<script lang="ts">
    let {
        message,
        id,
        class: className = '',
    }: { message?: string; id?: string; class?: string } = $props();
</script>

{#if message}<p
        {id}
        role="alert"
        class={'text-sm text-destructive ' + className}
    >
        {message}
    </p>{/if}
