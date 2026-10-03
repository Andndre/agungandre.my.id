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
    const process = [
        {
            title: 'Understand the problem.',
            text: 'Start with the people using the product, the work they need to do, and the constraints around it.',
        },
        {
            title: 'Connect the whole system.',
            text: 'Build the interface and the backend together, so the experience holds up beyond the first screen.',
        },
        {
            title: 'Ship, learn, refine.',
            text: 'Test the important paths, listen to feedback, and make the next iteration more useful.',
        },
    ];
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
        content="Andre is a full-stack developer building thoughtful web and mobile products, from interface to backend. Explore the work and get in touch."
    /></AppHead
>
<section class="studio-shell studio-hero" aria-labelledby="hero-title">
    <div class="hero-copy" class:intro-copy={intro}>
        <p class="section-kicker">Independent full-stack developer</p>
        <h1 id="hero-title" class="studio-title mt-6">
            Thoughtful interfaces.<br /><span class="text-primary"
                >Solid systems.</span
            >
        </h1>
        <p class="mt-6 text-lg leading-relaxed text-muted-foreground">
            I'm Andre. I build web and mobile products, connecting what people
            see with everything that makes it work.
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
        <div
            class="mt-9 flex flex-wrap gap-x-5 gap-y-2 font-mono text-xs text-muted-foreground"
        >
            <span>Laravel / Svelte / Flutter</span><span>Bali, Indonesia</span>
        </div>
    </div>
    <div class="relative" class:intro-frame={intro}>
        <ProjectPreview project={selected} source="hero" eager />
        {#if intro}<button
                type="button"
                class="studio-link absolute right-4 -bottom-12"
                onclick={finishIntro}>Skip intro →</button
            >{/if}
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
    </div>
</section>
<section id="work" class="studio-section" aria-labelledby="work-title">
    <div class="studio-shell">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="section-kicker">01 / Selected work</p>
                <h2 id="work-title" class="section-title mt-4">
                    Built to be useful.
                </h2>
            </div>
            <p class="max-w-xs text-sm leading-relaxed text-muted-foreground">
                A closer look at the products, the problems, and the decisions
                behind them.
            </p>
        </div>
        {#if projects.length === 0}<div class="studio-empty mt-10">
                <span class="font-mono text-xs text-primary"
                    >WORK IN PROGRESS</span
                >
                <h3 class="text-xl font-semibold">
                    The next project starts with a conversation.
                </h3>
                <p
                    class="max-w-xl text-sm leading-relaxed text-muted-foreground"
                >
                    Project stories are being prepared. In the meantime, tell me
                    what you're building and where you could use a hand.
                </p>
                <a href="mailto:contact@agungandre.my.id" class="studio-link"
                    >Let's talk <ArrowUpRight class="size-4" /></a
                >
            </div>
        {:else}{#each projects as project, i (project.id)}<article
                    class="work-row"
                >
                    <ProjectPreview {project} source={'work-' + project.id} />
                    <div>
                        <p class="section-kicker">
                            {String(i + 1).padStart(2, '0')} / {project.is_featured
                                ? 'Featured project'
                                : 'Project'}
                        </p>
                        <h3 class="mt-5 text-3xl font-semibold tracking-tight">
                            {project.title}
                        </h3>
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
                            >Explore project <ArrowUpRight
                                class="size-4"
                            /></Link
                        >
                    </div>
                </article>{/each}{/if}
    </div>
</section>
<section class="studio-section">
    <div class="studio-shell">
        <p class="section-kicker">02 / How I work</p>
        <h2 class="section-title mt-4 max-w-2xl">
            The details connect.<br />So should the work.
        </h2>
        <div class="process-grid">
            {#each process as step, i (step.title)}<div class="process-item">
                    <span class="font-mono text-xs text-primary">0{i + 1}</span>
                    <h3 class="mt-4 text-xl font-semibold">{step.title}</h3>
                    <p
                        class="mt-3 text-sm leading-relaxed text-muted-foreground"
                    >
                        {step.text}
                    </p>
                </div>{/each}
        </div>
    </div>
</section>
<section id="about" class="studio-section">
    <div class="studio-shell grid gap-10 lg:grid-cols-2">
        <div>
            <p class="section-kicker">03 / A little about me</p>
            <h2 class="section-title mt-4">
                Curious by nature.<br />A builder by practice.
            </h2>
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
            <div class="flex flex-wrap gap-2 pt-2">
                {#each ['Laravel', 'Svelte', 'Flutter', 'Next.js', 'Tailwind CSS'] as tech (tech)}<span
                        class="tech-label">{tech}</span
                    >{/each}
            </div>
        </div>
    </div>
</section>
<section id="writing" class="studio-section">
    <div class="studio-shell">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="section-kicker">04 / Writing</p>
                <h2 class="section-title mt-4">Notes from the build.</h2>
            </div>
            <Link href={writing()} class="studio-link"
                >All writing <ArrowUpRight class="size-4" /></Link
            >
        </div>
        {#if latestPosts.length === 0}<p class="text-muted-foreground">
                Notes on development, decisions, and things learned along the
                way. Coming soon.
            </p>{:else}{#each latestPosts as post (post.id)}<article
                    class="writing-item"
                >
                    <div class="max-w-2xl">
                        <p class="font-mono text-xs text-muted-foreground">
                            {date(post.published_at)} / {post.reading_time} min read
                        </p>
                        <h3 class="mt-3 text-xl font-semibold">
                            <Link
                                href={article({ slug: post.slug })}
                                class="hover:text-primary">{post.title}</Link
                            >
                        </h3>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            {post.excerpt}
                        </p>
                    </div>
                    <Link
                        href={article({ slug: post.slug })}
                        class="studio-link"
                        >Read story <ArrowUpRight class="size-4" /></Link
                    >
                </article>{/each}{/if}
    </div>
</section>
<section id="contact" class="studio-shell pb-12">
    <div class="contact-panel">
        <div>
            <p class="text-xs font-semibold tracking-widest uppercase">
                Have something in mind?
            </p>
            <h2 class="section-title mt-4">Let's make it work.</h2>
            <p class="mt-4 max-w-xl text-sm leading-relaxed">
                A new product, a better workflow, or an idea that needs a first
                step. I'd like to hear about it.
            </p>
        </div>
        <a href="mailto:contact@agungandre.my.id" class="studio-button"
            >Get in touch <ArrowUpRight class="size-4" /></a
        >
    </div>
</section>
