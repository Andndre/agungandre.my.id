<script lang="ts">
    import { Link, page, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { home } from '@/routes';
    import { dashboard } from '@/routes/admin';
    import posts from '@/routes/admin/posts';
    import projects from '@/routes/admin/projects';
    let {
        stats,
        recentProjects,
    }: {
        stats: {
            total: number;
            featured: number;
            published: number;
            drafts: number;
        };
        recentProjects: {
            id: number;
            title: string;
            is_featured: boolean;
            is_published: boolean;
            created_at: string;
        }[];
    } = $props();
    setLayoutProps({
        breadcrumbs: [{ title: 'Ringkasan', href: dashboard() }],
    });
</script>

<AppHead title="Ringkasan" />
<div class="cms-page">
    <header class="cms-header">
        <div>
            <p class="section-kicker">Ruang kerja / Portfolio</p>
            <h1 class="cms-title">Selamat datang kembali.</h1>
            <p class="mt-2 text-muted-foreground">
                Lanjutkan karya Anda, satu langkah pada satu waktu.
            </p>
        </div>
        <Link href={projects.create()} class="studio-button">+ Proyek baru</Link
        >
    </header>
    <div
        class="mb-8 flex flex-wrap gap-x-8 gap-y-3 border-y border-border py-5 text-sm"
    >
        <p><strong class="mr-2 text-foreground">{stats.total}</strong>proyek</p>
        <p>
            <strong class="mr-2 text-foreground">{stats.published}</strong
            >terbit
        </p>
        <p><strong class="mr-2 text-foreground">{stats.drafts}</strong>draf</p>
        <p>
            <strong class="mr-2 text-foreground">{stats.featured}</strong
            >unggulan
        </p>
    </div>
    <section class="cms-panel">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Lanjutkan proyek</h2>
                <p class="cms-help mt-1">
                    Proyek terbaru yang dapat Anda buka dan edit.
                </p>
            </div>
            <Link href={projects.index()} class="studio-button quiet"
                >Semua proyek →</Link
            >
        </div>
        {#if recentProjects.length}<div class="divide-y divide-border">
                {#each recentProjects as project (project.id)}<Link
                        href={projects.edit({ project: project.id })}
                        class="flex min-h-20 flex-wrap items-center justify-between gap-3 rounded-xl py-4 hover:bg-muted"
                        ><div class="min-w-0">
                            <h3 class="break-words font-medium">
                                {project.title}
                            </h3>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Dibuat {new Date(
                                    project.created_at,
                                ).toLocaleDateString('id-ID')}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="status-label"
                                class:published={project.is_published}
                                >{project.is_published
                                    ? 'Terbit'
                                    : 'Draf'}</span
                            >{#if project.is_featured}<span
                                    class="status-label featured">Unggulan</span
                                >{/if}<span aria-hidden="true" class="px-3"
                                >→</span
                            >
                        </div></Link
                    >{/each}
            </div>
        {:else}<div class="studio-empty">
                <h3 class="text-lg font-semibold">
                    Karya pertama dimulai di sini
                </h3>
                <p class="text-muted-foreground">
                    Tambahkan proyek dengan screenshot dan cerita kontribusi
                    Anda.
                </p>
                <Link href={projects.create()} class="studio-button mt-3"
                    >Buat proyek</Link
                >
            </div>{/if}
    </section>
    <div class="mt-6 flex flex-wrap gap-3">
        {#if page.props.auth.canManagePosts}<Link
                href={posts.create()}
                class="studio-button quiet">Tulis artikel</Link
            >{/if}<Link href={home()} class="studio-button quiet"
            >Buka portfolio ↗</Link
        >
    </div>
</div>
