<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowDown from 'lucide-svelte/icons/arrow-down';
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
    let workSection: HTMLElement;
    let workTrack: HTMLElement;
    let workProgress = $state(0);
    let intro = $state(false);
    let introFinished = $state(false);
    let introTimer: ReturnType<typeof setTimeout>;
    function finishIntro(): void {
        if (intro) {
            introFinished = true;
        }

        intro = false;
        clearTimeout(introTimer);
    }
    onMount(() => {
        intro = claimIntro();

        if (intro) {
            introTimer = setTimeout(finishIntro, 2200);
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

        const desktopMotion = window.matchMedia(
            '(min-width: 1024px) and (prefers-reduced-motion: no-preference)',
        );
        let distance = 0;
        const updateWork = (): void => {
            if (distance > 0) {
                workTrack.scrollLeft = Math.max(
                    0,
                    Math.min(
                        distance,
                        80 - workSection.getBoundingClientRect().top,
                    ),
                );
                workProgress = workTrack.scrollLeft / distance;
            }
        };
        const measureWork = (): void => {
            workSection.classList.toggle(
                'work-single',
                desktopMotion.matches && projects.length === 1,
            );
            workSection.classList.toggle(
                'work-horizontal',
                desktopMotion.matches && projects.length > 1,
            );
            distance =
                desktopMotion.matches && projects.length > 1
                    ? Math.max(0, workTrack.scrollWidth - workTrack.clientWidth)
                    : 0;
            workSection.style.setProperty('--work-distance', `${distance}px`);
            updateWork();
        };
        const followFocus = (event: FocusEvent): void => {
            if (distance === 0 || !(event.target instanceof HTMLElement)) {
                return;
            }

            const card = event.target.closest<HTMLElement>('.work-card');

            if (!card) {
                return;
            }

            const trackRect = workTrack.getBoundingClientRect();
            const cardRect = card.getBoundingClientRect();

            if (
                cardRect.left < trackRect.left ||
                cardRect.right > trackRect.right
            ) {
                const target = Math.max(
                    0,
                    Math.min(
                        distance,
                        workTrack.scrollLeft + cardRect.left - trackRect.left,
                    ),
                );
                window.scrollTo({
                    top:
                        window.scrollY +
                        workSection.getBoundingClientRect().top -
                        80 +
                        target,
                    behavior: 'instant',
                });
            }
        };
        const resizeObserver = new ResizeObserver(measureWork);
        resizeObserver.observe(workTrack);
        desktopMotion.addEventListener('change', measureWork);
        window.addEventListener('resize', measureWork);
        window.addEventListener('scroll', updateWork, { passive: true });
        workTrack.addEventListener('focusin', followFocus);
        measureWork();

        return () => {
            clearTimeout(introTimer);
            cancelAnimationFrame(frame);
            reduced.removeEventListener('change', stop);
            resizeObserver.disconnect();
            desktopMotion.removeEventListener('change', measureWork);
            window.removeEventListener('resize', measureWork);
            window.removeEventListener('scroll', updateWork);
            workTrack.removeEventListener('focusin', followFocus);
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
{#if intro}
    <div class="landing-intro" aria-hidden="true">
        <span class="landing-intro-label">Full-stack developer</span>
        <strong class="landing-intro-name">Andre<span>.</span></strong>
        <span class="landing-intro-location">Klungkung, Bali</span>
    </div>
    <button type="button" class="landing-intro-skip" onclick={finishIntro}
        >Skip intro</button
    >
{/if}
<section class="studio-shell landing-hero" aria-labelledby="hero-title">
    <div class="landing-hero-meta">
        <span>Full-stack developer · Klungkung, Bali</span><span
            >Web &amp; mobile</span
        >
    </div>
    <div class:intro-copy={introFinished}>
        <p class="mb-4 text-lg text-muted-foreground">Hello, I'm</p>
        <h1 id="hero-title" class="landing-name">
            Andre<span class="text-primary">.</span>
        </h1>
        <div class="landing-hero-detail">
            <p>
                I build web and mobile applications with Laravel, Svelte, and
                Flutter.
            </p>
            <a href="#work" class="studio-button rounded-full"
                >Explore my work <ArrowDown class="size-4" /></a
            >
        </div>
    </div>
    <div class="landing-hero-bottom">
        <span>Thoughtful digital experiences</span><a
            href="mailto:contact@agungandre.my.id"
            class="studio-link">Get in touch <ArrowUpRight class="size-4" /></a
        >
    </div>
</section>
<section
    id="work"
    bind:this={workSection}
    class="landing-work"
    aria-labelledby="work-title"
>
    <div class="studio-shell work-layout">
        <div class="work-intro">
            <div>
                <h2 id="work-title" class="section-title">Selected work</h2>
                <p class="mt-6 text-muted-foreground">
                    A selection of web and mobile applications.
                </p>
            </div>
            {#if projects.length > 1}
                <div class="work-progress" aria-hidden="true">
                    <div class="work-progress-track">
                        <div
                            class="work-progress-fill"
                            style:width={`${Math.max(1 / projects.length, workProgress) * 100}%`}
                        ></div>
                    </div>
                    <span
                        >{String(
                            Math.min(
                                projects.length,
                                Math.floor(workProgress * projects.length) + 1,
                            ),
                        ).padStart(2, '0')} / {String(projects.length).padStart(
                            2,
                            '0',
                        )}</span
                    >
                </div>
            {/if}
        </div>
        <div
            bind:this={workTrack}
            class="work-track"
            aria-label="Selected projects"
        >
            {#if projects.length === 0}<p class="text-muted-foreground">
                    No projects published yet.
                </p>
            {:else}{#each projects as project (project.id)}<article
                        class="work-card"
                    >
                        <ProjectPreview
                            {project}
                            source={'work-' + project.id}
                        />
                        <div>
                            <h3 class="sr-only">{project.title}</h3>
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
                                >View project <ArrowUpRight
                                    class="size-4"
                                /></Link
                            >
                        </div>
                    </article>{/each}{/if}
        </div>
    </div>
</section>
<section
    id="about"
    class="studio-section bg-sidebar"
    aria-labelledby="about-title"
>
    <div class="studio-shell grid gap-10 lg:grid-cols-2">
        <div>
            <h2 id="about-title" class="section-title">About me</h2>
            <p class="mt-6 max-w-sm text-2xl text-primary">
                From curiosity to things people can use.
            </p>
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
                class="flex flex-wrap gap-2 pt-2 text-sm"
                aria-label="Technologies"
            >
                {#each ['Laravel', 'Svelte', 'Flutter', 'Next.js', 'Tailwind CSS'] as tech (tech)}<li
                        class="rounded-full border border-input px-3 py-2"
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
<section
    id="contact"
    class="studio-section bg-primary-container text-on-primary-container"
    aria-labelledby="contact-title"
>
    <div class="studio-shell flex flex-wrap items-end justify-between gap-6">
        <div>
            <h2 id="contact-title" class="section-title">Let's talk.</h2>
            <p class="mt-4">For project enquiries or hiring conversations.</p>
        </div>
        <a
            href="mailto:contact@agungandre.my.id"
            class="studio-link text-on-primary-container"
            >contact@agungandre.my.id <ArrowUpRight class="size-4" /></a
        >
    </div>
</section>
