<script module lang="ts">
    export const layout = {
        title: 'Konfirmasi kata sandi',
        description:
            'Konfirmasikan kata sandi Anda untuk melanjutkan ke area ini.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { store } from '@/routes/password/confirm';
</script>

<AppHead title="Konfirmasi kata sandi" />

<Form {...store.form()} resetOnSuccess>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    aria-invalid={Boolean(errors.password)}
                    aria-describedby="password-error"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError id="password-error" message={errors.password} />
            </div>

            <div class="flex items-center">
                <Button
                    type="submit"
                    class="w-full"
                    disabled={processing}
                    data-test="confirm-password-button"
                >
                    {#if processing}<Spinner />{/if}
                    Konfirmasi kata sandi
                </Button>
            </div>
        </div>
    {/snippet}
</Form>
