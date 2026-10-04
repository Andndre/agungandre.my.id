<script module lang="ts">
    import { edit } from '@/routes/security';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pengaturan keamanan',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ShieldCheck from 'lucide-svelte/icons/shield-check';
    import { onDestroy } from 'svelte';
    import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.svelte';
    import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import { disable, enable } from '@/routes/two-factor';

    let {
        canManageTwoFactor = false,
        requiresConfirmation = false,
        twoFactorEnabled = false,
        passwordRules,
    }: {
        canManageTwoFactor?: boolean;
        requiresConfirmation?: boolean;
        twoFactorEnabled?: boolean;
        passwordRules: string;
    } = $props();

    const twoFactorAuth = twoFactorAuthState();
    let showSetupModal = $state(false);

    onDestroy(() => twoFactorAuth.clearTwoFactorAuthData());
</script>

<AppHead title="Pengaturan keamanan" />

<h1 class="sr-only">Pengaturan keamanan</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Ubah kata sandi"
        description="Gunakan kata sandi panjang dan unik untuk menjaga keamanan akun."
    />

    <Form
        {...SecurityController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
        resetOnSuccess
        resetOnError={['password', 'password_confirmation', 'current_password']}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="current_password">Kata sandi saat ini</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    aria-invalid={Boolean(errors.current_password)}
                    aria-describedby="current-password-error"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Kata sandi saat ini"
                />
                <InputError
                    id="current-password-error"
                    message={errors.current_password}
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi baru</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    aria-invalid={Boolean(errors.password)}
                    aria-describedby="new-password-error"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Kata sandi baru"
                    passwordrules={passwordRules}
                />
                <InputError id="new-password-error" message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    aria-invalid={Boolean(errors.password_confirmation)}
                    aria-describedby="password-confirmation-error"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Konfirmasi kata sandi"
                    passwordrules={passwordRules}
                />
                <InputError
                    id="password-confirmation-error"
                    message={errors.password_confirmation}
                />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-password-button"
                >
                    Simpan kata sandi
                </Button>
            </div>
        {/snippet}
    </Form>
</div>

{#if canManageTwoFactor}
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Autentikasi dua faktor"
            description="Kelola autentikasi dua faktor Anda."
        />

        {#if !twoFactorEnabled}
            <div class="flex flex-col items-start justify-start space-y-4">
                <p class="text-muted-foreground text-sm">
                    Setelah 2FA aktif, masukkan kode dari aplikasi autentikator
                    di ponsel Anda saat login.
                </p>

                <div>
                    {#if twoFactorAuth.hasSetupData()}
                        <Button onclick={() => (showSetupModal = true)}>
                            <ShieldCheck class="size-4" />Lanjutkan pengaturan
                        </Button>
                    {:else}
                        <Form
                            {...enable.form()}
                            onSuccess={() => (showSetupModal = true)}
                        >
                            {#snippet children({ processing })}
                                <Button type="submit" disabled={processing}>
                                    Aktifkan 2FA
                                </Button>
                            {/snippet}
                        </Form>
                    {/if}
                </div>
            </div>
        {:else}
            <div class="flex flex-col items-start justify-start space-y-4">
                <p class="text-muted-foreground text-sm">
                    Akun dilindungi oleh kode dari aplikasi autentikator di
                    ponsel Anda saat login.
                </p>

                <div class="relative inline">
                    <Form {...disable.form()}>
                        {#snippet children({ processing })}
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                Nonaktifkan 2FA
                            </Button>
                        {/snippet}
                    </Form>
                </div>

                <TwoFactorRecoveryCodes />
            </div>
        {/if}

        <TwoFactorSetupModal
            bind:isOpen={showSetupModal}
            {requiresConfirmation}
            {twoFactorEnabled}
        />
    </div>
{/if}
