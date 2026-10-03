<script lang="ts">
    import { Form, Link, setLayoutProps } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogTitle,
        DialogDescription,
    } from '@/components/ui/dialog';
    import { dashboard } from '@/routes/admin';
    import projectsRoutes from '@/routes/admin/projects';
    import { show } from '@/routes/projects';
    import type { AdminProject } from '@/types/project';
    let { projects }: { projects: AdminProject[] } = $props();
    let query = $state('');
    let status = $state('all');
    let featured = $state(false);
    let deleting = $state<AdminProject | null>(null);
    let deleteOpen = $state(false);
    const filtered = $derived(
        projects.filter(
            (project) =>
                (!query ||
                    (project.title + ' ' + project.tech_stack.join(' '))
                        .toLowerCase()
                        .includes(query.toLowerCase())) &&
                (status === 'all' ||
                    project.is_published === (status === 'published')) &&
                (!featured || project.is_featured),
        ),
    );
    setLayoutProps({
        breadcrumbs: [
            { title: 'Ringkasan', href: dashboard() },
            { title: 'Proyek', href: projectsRoutes.index() },
        ],
    });
    function askDelete(project: AdminProject): void {
        deleting = project;
        deleteOpen = true;
    }
</script>

<AppHead title="Proyek" />
<div class="cms-page">
    <header class="cms-header">
        <div>
            <p class="section-kicker">Portfolio / Konten</p>
            <h1 class="cms-title">Proyek</h1>
            <p class="mt-2 text-muted-foreground">
                {projects.length} proyek · Kelola karya yang tampil di portfolio.
            </p>
        </div>
        <Link
            href={projectsRoutes.create()}
            class="studio-button studio-button-primary">+ Proyek baru</Link
        >
    </header>
    {#if projects.length === 0}<div class="studio-empty">
            <p class="section-kicker">Mulai dengan satu karya</p>
            <h2 class="mt-3 text-2xl font-semibold">Belum ada proyek</h2>
            <p class="mt-3 text-muted-foreground">
                Tambahkan screenshot dan cerita kontribusi Anda. Proyek dapat
                disimpan sebagai draf.
            </p>
            <Link
                href={projectsRoutes.create()}
                class="studio-button studio-button-primary mt-6"
                >Buat proyek pertama</Link
            >
        </div>
    {:else}
        <div class="cms-panel mb-6 flex flex-wrap items-end gap-4">
            <div class="cms-field min-w-0 flex-1">
                <label class="cms-label" for="project-search">Cari proyek</label
                ><input
                    class="cms-input"
                    id="project-search"
                    type="search"
                    bind:value={query}
                    placeholder="Judul atau teknologi…"
                />
            </div>
            <div class="cms-field">
                <label class="cms-label" for="project-filter">Status</label
                ><select
                    class="cms-input"
                    id="project-filter"
                    bind:value={status}
                    ><option value="all">Semua status</option><option
                        value="published">Terbit</option
                    ><option value="draft">Draf</option></select
                >
            </div>
            <label
                class="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                ><input
                    type="checkbox"
                    bind:checked={featured}
                    class="size-5 accent-primary"
                />Hanya unggulan</label
            >
        </div>
        <p role="status" class="mb-3 text-sm text-muted-foreground">
            {filtered.length} proyek ditampilkan
        </p>
        {#if filtered.length === 0}<div class="studio-empty">
                <h2 class="text-xl font-semibold">Tidak ada hasil</h2>
                <p class="mt-2 text-muted-foreground">
                    Coba kata lain atau hapus filter.
                </p>
                <Button
                    class="mt-5"
                    variant="outline"
                    onclick={() => {
                        query = '';
                        status = 'all';
                        featured = false;
                    }}>Hapus filter</Button
                >
            </div>
        {:else}
            <div class="cms-panel hidden overflow-hidden p-0 md:block">
                <table class="cms-table">
                    <thead
                        ><tr
                            ><th>Proyek</th><th>Publikasi</th><th>Unggulan</th
                            ><th>Urutan</th><th
                                ><span class="sr-only">Aksi</span></th
                            ></tr
                        ></thead
                    ><tbody
                        >{#each filtered as project (project.id)}<tr
                                ><td
                                    ><Link
                                        class="font-medium hover:text-primary"
                                        href={projectsRoutes.edit({
                                            project: project.id,
                                        })}>{project.title}</Link
                                    >
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {project.tech_stack
                                            .slice(0, 3)
                                            .join(' · ') || project.slug}
                                    </p></td
                                ><td
                                    ><span
                                        class="cms-status"
                                        class:cms-status-success={project.is_published}
                                        >{project.is_published
                                            ? 'Terbit'
                                            : 'Draf'}</span
                                    ></td
                                ><td>{project.is_featured ? 'Ya' : '—'}</td><td
                                    >{project.sort_order}</td
                                ><td
                                    ><div class="flex justify-end gap-2">
                                        <Link
                                            class="studio-button studio-button-outline"
                                            href={projectsRoutes.edit({
                                                project: project.id,
                                            })}>Edit</Link
                                        ><Button
                                            variant="ghost"
                                            aria-label={'Hapus ' +
                                                project.title}
                                            onclick={() => askDelete(project)}
                                            >Hapus</Button
                                        >
                                    </div></td
                                ></tr
                            >{/each}</tbody
                    >
                </table>
            </div>
            <div class="space-y-3 md:hidden">
                {#each filtered as project (project.id)}<article
                        class="cms-panel space-y-4"
                    >
                        <Link
                            class="block wrap-break-word text-lg font-semibold hover:text-primary"
                            href={projectsRoutes.edit({ project: project.id })}
                            >{project.title}</Link
                        >
                        <div class="flex flex-wrap gap-2">
                            <span
                                class="cms-status"
                                class:cms-status-success={project.is_published}
                                >{project.is_published
                                    ? 'Terbit'
                                    : 'Draf'}</span
                            >{#if project.is_featured}<span class="cms-status"
                                    >Unggulan</span
                                >{/if}<span
                                class="text-sm text-muted-foreground"
                                >Urutan {project.sort_order}</span
                            >
                        </div>
                        <div class="flex gap-2">
                            <Link
                                class="studio-button studio-button-outline"
                                href={projectsRoutes.edit({
                                    project: project.id,
                                })}>Edit proyek</Link
                            >{#if project.is_published}<Link
                                    class="studio-button studio-button-outline"
                                    href={show({ slug: project.slug })}
                                    >Lihat</Link
                                >{/if}<Button
                                variant="ghost"
                                onclick={() => askDelete(project)}>Hapus</Button
                            >
                        </div>
                    </article>{/each}
            </div>
        {/if}{/if}
</div>
<Dialog bind:open={deleteOpen}
    ><DialogContent
        ><DialogTitle>Hapus proyek?</DialogTitle><DialogDescription
            >Proyek “{deleting?.title}” dan media tersimpannya akan dihapus.
            Tindakan ini tidak dapat dibatalkan.</DialogDescription
        >{#if deleting}<Form
                action={projectsRoutes.destroy({ project: deleting.id }).url}
                method="post"
                onSuccess={() => (deleteOpen = false)}
                >{#snippet children({ processing, errors })}<input
                        type="hidden"
                        name="_method"
                        value="delete"
                    />
                    <p role="alert" class="mt-4 text-sm text-destructive">
                        {Object.values(errors).join(' ')}
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <Button
                            variant="outline"
                            onclick={() => (deleteOpen = false)}>Batal</Button
                        ><Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                            >{processing
                                ? 'Menghapus…'
                                : 'Hapus proyek'}</Button
                        >
                    </div>{/snippet}</Form
            >{/if}</DialogContent
    ></Dialog
>
