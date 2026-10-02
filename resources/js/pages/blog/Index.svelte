<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { home } from '@/routes';
    import { show } from '@/routes/blog';
    import type { PostPage, PostSummary } from '@/types/blog';

    let { posts }: { posts: PostPage<PostSummary> } = $props();

    const date = (value: string) =>
        new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(value));
</script>

<AppHead title="Blog / Devlog" />

<div class="min-h-screen bg-[#0a0a0a] text-[#EDEDEC]">
    <header class="border-b border-white/10">
        <nav
            class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5"
        >
            <Link href={home()} class="font-mono text-sm text-cyan-400"
                >&lt;andre /&gt;</Link
            >
            <Link href={home()} class="text-sm text-[#A1A09A] hover:text-white"
                >Beranda</Link
            >
        </nav>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-16 sm:py-24">
        <p
            class="mb-3 font-mono text-xs tracking-[0.3em] text-cyan-400 uppercase"
        >
            Tulisan
        </p>
        <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">
            Blog / Devlog
        </h1>
        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-[#A1A09A]">
            Catatan tentang proyek, kode, dan hal yang saya pelajari.
        </p>

        {#if posts.data.length === 0}
            <div
                class="mt-14 rounded-2xl border border-dashed border-white/15 p-12 text-center text-[#A1A09A]"
            >
                Belum ada artikel yang terbit.
            </div>
        {:else}
            <div class="mt-14 grid gap-5 md:grid-cols-2">
                {#each posts.data as post (post.id)}
                    <article
                        class="flex flex-col rounded-2xl border border-white/10 bg-white/3 p-6 transition-colors hover:border-cyan-400/40 sm:p-8"
                    >
                        <div
                            class="flex flex-wrap gap-3 font-mono text-xs text-[#A1A09A]"
                        >
                            <time datetime={post.published_at}
                                >{date(post.published_at)}</time
                            >
                            <span aria-hidden="true">·</span>
                            <span>{post.reading_time} menit baca</span>
                        </div>
                        <h2 class="mt-5 text-2xl font-semibold leading-tight">
                            <Link
                                href={show({ slug: post.slug })}
                                class="hover:text-cyan-400">{post.title}</Link
                            >
                        </h2>
                        <p
                            class="mt-4 line-clamp-3 leading-relaxed text-[#A1A09A]"
                        >
                            {post.excerpt}
                        </p>
                        <Link
                            href={show({ slug: post.slug })}
                            class="mt-auto pt-7 text-sm font-medium text-cyan-400 hover:text-cyan-300"
                            >Baca artikel →</Link
                        >
                    </article>
                {/each}
            </div>

            <nav
                class="mt-12 flex justify-between gap-4"
                aria-label="Navigasi halaman blog"
            >
                {#if posts.prev_page_url}<Link
                        href={posts.prev_page_url}
                        class="rounded-lg border border-white/15 px-4 py-2 text-sm hover:border-cyan-400"
                        >← Sebelumnya</Link
                    >{:else}<span></span>{/if}
                {#if posts.next_page_url}<Link
                        href={posts.next_page_url}
                        class="rounded-lg border border-white/15 px-4 py-2 text-sm hover:border-cyan-400"
                        >Berikutnya →</Link
                    >{/if}
            </nav>
        {/if}
    </main>
</div>
