<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
</script>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Hapus akun"
        description="Hapus akun dan seluruh data terkait."
    />
    <div
        class="space-y-4 rounded-lg border border-destructive/20 bg-error-container p-4"
    >
        <div class="relative space-y-0.5 text-on-error-container">
            <p class="font-medium">Perhatian</p>
            <p class="text-sm">Penghapusan akun tidak dapat dibatalkan.</p>
        </div>
        <Dialog>
            <DialogTrigger asChild
                >{#snippet children(props)}
                    <Button
                        {...props}
                        variant="destructive"
                        data-test="delete-user-button">Hapus akun</Button
                    >
                {/snippet}</DialogTrigger
            >
            <DialogContent>
                <Form
                    {...ProfileController.destroy.form()}
                    class="space-y-6"
                    options={{ preserveScroll: true }}
                >
                    {#snippet children({ errors, processing })}
                        <div class="space-y-3">
                            <DialogTitle>Hapus akun Anda?</DialogTitle>
                            <DialogDescription>
                                Akun beserta data terkait akan dihapus secara
                                permanen. Masukkan kata sandi Anda untuk
                                mengonfirmasi penghapusan.
                            </DialogDescription>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only"
                                >Kata sandi</Label
                            >
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder="Kata sandi"
                                aria-invalid={Boolean(errors.password)}
                                aria-describedby="delete-password-error"
                            />
                            <InputError
                                id="delete-password-error"
                                message={errors.password}
                            />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose asChild
                                >{#snippet children(props)}
                                    <Button {...props} variant="secondary"
                                        >Batal</Button
                                    >
                                {/snippet}</DialogClose
                            >

                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                data-test="confirm-delete-user-button"
                            >
                                Hapus akun
                            </Button>
                        </DialogFooter>
                    {/snippet}
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</div>
