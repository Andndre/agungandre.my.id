<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowDown from 'lucide-svelte/icons/arrow-down';
    import ArrowRight from 'lucide-svelte/icons/arrow-right';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectPreview from '@/components/ProjectPreview.svelte';
    import {
        claimIntro,
        projectMotion,
        selectProject,
    } from '@/lib/public-motion.svelte';
    import { index as writing, show as article } from '@/routes/blog';
    import { show as projectDetail } from '@/routes/projects';
    import type { PostSummary } from '@/types/blog';
    import type { PublicProjectSummary } from '@/types/project';
    let {
        projects = [],
        latestPosts = [],
    }: { projects?: PublicProjectSummary[]; latestPosts?: PostSummary[] } =
        $props();
    let selectedId = $state<number | null>(null);
    const selected = $derived(
        projects.find((project) => project.id === selectedId) ??
            projects.find((project) => project.is_featured) ??
            projects[0] ??
            null,
    );
    let intro = $state(false);
    let introTimer: ReturnType<typeof setTimeout>;
    function finishIntro(): void {
        intro = false;
        clearTimeout(introTimer);
    }
    onMount(() => {
        intro = claimIntro();

        if (intro) {
            introTimer = setTimeout(finishIntro, 1200);
        }

        let frame = 0;

        if (
            projectMotion.returning ||
            (projectMotion.source && window.location.pathname === '/')
        ) {
            projectMotion.returning = false;
            frame = requestAnimationFrame(() => {
                window.scrollTo({
                    top: projectMotion.scrollY,
                    behavior: 'instant',
                });
                document
                    .querySelector<HTMLElement>(
                        '[data-project-source="' + projectMotion.source + '"]',
                    )
                    ?.focus({ preventScroll: true });
            });
        }

        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        const stop = (): void => {
            if (reduced.matches) {
                finishIntro();
            }
        };
        reduced.addEventListener('change', stop);

        return () => {
            clearTimeout(introTimer);
            cancelAnimationFrame(frame);
            reduced.removeEventListener('change', stop);
        };
    });
    const date = (value: string): string =>
        new Intl.DateTimeFormat('en', {
            month: 'short',
            year: 'numeric',
        }).format(new Date(value));
</script>

<svelte:window
    onkeydown={(event) => {
        if (event.key === 'Escape') {
            finishIntro();
        }
    }}
/>
<AppHead title="Andre — Full-stack Developer"
    ><meta
        name="description"
        content="Andre is a full-stack developer from Klungkung, Bali, building web and mobile applications with Laravel, Svelte, and Flutter. View projects, read articles, or get in touch."
    /></AppHead
>
<section
    class="studio-shell studio-hero relative"
    class:has-project={selected !== null}
    aria-labelledby="hero-title"
>
    <div class="hero-copy" class:intro-copy={intro}>
        <h1 id="hero-title" class="studio-title">
            I'm Andre.<br /><span class="text-primary"
                >Full-stack developer.</span
            >
        </h1>
        <p class="mt-6 text-lg leading-relaxed text-muted-foreground">
            I build web and mobile applications with Laravel, Svelte, and
            Flutter. Based in Klungkung, Bali.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="#work" class="studio-button"
                >View work <ArrowDown class="size-4" /></a
            ><a
                href="mailto:contact@agungandre.my.id"
                class="studio-button quiet"
                >Get in touch <ArrowUpRight class="size-4" /></a
            >
        </div>
    </div>
    {#if selected}<div class:intro-frame={intro}>
            <ProjectPreview project={selected} source="hero" eager />
            {#if projects.length > 1}<div
                    class="mt-4 flex flex-wrap gap-2"
                    role="group"
                    aria-label="Choose project preview"
                >
                    {#each projects.slice(0, 5) as project, i (project.id)}<button
                            type="button"
                            class="theme-option border border-border text-xs"
                            aria-label={'Preview ' + project.title}
                            aria-pressed={selected?.id === project.id}
                            onclick={() => {
                                finishIntro();
                                selectedId = project.id;
                            }}>{String(i + 1).padStart(2, '0')}</button
                        >{/each}
                </div>{/if}
        </div>{/if}
    {#if intro}<button
            type="button"
            class="studio-link absolute right-0 bottom-3"
            onclick={finishIntro}
            >Skip intro<ArrowRight class="size-4" aria-hidden="true" /></button
        >{/if}
</section>
<section id="work" class="studio-section" aria-labelledby="work-title">
    <div class="studio-shell">
        <h2 id="work-title" class="section-title">Selected work</h2>
        {#if projects.length === 0}<p class="mt-6 text-muted-foreground">
                No projects published yet.
            </p>
        {:else}{#each projects as project (project.id)}<article
                    class="work-row"
                >
                    <ProjectPreview {project} source={'work-' + project.id} />
                    <div>
                        <h3 class="text-3xl font-semibold tracking-tight">
                            {project.title}
                        </h3>
                        {#if project.is_featured}<p
                                class="mt-3 text-sm text-primary"
                            >
                                Featured project
                            </p>{/if}
                        <p
                            class="mt-4 line-clamp-4 leading-relaxed text-muted-foreground"
                        >
                            {project.description}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            {#each project.tech_stack as tech (tech)}<span
                                    class="tech-label">{tech}</span
                                >{/each}
                        </div>
                        <Link
                            href={projectDetail({ slug: project.slug })}
                            viewTransition={typeof window !== 'undefined' &&
                                !window.matchMedia(
                                    '(prefers-reduced-motion: reduce)',
                                ).matches}
                            onclick={(event?: MouseEvent) =>
                                selectProject(event, 'work-' + project.id)}
                            class="studio-link mt-6"
                            >View project <ArrowUpRight class="size-4" /></Link
                        >
                    </div>
                </article>{/each}{/if}
    </div>
</section>
<section id="about" class="studio-section" aria-labelledby="about-title">
    <div class="studio-shell grid gap-10 lg:grid-cols-2">
        <div>
            <h2 id="about-title" class="section-title">About me</h2>
        </div>
        <div class="space-y-5 text-base leading-relaxed text-muted-foreground">
            <p>
                I'm Anak Agung Gede Andre Kusuma, a full-stack developer from
                Klungkung, Bali. My path into programming started with Blender
                and Minecraft plugins, and grew into building web and mobile
                applications.
            </p>
            <p>
                I joined Informatics Education at Universitas Pendidikan Ganesha
                and coordinated the Networking & Programming division at HMJ TI.
                Since then, I've worked on profile, news, recruitment, and event
                systems, alongside projects for lecturers and personal clients.
            </p>
            <ul
                class="flex flex-wrap gap-x-5 gap-y-2 pt-2 text-sm"
                aria-label="Technologies"
            >
                {#each ['Laravel', 'Svelte', 'Flutter', 'Next.js', 'Tailwind CSS'] as tech (tech)}<li
                    >
                        {tech}
                    </li>{/each}
            </ul>
        </div>
    </div>
</section>
<section id="writing" class="studio-section" aria-labelledby="writing-title">
    <div class="studio-shell">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
            <h2 id="writing-title" class="section-title">Writing</h2>
            <a href={writing().url} class="studio-link"
                >All writing <ArrowUpRight class="size-4" /></a
            >
        </div>
        {#if latestPosts.length === 0}<p class="text-muted-foreground">
                No articles published yet.
            </p>{:else}{#each latestPosts as post (post.id)}<article
                    class="writing-item"
                >
                    <div class="max-w-2xl">
                        <p class="font-mono text-xs text-muted-foreground">
                            {date(post.published_at)} / {post.reading_time} min read
                        </p>
                        <h3 class="mt-3 text-xl font-semibold">
                            <a
                                href={article({ slug: post.slug }).url}
                                class="hover:text-primary">{post.title}</a
                            >
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            {post.excerpt}
                        </p>
                    </div>
                    <a
                        href={article({ slug: post.slug }).url}
                        class="studio-link"
                        >Read article <ArrowUpRight class="size-4" /></a
                    >
                </article>{/each}{/if}
    </div>
</section>
<section id="contact" class="studio-section" aria-labelledby="contact-title">
    <div class="studio-shell flex flex-wrap items-end justify-between gap-6">
        <div>
            <h2 id="contact-title" class="section-title">Contact</h2>
            <p class="mt-4 text-muted-foreground">
                For project enquiries or hiring conversations.
            </p>
        </div>
        <a href="mailto:contact@agungandre.my.id" class="studio-link"
            >contact@agungandre.my.id <ArrowUpRight class="size-4" /></a
        >
    </div>
</section>
