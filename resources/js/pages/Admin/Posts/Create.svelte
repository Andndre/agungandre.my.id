<script lang="ts">
    import { setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PostForm from '@/components/PostForm.svelte';
    import posts from '@/routes/admin/posts';
    setLayoutProps({
        breadcrumbs: [
            { title: 'Tulisan', href: posts.index().url },
            { title: 'Artikel baru', href: posts.create().url },
        ],
    });
</script>

<AppHead title="Artikel Baru" />
<div class="article-page">
    <PostForm />
</div>
