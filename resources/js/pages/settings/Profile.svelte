<script module lang="ts">
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pengaturan profil',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { send } from '@/routes/verification';

    let {
        mustVerifyEmail,
        status = '',
    }: {
        mustVerifyEmail: boolean;
        status?: string;
    } = $props();

    const user = $derived(page.props.auth.user);
</script>

<AppHead title="Pengaturan profil" />

<h1 class="sr-only">Pengaturan profil</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title="Profile information"
        description="Update your name and email address"
    />

    <Form
        {...ProfileController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input
                    id="name"
                    name="name"
                    aria-invalid={Boolean(errors.name)}
                    aria-describedby="profile-name-error"
                    class="mt-1 block w-full"
                    value={user.name}
                    required
                    autocomplete="name"
                    placeholder="Nama lengkap"
                />
                <InputError
                    id="profile-name-error"
                    class="mt-2"
                    message={errors.name}
                />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    aria-invalid={Boolean(errors.email)}
                    aria-describedby="profile-email-error"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder="Alamat email"
                />
                <InputError
                    id="profile-email-error"
                    class="mt-2"
                    message={errors.email}
                />
            </div>

            {#if mustVerifyEmail && !user.email_verified_at}
                <div>
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Alamat email Anda belum diverifikasi.
                        <TextLink href={send()} as="button">
                            Kirim ulang email verifikasi.
                        </TextLink>
                    </p>

                    {#if status === 'verification-link-sent'}
                        <div
                            role="status"
                            class="mt-2 text-sm font-medium text-success"
                        >
                            Tautan verifikasi baru telah dikirim ke alamat email
                            Anda.
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    disabled={processing}
                    data-test="update-profile-button">Simpan</Button
                >
            </div>
        {/snippet}
    </Form>
</div>

<DeleteUser />
