<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PostForm from '@/components/PostForm.svelte';
    import { Button } from '@/components/ui/button';
    import posts from '@/routes/admin/posts';
    import type { AdminPost } from '@/types/blog';

    let { post }: { post: AdminPost } = $props();
    let confirming = $state(false);
</script>

<AppHead title={`Edit ${post.title}`} />
<div class="mx-auto w-full max-w-4xl space-y-6 p-4">
    <div>
        <h1 class="text-2xl font-bold">Edit artikel</h1>
        <p class="text-sm text-muted-foreground">
            Perbarui isi atau status publikasi.
        </p>
    </div>
    {#key post.id}<PostForm {post} />{/key}
    <div class="border-t pt-6">
        {#if confirming}
            <div class="flex items-center gap-3">
                <p class="text-sm text-destructive">Hapus artikel ini?</p>
                <Form
                    action={posts.destroy({ post: post.id }).url}
                    method="post"
                >
                    {#snippet children({ processing })}
                        <input type="hidden" name="_method" value="delete" />
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}>Ya, hapus</Button
                        >
                    {/snippet}
                </Form>
                <Button
                    type="button"
                    variant="outline"
                    onclick={() => (confirming = false)}>Batal</Button
                >
            </div>
        {:else}
            <Button
                type="button"
                variant="outline"
                onclick={() => (confirming = true)}>Hapus artikel</Button
            >
        {/if}
    </div>
</div>
