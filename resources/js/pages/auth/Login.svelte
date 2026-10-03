<script module lang="ts">
    export const layout = {
        title: 'Selamat datang kembali',
        description: 'Masuk untuk mengelola proyek dan tulisan Anda.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';
    let {
        status = '',
        canResetPassword,
    }: { status?: string; canResetPassword: boolean; canRegister?: boolean } =
        $props();
    let remember = $state(false);
</script>

<AppHead title="Masuk" />
{#if status}<p
        role="status"
        class="mb-6 rounded-xl bg-success-container p-4 text-sm text-on-success-container"
    >
        {status}
    </p>{/if}
<Form {...store.form()} resetOnSuccess={['password']} class="space-y-6">
    {#snippet children({ errors, processing })}
        <div class="cms-field">
            <Label for="email">Alamat email</Label><Input
                id="email"
                type="email"
                name="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
                aria-invalid={Boolean(errors.email)}
                aria-describedby={errors.email ? 'email-error' : undefined}
            /><InputError id="email-error" message={errors.email} />
        </div>
        <div class="cms-field">
            <div class="flex items-center justify-between gap-3">
                <Label for="password">Kata sandi</Label
                >{#if canResetPassword}<TextLink
                        href={request()}
                        class="inline-flex min-h-11 items-center text-sm"
                        >Lupa kata sandi?</TextLink
                    >{/if}
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Kata sandi"
                aria-invalid={Boolean(errors.password)}
                aria-describedby={errors.password
                    ? 'password-error'
                    : undefined}
            /><InputError id="password-error" message={errors.password} />
        </div>
        <Label
            for="remember"
            class="flex min-h-11 cursor-pointer items-center gap-3"
            ><Checkbox
                id="remember"
                name="remember"
                value="1"
                bind:checked={remember}
            /><span>Ingat saya</span></Label
        >
        <Button
            type="submit"
            class="w-full"
            disabled={processing}
            data-test="login-button"
            >{#if processing}<Spinner />{/if}{processing
                ? 'Memproses…'
                : 'Masuk'}</Button
        >
        <p class="sr-only" role="status">
            {processing ? 'Sedang memproses login' : ''}
        </p>
    {/snippet}
</Form>
