<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { home } from '@/routes';
    import { index } from '@/routes/blog';
    import type { PostSummary } from '@/types/blog';

    let {
        post,
        contentHtml,
    }: { post: Omit<PostSummary, 'id'>; contentHtml: string } = $props();
    const date = (value: string) =>
        new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(value));
</script>

<AppHead title={post.title} />

<div class="min-h-screen bg-[#0a0a0a] text-[#EDEDEC]">
    <header class="border-b border-white/10">
        <nav
            class="mx-auto flex max-w-3xl items-center justify-between px-6 py-5"
        >
            <Link href={home()} class="font-mono text-sm text-cyan-400"
                >&lt;andre /&gt;</Link
            >
            <Link href={index()} class="text-sm text-[#A1A09A] hover:text-white"
                >Blog / Devlog</Link
            >
        </nav>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-14 sm:py-20">
        <Link href={index()} class="text-sm text-cyan-400 hover:text-cyan-300"
            >← Semua artikel</Link
        >
        <article class="mt-9">
            <div class="flex flex-wrap gap-3 font-mono text-xs text-[#A1A09A]">
                <time datetime={post.published_at}
                    >{date(post.published_at)}</time
                ><span aria-hidden="true">·</span><span
                    >{post.reading_time} menit baca</span
                >
            </div>
            <h1 class="mt-5 text-4xl font-bold tracking-tight sm:text-5xl">
                {post.title}
            </h1>
            <p class="mt-5 text-lg leading-relaxed text-[#A1A09A]">
                {post.excerpt}
            </p>
            <div
                class="prose prose-invert prose-cyan mt-12 max-w-none prose-img:rounded-xl prose-a:text-cyan-400 prose-pre:overflow-x-auto"
            >
                <!-- eslint-disable-next-line svelte/no-at-html-tags -- server-rendered by PostMarkdown with raw HTML and unsafe links disabled -->
                {@html contentHtml}
            </div>
        </article>
    </main>
</div>
