<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import AppHead from '@/components/AppHead.svelte';
    import { projectMotion, returnToWork } from '@/lib/public-motion.svelte';
    import { home } from '@/routes';
    import type { PublicProjectDetail } from '@/types/project';
    let { project }: { project: PublicProjectDetail } = $props();
    let failed = $state(false);
    $effect(() => {
        void project.id;
        failed = false;
    });
</script>

<AppHead title={project.title}
    ><meta
        name="description"
        content={project.description.slice(0, 160)}
    /></AppHead
>
<div class="studio-shell py-10 sm:py-16">
    <Link
        href={projectMotion.returnUrl || home().url + '#work'}
        preserveScroll={Boolean(projectMotion.returnUrl)}
        viewTransition={typeof window !== 'undefined' &&
            !window.matchMedia('(prefers-reduced-motion: reduce)').matches}
        onclick={returnToWork}
        class="studio-link"><ArrowLeft class="size-4" /> Back to work</Link
    >
    <div class="mt-9 grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
        <div>
            <p class="section-kicker">
                {project.is_featured ? 'Featured project' : 'Project story'}
            </p>
            <h1 class="section-title mt-4">{project.title}</h1>
        </div>
        <div class="flex flex-wrap gap-3">
            {#if project.live_url}<a
                    href={project.live_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="studio-button"
                    >View live <ArrowUpRight class="size-4" /></a
                >{/if}{#if project.repo_url}<a
                    href={project.repo_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    class="studio-button quiet"
                    >Source code <ArrowUpRight class="size-4" /></a
                >{/if}
        </div>
    </div>
    <div class="product-stage mt-10">
        <div
            class="stage-image aspect-video!"
            style:view-transition-name="project-cover"
        >
            {#if project.cover_image_url && !failed}<img
                    src={project.cover_image_url}
                    alt={project.title + ' — project screenshot'}
                    width="1600"
                    height="900"
                    fetchpriority="high"
                    onerror={() => {
                        failed = true;
                    }}
                />{:else}<p class="p-10 text-center text-muted-foreground">
                    Screenshot unavailable. Read the project story below.
                </p>{/if}
        </div>
        <div class="stage-accent"></div>
    </div>
    <div class="mt-12 grid gap-10 lg:grid-cols-[1fr_16rem]">
        <section>
            <p class="section-kicker">The project</p>
            <p class="mt-6 max-w-[68ch] whitespace-pre-line leading-relaxed">
                {project.description}
            </p>
        </section>
        <aside>
            <h2 class="text-sm font-semibold">Built with</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                {#each project.tech_stack as tech (tech)}<span
                        class="tech-label">{tech}</span
                    >{/each}{#if project.tech_stack.length === 0}<p
                        class="text-sm text-muted-foreground"
                    >
                        Technology details are being prepared.
                    </p>{/if}
            </div>
        </aside>
    </div>
    {#if project.gallery_urls.length}<section
            class="mt-16 space-y-6"
            aria-label="Project gallery"
        >
            {#each project.gallery_urls as image, i (image)}<figure
                    class="overflow-hidden rounded-xl border border-border bg-muted"
                >
                    <img
                        src={image}
                        alt={project.title +
                            ' — additional screenshot ' +
                            (i + 1)}
                        width="1600"
                        height="1000"
                        loading="lazy"
                        class="aspect-8/5 w-full object-contain"
                    />
                </figure>{/each}
        </section>{/if}
    <div class="contact-panel mt-16">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight">
                A similar challenge?
            </h2>
            <p class="mt-3 text-sm">Let's talk about what you need to build.</p>
        </div>
        <a href="mailto:contact@agungandre.my.id" class="studio-button"
            >Get in touch <ArrowUpRight class="size-4" /></a
        >
    </div>
</div>
