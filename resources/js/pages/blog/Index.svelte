<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import ArrowRight from 'lucide-svelte/icons/arrow-right';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import AppHead from '@/components/AppHead.svelte';
    import { show } from '@/routes/blog';
    import type { PostPage, PostSummary } from '@/types/blog';
    let { posts }: { posts: PostPage<PostSummary> } = $props();
    const date = (value: string) =>
        new Intl.DateTimeFormat('en', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(new Date(value));
</script>

<AppHead
    title="Writing"
    description="Notes on building interfaces, systems, and the things I learn along the way."
/>
<div class="studio-shell py-16 sm:py-24">
    <p class="section-kicker">Notes from the work</p>
    <h1 class="section-title mt-5">Writing & experiments.</h1>
    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted-foreground">
        Thoughts on projects, code, and the things I learn along the way.
    </p>
    {#if posts.data.length === 0}<div class="studio-empty mt-12">
            <h2 class="text-xl font-semibold">Notes are on the way.</h2>
            <p class="text-muted-foreground">
                New articles will appear here when they are ready.
            </p>
        </div>
    {:else}<div class="mt-12 grid gap-6 md:grid-cols-2">
            {#each posts.data as post (post.id)}<article
                    class="cms-panel flex flex-col"
                >
                    <div
                        class="flex flex-wrap gap-3 text-xs text-muted-foreground"
                    >
                        <time datetime={post.published_at}
                            >{date(post.published_at)}</time
                        ><span>{post.reading_time} min read</span>
                    </div>
                    <h2 class="mt-5 wrap-break-word text-2xl font-semibold">
                        <Link
                            href={show({ slug: post.slug })}
                            class="hover:text-primary">{post.title}</Link
                        >
                    </h2>
                    <p class="mt-4 line-clamp-3 text-muted-foreground">
                        {post.excerpt}
                    </p>
                    <Link
                        href={show({ slug: post.slug })}
                        class="studio-link mt-auto pt-6"
                        >Read article<ArrowUpRight
                            class="size-4"
                            aria-hidden="true"
                        /></Link
                    >
                </article>{/each}
        </div>
        <nav
            class="mt-10 flex justify-between gap-4"
            aria-label="Writing pagination"
        >
            {#if posts.prev_page_url}<Link
                    class="studio-button quiet"
                    href={posts.prev_page_url}
                    ><ArrowLeft
                        class="size-4"
                        aria-hidden="true"
                    />Previous</Link
                >{:else}<span></span>{/if}{#if posts.next_page_url}<Link
                    class="studio-button quiet"
                    href={posts.next_page_url}
                    >Next<ArrowRight class="size-4" aria-hidden="true" /></Link
                >{/if}
        </nav>{/if}
</div>
