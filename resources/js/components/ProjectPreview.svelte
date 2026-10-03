<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArchitecturePreview from '@/components/ArchitecturePreview.svelte';
    import { projectMotion, selectProject } from '@/lib/public-motion.svelte';
    import { show } from '@/routes/projects';
    import type { PublicProjectSummary } from '@/types/project';
    let {
        project = null,
        source = 'hero',
        eager = false,
    }: {
        project?: PublicProjectSummary | null;
        source?: string;
        eager?: boolean;
    } = $props();
    let failed = $state(false);
    $effect(() => {
        void project?.cover_image_url;
        failed = false;
    });
</script>

<div class="product-stage">
    <div class="stage-topbar">
        <span class="stage-dots" aria-hidden="true"><i></i><i></i><i></i></span
        ><span
            >{project
                ? 'PROJECT / ' + String(project.id).padStart(2, '0')
                : 'INTERFACE → APPLICATION → DATA'}</span
        >
    </div>
    {#if project}
        <Link
            href={show({ slug: project.slug })}
            viewTransition={typeof window !== 'undefined' &&
                !window.matchMedia('(prefers-reduced-motion: reduce)').matches}
            onclick={(event?: MouseEvent) => selectProject(event, source)}
            onerror={() => {
                projectMotion.source = '';
            }}
            class="project-visual-link block"
            aria-label={'View ' + project.title}
            data-project-source={source}
        >
            <div
                class="stage-image"
                style:view-transition-name={projectMotion.source === source
                    ? 'project-cover'
                    : 'none'}
            >
                {#if project.cover_image_url && !failed}
                    <img
                        src={project.cover_image_url}
                        alt={project.title + ' — project screenshot'}
                        width="1200"
                        height="900"
                        loading={eager ? 'eager' : 'lazy'}
                        fetchpriority={eager ? 'high' : 'auto'}
                        onerror={() => {
                            failed = true;
                        }}
                    />
                {:else}
                    <div class="p-8 text-center">
                        <p class="section-title">{project.title}</p>
                        <p class="mt-4 text-sm text-muted-foreground">
                            Screenshot unavailable. Explore the project details.
                        </p>
                    </div>
                {/if}
            </div>
            <div class="stage-caption">
                <div>
                    <p class="text-sm font-semibold">{project.title}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Explore the work
                    </p>
                </div>
                <ArrowUpRight class="size-5 text-primary" />
            </div>
        </Link>
    {:else}
        <ArchitecturePreview />
        <div class="stage-caption">
            <span class="text-sm font-medium"
                >From the first interaction<br />to the system behind it.</span
            ><span class="font-mono text-[10px] text-muted-foreground"
                >PROCESS<br />ILLUSTRATION</span
            >
        </div>
    {/if}
    <div class="stage-accent"></div>
</div>
