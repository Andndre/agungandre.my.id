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
<div class="cms-page">
    <div class="cms-header">
        <div>
            <p class="cms-eyebrow">Tulisan</p>
            <h1 class="cms-title">Artikel baru</h1>
            <p class="cms-description">
                Tulis, lihat preview, lalu pilih waktu publikasi.
            </p>
        </div>
    </div>
    <PostForm />
</div>
