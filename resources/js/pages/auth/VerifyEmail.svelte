<script module lang="ts">
    export const layout = {
        title: 'Verifikasi email',
        description:
            'Verifikasi alamat email melalui tautan yang kami kirimkan.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title="Verifikasi email" />

{#if status === 'verification-link-sent'}
    <div
        role="status"
        class="mb-4 text-center text-sm font-medium text-success"
    >
        Tautan verifikasi baru telah dikirim ke alamat email Anda.
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button type="submit" disabled={processing} variant="secondary">
            {#if processing}<Spinner />{/if}
            Kirim ulang email verifikasi
        </Button>

        <TextLink href={logout()} as="button" class="mx-auto block text-sm">
            Keluar
        </TextLink>
    {/snippet}
</Form>
