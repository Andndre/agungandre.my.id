<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import AppHead from '@/components/AppHead.svelte';
    import { index } from '@/routes/blog';
    import type { PostSummary } from '@/types/blog';
    let {
        post,
        contentHtml,
    }: { post: Omit<PostSummary, 'id'>; contentHtml: string } = $props();
    const date = (value: string) =>
        new Intl.DateTimeFormat('en', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(value));
</script>

<AppHead title={post.title} description={post.excerpt} />
<div class="studio-shell py-12 sm:py-20">
    <div class="mx-auto max-w-3xl">
        <Link href={index()} class="studio-link"
            ><ArrowLeft class="size-4" aria-hidden="true" />All writing</Link
        >
        <article class="mt-10">
            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                <time datetime={post.published_at}
                    >{date(post.published_at)}</time
                ><span>{post.reading_time} min read</span>
            </div>
            <h1
                class="mt-5 wrap-break-word text-4xl leading-tight font-semibold tracking-tight sm:text-5xl"
            >
                {post.title}
            </h1>
            <p class="mt-6 text-lg leading-relaxed text-muted-foreground">
                {post.excerpt}
            </p>
            <div
                class="studio-prose prose mt-12 max-w-none prose-img:rounded-xl prose-pre:overflow-x-auto"
            >
                <!-- eslint-disable-next-line svelte/no-at-html-tags -- sanitized by the existing PostMarkdown server renderer -->
                {@html contentHtml}
            </div>
        </article>
    </div>
</div>
