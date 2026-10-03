<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PostForm from '@/components/PostForm.svelte';
    import { Button } from '@/components/ui/button';
    import posts from '@/routes/admin/posts';
    import type { AdminPost } from '@/types/blog';

    let { post }: { post: AdminPost } = $props();
    let confirming = $state(false);
    $effect(() =>
        setLayoutProps({
            breadcrumbs: [
                { title: 'Tulisan', href: posts.index().url },
                {
                    title: 'Edit artikel',
                    href: posts.edit({ post: post.id }).url,
                },
            ],
        }),
    );
</script>

<AppHead title={`Edit ${post.title}`} />
<div class="cms-page">
    <div class="cms-header">
        <div>
            <p class="cms-eyebrow">Tulisan</p>
            <h1 class="cms-title">Edit artikel</h1>
            <p class="cms-description">Perbarui isi atau status publikasi.</p>
        </div>
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
