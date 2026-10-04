<script lang="ts">
    import { Link, setLayoutProps } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import ArrowRight from 'lucide-svelte/icons/arrow-right';
    import Plus from 'lucide-svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import { dashboard } from '@/routes/admin';
    import postsRoutes from '@/routes/admin/posts';
    import type { AdminPost, PostPage } from '@/types/blog';
    let {
        posts,
    }: {
        posts: PostPage<
            Pick<
                AdminPost,
                | 'id'
                | 'title'
                | 'slug'
                | 'reading_time'
                | 'published_at'
                | 'updated_at'
            >
        >;
    } = $props();
    const status = (value: string | null) =>
        !value ? 'Draf' : new Date(value) > new Date() ? 'Terjadwal' : 'Terbit';
    setLayoutProps({
        breadcrumbs: [
            { title: 'Ringkasan', href: dashboard() },
            { title: 'Tulisan', href: postsRoutes.index() },
        ],
    });
</script>

<AppHead title="Tulisan" />
<div class="cms-page">
    <header class="cms-header">
        <div>
            <p class="section-kicker">Portfolio / Konten</p>
            <h1 class="cms-title">Tulisan</h1>
            <p class="mt-2 text-muted-foreground">
                {posts.total} artikel · Draf, publikasi, dan jadwal terbit.
            </p>
        </div>
        <Link href={postsRoutes.create()} class="studio-button"
            ><Plus class="size-4" aria-hidden="true" />Artikel baru</Link
        >
    </header>
    {#if posts.data.length === 0}<div class="studio-empty">
            <h2 class="text-xl font-semibold">Belum ada artikel</h2>
            <p class="text-muted-foreground">
                Tulis catatan tentang proyek, kode, atau hal yang Anda pelajari.
            </p>
            <Link href={postsRoutes.create()} class="studio-button mt-4"
                >Tulis artikel pertama</Link
            >
        </div>
    {:else}<div class="cms-panel space-y-0 p-0">
            {#each posts.data as post (post.id)}<article
                    class="flex flex-wrap items-center justify-between gap-4 border-b border-border p-6 last:border-b-0"
                >
                    <div class="min-w-0 flex-1">
                        <Link
                            href={postsRoutes.edit({ post: post.id })}
                            class="block wrap-break-word font-semibold hover:text-primary"
                            >{post.title}</Link
                        >
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <span
                                class="status-label"
                                class:published={status(post.published_at) ===
                                    'Terbit'}
                                class:scheduled={status(post.published_at) ===
                                    'Terjadwal'}
                                >{status(post.published_at)}</span
                            ><span class="text-xs text-muted-foreground"
                                >{post.reading_time} menit baca · Diperbarui {new Date(
                                    post.updated_at,
                                ).toLocaleDateString('id-ID')}</span
                            >{#if status(post.published_at) === 'Terjadwal' && post.published_at}<time
                                    datetime={post.published_at}
                                    class="text-xs text-muted-foreground"
                                    >{new Date(
                                        post.published_at,
                                    ).toLocaleString('id-ID')}</time
                                >{/if}
                        </div>
                    </div>
                    <Link
                        class="studio-button quiet"
                        href={postsRoutes.edit({ post: post.id })}
                        >Edit artikel</Link
                    >
                </article>{/each}
        </div>
        <nav
            class="mt-6 flex justify-between gap-3"
            aria-label="Halaman daftar artikel"
        >
            {#if posts.prev_page_url}<Link
                    class="studio-button quiet"
                    href={posts.prev_page_url}
                    ><ArrowLeft
                        class="size-4"
                        aria-hidden="true"
                    />Sebelumnya</Link
                >{:else}<span></span>{/if}{#if posts.next_page_url}<Link
                    class="studio-button quiet"
                    href={posts.next_page_url}
                    >Berikutnya<ArrowRight
                        class="size-4"
                        aria-hidden="true"
                    /></Link
                >{/if}
        </nav>{/if}
</div>
