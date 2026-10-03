<script lang="ts">
    import { setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectForm from '@/components/ProjectForm.svelte';
    import { dashboard } from '@/routes/admin';
    import projects from '@/routes/admin/projects';
    import type { AdminProject } from '@/types/project';
    let { project }: { project: AdminProject } = $props();
    $effect(() =>
        setLayoutProps({
            breadcrumbs: [
                { title: 'Ringkasan', href: dashboard() },
                { title: 'Proyek', href: projects.index() },
                {
                    title: 'Edit proyek',
                    href: projects.edit({ project: project.id }),
                },
            ],
        }),
    );
</script>

<AppHead title={'Edit ' + project.title} />
<div class="cms-page">
    <header class="cms-header">
        <div>
            <p class="section-kicker">Portfolio / Proyek</p>
            <h1 class="cms-title">Edit proyek</h1>
            <p class="mt-2 break-words text-muted-foreground">
                {project.title}
            </p>
        </div>
    </header>
    {#key project.id}<ProjectForm {project} />{/key}
</div>
