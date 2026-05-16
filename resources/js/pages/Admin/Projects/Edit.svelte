<script module lang="ts">
    import admin from '@/routes/admin/projects';
    import projects from '@/routes/admin/projects';

    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/' },
            { title: 'Projects', href: admin.index() },
            { title: 'Edit', href: admin.projects.edit({ project: project.id }) },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    type Project = {
        id: number;
        title: string;
        description: string;
        slug: string;
        cover_image: string | null;
        images: string[] | null;
        tech_stack: string[] | null;
        live_url: string | null;
        repo_url: string | null;
        sort_order: number;
        is_featured: boolean;
        is_published: boolean;
    };

    let {
        project,
    }: {
        project: Project;
    } = $props();

    let techInput = $state('');
    let techStack = $state<string[]>([...(project.tech_stack ?? [])]);
    let showDeleteConfirm = $state(false);

    function addTech() {
        const trimmed = techInput.trim();

        if (trimmed && !techStack.includes(trimmed) && techStack.length < 20) {
            techStack = [...techStack, trimmed];
            techInput = '';
        }
    }

    function removeTech(tech: string) {
        techStack = techStack.filter((t) => t !== tech);
    }

    function handleTechKeydown(e: KeyboardEvent) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTech();
        }

        if (e.key === 'Backspace' && !techInput && techStack.length > 0) {
            techStack = techStack.slice(0, -1);
        }
    }
</script>

<AppHead title={project.title} />

<div class="flex max-w-2xl flex-col gap-6">
    <div class="flex items-center gap-4">
        <Link
            href={admin.index()}
            class="flex h-9 items-center gap-2 rounded-lg border border-border bg-background px-3 text-sm font-medium transition-colors hover:bg-muted"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </Link>
        <div>
            <h1 class="text-2xl font-bold">Edit Project</h1>
            <p class="mt-1 text-sm text-muted-foreground">Update project details.</p>
        </div>
    </div>

    <Form
        {...projects.update.form({ query: { project: project.id } })}
        method="post"
        enctype="multipart/form-data"
        class="flex flex-col gap-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <input type="hidden" name="_method" value="put" />
            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Basic Information</h2>
                <div class="flex flex-col gap-4">
                    <div class="grid gap-2">
                        <Label for="title">Title *</Label>
                        <Input
                            id="title"
                            name="title"
                            type="text"
                            required
                            value={project.title}
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="slug">Slug *</Label>
                        <Input
                            id="slug"
                            name="slug"
                            type="text"
                            required
                            value={project.slug}
                        />
                        <InputError message={errors.slug} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description *</Label>
                        <textarea
                            id="description"
                            name="description"
                            required
                            rows="4"
                            class="flex w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >{project.description}</textarea>
                        <InputError message={errors.description} />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Images</h2>
                <div class="flex flex-col gap-4">
                    {#if project.cover_image}
                        <div class="grid gap-2">
                            <Label>Current Cover Image</Label>
                            <div class="relative aspect-video w-full max-w-sm overflow-hidden rounded-lg border border-border">
                                <img
                                    src={project.cover_image}
                                    alt={project.title}
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">Upload a new image to replace the current one.</p>
                        </div>
                    {/if}

                    <div class="grid gap-2">
                        <Label for="cover_image">{project.cover_image ? 'Replace Cover Image' : 'Cover Image *'}</Label>
                        <Input
                            id="cover_image"
                            name="cover_image"
                            type="file"
                            accept="image/*"
                        />
                        <InputError message={errors.cover_image} />
                    </div>

                    {#if project.images && project.images.length > 0}
                        <div class="grid gap-2">
                            <Label>Current Gallery</Label>
                            <div class="flex flex-wrap gap-2">
                                {#each project.images as img (img)}
                                    <div class="relative h-20 w-20 overflow-hidden rounded-lg border border-border">
                                        <img src={img} alt="Gallery" class="h-full w-full object-cover" />
                                    </div>
                                {/each}
                            </div>
                            <p class="text-xs text-muted-foreground">Upload new images to replace gallery. Old images will be deleted.</p>
                        </div>
                    {/if}

                    <div class="grid gap-2">
                        <Label for="images">Gallery Images</Label>
                        <Input
                            id="images"
                            name="images"
                            type="file"
                            accept="image/*"
                            multiple
                        />
                        <InputError message={errors.images} />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Tech Stack</h2>
                <div class="grid gap-2">
                    <Label for="tech_input">Add Technologies</Label>
                    <div class="flex flex-wrap gap-2">
                        {#each techStack as tech (tech)}
                            <span class="inline-flex items-center gap-1 rounded-full bg-secondary px-3 py-1 font-mono text-xs text-secondary-foreground">
                                {tech}
                                <button type="button" onclick={() => removeTech(tech)} class="ml-1 hover:text-destructive">
                                    ×
                                </button>
                            </span>
                        {/each}
                    </div>
                    <Input
                        id="tech_input"
                        type="text"
                        placeholder="Type a tech and press Enter"
                        bind:value={techInput}
                        onkeydown={handleTechKeydown}
                        onblur={addTech}
                    />
                    {#each techStack as tech (tech)}
                        <input type="hidden" name="tech_stack[]" value={tech} />
                    {/each}
                    <InputError message={errors.tech_stack} />
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Links</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="live_url">Live URL</Label>
                        <Input
                            id="live_url"
                            name="live_url"
                            type="url"
                            placeholder="https://example.com"
                            value={project.live_url ?? ''}
                        />
                        <InputError message={errors.live_url} />
                    </div>
                    <div class="grid gap-2">
                        <Label for="repo_url">Repository URL</Label>
                        <Input
                            id="repo_url"
                            name="repo_url"
                            type="url"
                            placeholder="https://github.com/username/repo"
                            value={project.repo_url ?? ''}
                        />
                        <InputError message={errors.repo_url} />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Settings</h2>
                <div class="grid gap-2">
                    <Label for="sort_order">Sort Order</Label>
                    <Input
                        id="sort_order"
                        name="sort_order"
                        type="number"
                        min="0"
                        value={project.sort_order}
                    />
                    <InputError message={errors.sort_order} />
                </div>
                <div class="mt-4 flex flex-col gap-3">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            name="is_featured"
                            value="1"
                            checked={project.is_featured}
                            class="h-4 w-4 rounded border-muted-foreground text-primary focus:ring-primary"
                        />
                        <div>
                            <span class="font-medium">Featured Project</span>
                            <p class="text-xs text-muted-foreground">Show in featured section on homepage.</p>
                        </div>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            checked={project.is_published}
                            class="h-4 w-4 rounded border-muted-foreground text-primary focus:ring-primary"
                        />
                        <div>
                            <span class="font-medium">Published</span>
                            <p class="text-xs text-muted-foreground">Visible on the public portfolio page.</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    {#if showDeleteConfirm}
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-destructive">Are you sure?</span>
                            <Form
                                {...projects.destroy.form({ query: { project: project.id } })}
                                method="post"
                                options={{ preserveScroll: true }}
                            >
                                {#snippet children({ processing: deleteProcessing })}
                                    <input type="hidden" name="_method" value="delete" />
                                    <Button type="submit" variant="destructive" size="sm" disabled={deleteProcessing}>
                                        {deleteProcessing ? 'Deleting...' : 'Yes, Delete'}
                                    </Button>
                                {/snippet}
                            </Form>
                            <Button variant="outline" size="sm" onclick={() => {
 showDeleteConfirm = false; 
}}>
                                Cancel
                            </Button>
                        </div>
                    {:else}
                        <Button variant="outline" onclick={() => {
 showDeleteConfirm = true; 
}}>
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Project
                        </Button>
                    {/if}
                </div>
                <div class="flex gap-3">
                    <Link
                        href={admin.index()}
                        class="rounded-lg border border-border bg-background px-6 py-2 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        Cancel
                    </Link>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving...' : 'Save Changes'}
                    </Button>
                </div>
            </div>
        {/snippet}
    </Form>
</div>