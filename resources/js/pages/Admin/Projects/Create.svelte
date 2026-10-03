<script lang="ts">
    import { setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ProjectForm from '@/components/ProjectForm.svelte';
    import { dashboard } from '@/routes/admin';
    import projects from '@/routes/admin/projects';
    setLayoutProps({
        breadcrumbs: [
            { title: 'Ringkasan', href: dashboard() },
            { title: 'Proyek', href: projects.index() },
            { title: 'Proyek baru', href: projects.create() },
        ],
    });
</script>

<AppHead title="Proyek baru" />
<div class="cms-page">
    <header class="cms-header">
        <div>
            <p class="section-kicker">Portfolio / Proyek</p>
            <h1 class="cms-title">Proyek baru</h1>
            <p class="mt-2 text-muted-foreground">
                Mulai dari cerita karya Anda. Simpan sebagai draf sampai siap
                diterbitkan.
            </p>
        </div>
    </header>
    <ProjectForm />
</div>
