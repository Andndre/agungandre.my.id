<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        InputOTP,
        InputOTPGroup,
        InputOTPSlot,
    } from '@/components/ui/input-otp';
    import { store } from '@/routes/two-factor/login';
    import type { TwoFactorConfigContent } from '@/types';

    let showRecoveryInput = $state(false);
    let code = $state('');

    const authConfigContent: TwoFactorConfigContent = $derived.by(() => {
        if (showRecoveryInput) {
            return {
                title: 'Kode pemulihan',
                description:
                    'Masukkan salah satu kode pemulihan untuk mengakses akun Anda.',
                buttonText: 'gunakan kode autentikasi',
            };
        }

        return {
            title: 'Kode autentikasi',
            description: 'Masukkan kode dari aplikasi autentikator Anda.',
            buttonText: 'gunakan kode pemulihan',
        };
    });

    $effect(() => {
        setLayoutProps({
            title: authConfigContent.title,
            description: authConfigContent.description,
        });
    });

    function toggleRecoveryMode(clearErrors: () => void) {
        showRecoveryInput = !showRecoveryInput;
        clearErrors();
        code = '';
    }
</script>

<AppHead title="Autentikasi dua faktor" />

<div class="space-y-6">
    {#if !showRecoveryInput}
        <Form
            {...store.form()}
            class="space-y-4"
            resetOnError
            onError={() => (code = '')}
        >
            {#snippet children({ errors, processing, clearErrors })}
                <input type="hidden" name="code" value={code} />
                <div
                    class="flex flex-col items-center justify-center space-y-3 text-center"
                >
                    <div class="flex w-full items-center justify-center">
                        <InputOTP
                            id="otp"
                            aria-label="Kode autentikasi enam digit"
                            aria-invalid={Boolean(errors.code)}
                            aria-describedby="otp-error"
                            bind:value={code}
                            maxlength={6}
                            disabled={processing}
                            autofocus
                        >
                            <InputOTPGroup>
                                {#each { length: 6 } as _, i (i)}
                                    <InputOTPSlot index={i} />
                                {/each}
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError id="otp-error" message={errors.code} />
                </div>
                <Button type="submit" class="w-full" disabled={processing}
                    >Lanjutkan</Button
                >
                <div class="text-center text-sm text-muted-foreground">
                    <span>atau </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        onclick={() => toggleRecoveryMode(clearErrors)}
                    >
                        {authConfigContent.buttonText}
                    </button>
                </div>
            {/snippet}
        </Form>
    {:else}
        <Form {...store.form()} class="space-y-4" resetOnError>
            {#snippet children({ errors, processing, clearErrors })}
                <Input
                    name="recovery_code"
                    aria-label="Kode pemulihan"
                    aria-invalid={Boolean(errors.recovery_code)}
                    aria-describedby="recovery-error"
                    type="text"
                    placeholder="Masukkan kode pemulihan"
                    required
                />
                <InputError
                    id="recovery-error"
                    message={errors.recovery_code}
                />
                <Button type="submit" class="w-full" disabled={processing}
                    >Lanjutkan</Button
                >

                <div class="text-center text-sm text-muted-foreground">
                    <span>atau </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        onclick={() => toggleRecoveryMode(clearErrors)}
                    >
                        {authConfigContent.buttonText}
                    </button>
                </div>
            {/snippet}
        </Form>
    {/if}
</div>
