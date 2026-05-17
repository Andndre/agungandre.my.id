<script module lang="ts">
    import projectsRoutes from '@/routes/admin/projects';

    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/' },
            { title: 'Projects', href: projectsRoutes.index() },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';

    let { projects: projectList } = $props();

    let deletingId = $state<number | null>(null);
</script>

<AppHead title="Manage Projects" />

<div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Projects</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage your portfolio projects. {projectList?.length ?? 0} total.
            </p>
        </div>
        <a
            href={projectsRoutes.create().url}
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                />
            </svg>
            Add Project
        </a>
    </div>

    {#if !projectList || projectList.length === 0}
        <div
            class="flex min-h-64 flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/20 text-center"
        >
            <div class="mb-3 text-5xl">📁</div>
            <h3 class="font-semibold">No projects yet</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Start by adding your first project to the portfolio.
            </p>
            <a
                href={projectsRoutes.create().url}
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
            >
                Add First Project
            </a>
        </div>
    {:else}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {#each projectList as project (project.id)}
                <div
                    class="group relative overflow-hidden rounded-xl border border-border bg-card transition-all hover:border-primary/30 hover:shadow-md"
                >
                    <!-- Cover image -->
                    <div class="aspect-video w-full overflow-hidden bg-muted">
                        {#if project.cover_image}
                            <img
                                src={project.cover_image}
                                alt={project.title}
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                        {:else}
                            <div
                                class="flex h-full items-center justify-center"
                            >
                                <span
                                    class="text-4xl font-bold text-muted-foreground/30"
                                >
                                    {project.title.charAt(0)}
                                </span>
                            </div>
                        {/if}
                    </div>

                    <div class="p-4">
                        <!-- Badges -->
                        <div class="mb-2 flex flex-wrap gap-1.5">
                            {#if project.is_featured}
                                <Badge
                                    variant="default"
                                    class="bg-amber-500/10 text-amber-500 hover:bg-amber-500/20"
                                >
                                    ★ Featured
                                </Badge>
                            {/if}
                            {#if project.is_published}
                                <Badge
                                    variant="outline"
                                    class="text-emerald-500 border-emerald-500/30"
                                >
                                    Published
                                </Badge>
                            {:else}
                                <Badge
                                    variant="outline"
                                    class="text-muted-foreground"
                                >
                                    Draft
                                </Badge>
                            {/if}
                        </div>

                        <!-- Title & description -->
                        <h3 class="font-semibold leading-tight">
                            {project.title}
                        </h3>
                        <p
                            class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                        >
                            {project.description}
                        </p>

                        <!-- Tech stack -->
                        {#if project.tech_stack && project.tech_stack.length > 0}
                            <div class="mt-3 flex flex-wrap gap-1">
                                {#each project.tech_stack.slice(0, 3) as tech (tech)}
                                    <span
                                        class="rounded bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground"
                                    >
                                        {tech}
                                    </span>
                                {/each}
                                {#if project.tech_stack.length > 3}
                                    <span
                                        class="rounded bg-muted px-2 py-0.5 font-mono text-xs text-muted-foreground"
                                    >
                                        +{project.tech_stack.length - 3}
                                    </span>
                                {/if}
                            </div>
                        {/if}

                        <!-- Actions -->
                        <div class="mt-4 flex items-center gap-2">
                            <a
                                href={projectsRoutes.edit({ project: project.id }).url}
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                                Edit
                            </a>

                            {#if deletingId === project.id}
                                <Form
                                    action={projectsRoutes.destroy({
                                        project: project.id,
                                    }).url}
                                    method="post"
                                    options={{ preserveScroll: true }}
                                >
                                    {#snippet children({ processing })}
                                        <input
                                            type="hidden"
                                            name="_method"
                                            value="delete"
                                        />
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Deleting...'
                                                : 'Confirm'}
                                        </Button>
                                    {/snippet}
                                </Form>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onclick={() => {
                                        deletingId = null;
                                    }}
                                >
                                    Cancel
                                </Button>
                            {:else}
                                <button
                                    onclick={() => {
                                        deletingId = project.id;
                                    }}
                                    class="flex items-center justify-center gap-1.5 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-1.5 text-sm font-medium text-destructive transition-colors hover:bg-destructive/20"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Delete
                                </button>
                            {/if}
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    {/if}
</div>
