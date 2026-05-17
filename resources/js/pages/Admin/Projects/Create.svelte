<script module lang="ts">
    import admin from '@/routes/admin/projects';

    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/' },
            { title: 'Projects', href: admin.index() },
            { title: 'Create', href: admin.create() },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Link } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { store } from '@/routes/admin/projects';

    let techInput = $state('');
    let techStack = $state<string[]>([]);

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

    onMount(() => {
        // Svelte reactive — nothing else needed
    });
</script>

<AppHead title="Create Project" />

<div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
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
            <h1 class="text-2xl font-bold">Create Project</h1>
            <p class="mt-1 text-sm text-muted-foreground">Add a new project to your portfolio.</p>
        </div>
    </div>

    <Form
        {...store.form()}
        method="post"
        enctype="multipart/form-data"
        class="flex flex-col gap-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
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
                            placeholder="e.g. E-Commerce Dashboard"
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
                            placeholder="e.g. ecommerce-dashboard"
                        />
                        <p class="text-xs text-muted-foreground">URL-friendly identifier. Unique across all projects.</p>
                        <InputError message={errors.slug} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description *</Label>
                        <textarea
                            id="description"
                            name="description"
                            required
                            rows="4"
                            placeholder="Describe what this project does, the problem it solves, and the tech stack used..."
                            class="flex w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        ></textarea>
                        <InputError message={errors.description} />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Images</h2>
                <div class="flex flex-col gap-4">
                    <div class="grid gap-2">
                        <Label for="cover_image">Cover Image *</Label>
                        <Input
                            id="cover_image"
                            name="cover_image"
                            type="file"
                            accept="image/*"
                            required
                        />
                        <p class="text-xs text-muted-foreground">Main project image. Max 2MB. JPEG, PNG, WebP, GIF.</p>
                        <InputError message={errors.cover_image} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="images">Gallery Images</Label>
                        <Input
                            id="images"
                            name="images"
                            type="file"
                            accept="image/*"
                            multiple
                        />
                        <p class="text-xs text-muted-foreground">Additional project screenshots. Max 10 images, 2MB each.</p>
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
                        placeholder="Type a tech (e.g. Laravel) and press Enter or comma"
                        bind:value={techInput}
                        onkeydown={handleTechKeydown}
                        onblur={addTech}
                    />
                    <!-- Hidden inputs for tech_stack array -->
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
                        />
                        <InputError message={errors.repo_url} />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="mb-4 font-semibold">Settings</h2>
                <div class="flex flex-col gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            value="0"
                        />
                        <InputError message={errors.sort_order} />
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            name="is_featured"
                            value="1"
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
                            checked
                            class="h-4 w-4 rounded border-muted-foreground text-primary focus:ring-primary"
                        />
                        <div>
                            <span class="font-medium">Published</span>
                            <p class="text-xs text-muted-foreground">Visible on the public portfolio page.</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <Link
                    href={admin.index()}
                    class="rounded-lg border border-border bg-background px-6 py-2 text-sm font-medium transition-colors hover:bg-muted"
                >
                    Cancel
                </Link>
                <Button type="submit" disabled={processing}>
                    {processing ? 'Creating...' : 'Create Project'}
                </Button>
            </div>
        {/snippet}
    </Form>
    </div>
</div>