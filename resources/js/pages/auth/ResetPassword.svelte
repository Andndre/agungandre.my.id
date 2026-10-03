<script module lang="ts">
    export const layout = {
        title: 'Reset kata sandi',
        description: 'Masukkan kata sandi baru Anda.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { update } from '@/routes/password';

    let {
        token,
        email,
        passwordRules,
    }: {
        token: string;
        email: string;
        passwordRules: string;
    } = $props();
</script>

<AppHead title="Reset kata sandi" />

<Form
    {...update.form()}
    transform={(data) => ({ ...data, token, email })}
    resetOnSuccess={['password', 'password_confirmation']}
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    aria-invalid={Boolean(errors.email)}
                    aria-describedby="email-error"
                    autocomplete="email"
                    value={email}
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError
                    id="email-error"
                    message={errors.email}
                    class="mt-2"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    aria-invalid={Boolean(errors.password)}
                    aria-describedby="password-error"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="Kata sandi"
                    passwordrules={passwordRules}
                />
                <InputError id="password-error" message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    aria-invalid={Boolean(errors.password_confirmation)}
                    aria-describedby="confirmation-error"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="Konfirmasi kata sandi"
                    passwordrules={passwordRules}
                />
                <InputError
                    id="confirmation-error"
                    message={errors.password_confirmation}
                />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                disabled={processing}
                data-test="reset-password-button"
            >
                {#if processing}<Spinner />{/if}
                Reset kata sandi
            </Button>
        </div>
    {/snippet}
</Form>
