<script lang="ts">
    import { setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PostForm from '@/components/PostForm.svelte';
    import posts from '@/routes/admin/posts';
    import type { AdminPost } from '@/types/blog';

    let { post }: { post: AdminPost } = $props();
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
<div class="article-page">
    {#key post.id}<PostForm {post} />{/key}
</div>
