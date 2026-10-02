<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
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
</script>

<AppHead title="Kelola Blog" />

<div class="flex h-full flex-col gap-6 rounded-xl p-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Blog / Devlog</h1>
            <p class="text-sm text-muted-foreground">
                Kelola artikel dan jadwal publikasi.
            </p>
        </div>
        <Link
            href={postsRoutes.create()}
            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >+ Artikel baru</Link
        >
    </div>

    {#if posts.data.length === 0}
        <div
            class="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
        >
            Belum ada artikel.
        </div>
    {:else}
        <div class="divide-y rounded-xl border bg-card">
            {#each posts.data as post (post.id)}
                <div
                    class="flex flex-wrap items-center justify-between gap-3 p-4"
                >
                    <div>
                        <p class="font-medium">{post.title}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            /{post.slug} · {post.reading_time} menit · {status(
                                post.published_at,
                            )}
                        </p>
                    </div>
                    <Link
                        href={postsRoutes.edit({ post: post.id })}
                        class="rounded-lg border px-3 py-1.5 text-sm hover:bg-muted"
                        >Edit</Link
                    >
                </div>
            {/each}
        </div>
        <nav class="flex justify-between text-sm">
            {#if posts.prev_page_url}<Link href={posts.prev_page_url}
                    >← Sebelumnya</Link
                >{:else}<span></span>{/if}
            {#if posts.next_page_url}<Link href={posts.next_page_url}
                    >Berikutnya →</Link
                >{/if}
        </nav>
    {/if}
</div>
