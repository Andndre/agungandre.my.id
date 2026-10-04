<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import X from 'lucide-svelte/icons/x';
    import { onDestroy, untrack } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import {
        Dialog,
        DialogContent,
        DialogTitle,
        DialogDescription,
    } from '@/components/ui/dialog';
    import projects from '@/routes/admin/projects';
    import type { AdminProject } from '@/types/project';
    let { project = null }: { project?: AdminProject | null } = $props();
    let title = $state(untrack(() => project?.title ?? ''));
    let slug = $state(untrack(() => project?.slug ?? ''));
    let description = $state(untrack(() => project?.description ?? ''));
    let liveUrl = $state(untrack(() => project?.live_url ?? ''));
    let repoUrl = $state(untrack(() => project?.repo_url ?? ''));
    let order = $state(untrack(() => project?.sort_order ?? 0));
    let featured = $state(untrack(() => project?.is_featured ?? false));
    let published = $state(untrack(() => project?.is_published ?? false));
    let techStack = $state<string[]>(
        untrack(() => [...(project?.tech_stack ?? [])]),
    );
    let techInput = $state('');
    let previewOpen = $state(false);
    let coverPreview = $state(untrack(() => project?.cover_image_url ?? ''));
    let galleryPreview = $state<string[]>(
        untrack(() => [...(project?.gallery_urls ?? [])]),
    );
    let objectUrls: string[] = [];
    let mediaChanged = $state(false);
    const signature = $derived(
        JSON.stringify({
            title,
            slug,
            description,
            liveUrl,
            repoUrl,
            order,
            featured,
            published,
            techStack,
        }),
    );
    let savedSignature = $state(untrack(() => signature));
    const dirty = $derived(signature !== savedSignature || mediaChanged);
    function addTech(): void {
        const value = techInput.trim();

        if (value && !techStack.includes(value) && techStack.length < 20) {
            techStack = [...techStack, value];
            techInput = '';
        }
    }
    function previewFiles(event: Event, cover: boolean): void {
        const input = event.currentTarget as HTMLInputElement;
        const files = Array.from(input.files ?? []);
        const urls = files.map((file) => {
            const url = URL.createObjectURL(file);
            objectUrls.push(url);

            return url;
        });
        mediaChanged = true;

        if (cover) {
            coverPreview = urls[0] ?? project?.cover_image_url ?? '';
        } else {
            galleryPreview = urls.length ? urls : (project?.gallery_urls ?? []);
        }
    }
    onDestroy(() => objectUrls.forEach((url) => URL.revokeObjectURL(url)));
    const action = $derived(
        project
            ? projects.update({ project: project.id }).url
            : projects.store().url,
    );
</script>

<Form
    {action}
    method="post"
    enctype="multipart/form-data"
    class="space-y-6"
    options={{ preserveScroll: true }}
    onSuccess={() => {
        savedSignature = signature;
        mediaChanged = false;
    }}
>
    {#snippet children({ errors, processing, recentlySuccessful })}
        {#if project}<input type="hidden" name="_method" value="put" />{/if}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-6">
                <section class="cms-panel space-y-5">
                    <div>
                        <h2 class="font-semibold">Informasi proyek</h2>
                        <p class="cms-help mt-1">
                            Jelaskan kebutuhan, kontribusi, dan hasil yang dapat
                            dibuktikan.
                        </p>
                    </div>
                    <div class="cms-field">
                        <label class="cms-label" for="project-title"
                            >Judul <span aria-hidden="true">*</span></label
                        ><input
                            class="cms-input"
                            id="project-title"
                            name="title"
                            required
                            maxlength="255"
                            bind:value={title}
                            aria-invalid={Boolean(errors.title)}
                            aria-describedby="project-title-error"
                        /><InputError
                            id="project-title-error"
                            message={errors.title}
                        />
                    </div>
                    <div class="cms-field">
                        <label class="cms-label" for="project-slug"
                            >Slug <span aria-hidden="true">*</span></label
                        ><input
                            class="cms-input"
                            id="project-slug"
                            name="slug"
                            required
                            maxlength="255"
                            pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                            bind:value={slug}
                            aria-invalid={Boolean(errors.slug)}
                            aria-describedby="slug-help project-slug-error"
                        />
                        <p id="slug-help" class="cms-help">
                            Huruf kecil, angka, dan tanda hubung. Digunakan pada
                            URL detail proyek.
                        </p>
                        <InputError
                            id="project-slug-error"
                            message={errors.slug}
                        />
                    </div>
                    <div class="cms-field">
                        <label class="cms-label" for="project-description"
                            >Deskripsi <span aria-hidden="true">*</span></label
                        ><textarea
                            class="cms-input min-h-52"
                            id="project-description"
                            name="description"
                            required
                            rows="8"
                            bind:value={description}
                            aria-invalid={Boolean(errors.description)}
                            aria-describedby="description-help project-description-error"
                        ></textarea>
                        <p id="description-help" class="cms-help">
                            Teks biasa; paragraf dipertahankan. HTML tidak
                            dijalankan.
                        </p>
                        <InputError
                            id="project-description-error"
                            message={errors.description}
                        />
                    </div>
                </section>
                <section class="cms-panel space-y-5">
                    <div>
                        <h2 class="font-semibold">Media</h2>
                        <p class="cms-help mt-1">
                            Gunakan screenshot karya asli. Target cover WebP
                            ≤200 KB untuk pemuatan awal.
                        </p>
                    </div>
                    <div class="cms-field">
                        <label class="cms-label" for="project-cover"
                            >Cover {!project ? '*' : ''}</label
                        >{#if coverPreview}<img
                                src={coverPreview}
                                alt="Pratinjau cover"
                                width="1200"
                                height="750"
                                class="aspect-video w-full rounded-xl bg-muted object-contain"
                            />{/if}<input
                            class="cms-input"
                            id="project-cover"
                            name="cover_image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            required={!project}
                            onchange={(event) => previewFiles(event, true)}
                            aria-describedby="cover-help cover-error"
                            aria-invalid={Boolean(errors.cover_image)}
                        />
                        <p id="cover-help" class="cms-help">
                            JPEG, PNG, WebP, atau GIF. Maksimal 2 MB. Kosongkan
                            untuk mempertahankan cover saat edit.
                        </p>
                        <InputError
                            id="cover-error"
                            message={errors.cover_image}
                        />
                    </div>
                    <div class="cms-field">
                        <label class="cms-label" for="project-gallery"
                            >Galeri</label
                        ><input
                            class="cms-input"
                            id="project-gallery"
                            name="images[]"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            multiple
                            onchange={(event) => previewFiles(event, false)}
                            aria-describedby={'gallery-help gallery-error ' +
                                Object.keys(errors)
                                    .filter((key) => key.startsWith('images.'))
                                    .map(
                                        (key) =>
                                            'error-' + key.replaceAll('.', '-'),
                                    )
                                    .join(' ')}
                            aria-invalid={Object.keys(errors).some(
                                (key) =>
                                    key === 'images' ||
                                    key.startsWith('images.'),
                            )}
                        />
                        <p id="gallery-help" class="cms-help">
                            Maksimal 10 gambar, 2 MB per gambar. Pilihan baru
                            menggantikan seluruh galeri. Tanpa pilihan baru,
                            galeri lama tetap disimpan.
                        </p>
                        <InputError
                            id="gallery-error"
                            message={errors.images}
                        />{#each Object.entries(errors).filter( ([key]) => key.startsWith('images.'), ) as [key, message] (key)}<InputError
                                id={'error-' + key.replaceAll('.', '-')}
                                {message}
                            />{/each}
                        {#if galleryPreview.length}<div
                                class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                            >
                                {#each galleryPreview as url, i (url)}<img
                                        src={url}
                                        alt={'Galeri ' + (i + 1)}
                                        width="600"
                                        height="400"
                                        loading="lazy"
                                        class="aspect-video w-full rounded-lg bg-muted object-contain"
                                    />{/each}
                            </div>{/if}
                    </div>
                </section>
                <section class="cms-panel space-y-5">
                    <h2 class="font-semibold">Teknologi & tautan</h2>
                    <div class="cms-field">
                        <label class="cms-label" for="project-tech"
                            >Teknologi</label
                        >
                        <div class="flex gap-2">
                            <input
                                class="cms-input"
                                id="project-tech"
                                bind:value={techInput}
                                placeholder="Contoh: Laravel"
                                onkeydown={(event) => {
                                    if (
                                        event.key === 'Enter' ||
                                        event.key === ','
                                    ) {
                                        event.preventDefault();
                                        addTech();
                                    }
                                }}
                            /><Button onclick={addTech} variant="outline"
                                >Tambah</Button
                            >
                        </div>
                        <p class="cms-help">
                            Tekan Enter untuk menambah. Maksimal 20 teknologi.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            {#each techStack as tech (tech)}<input
                                    type="hidden"
                                    name="tech_stack[]"
                                    value={tech}
                                /><button
                                    type="button"
                                    class="studio-tag min-h-11 gap-3"
                                    aria-label={'Hapus ' + tech}
                                    onclick={() =>
                                        (techStack = techStack.filter(
                                            (item) => item !== tech,
                                        ))}
                                    >{tech}<X
                                        class="size-4"
                                        aria-hidden="true"
                                    /></button
                                >{/each}
                        </div>
                        <InputError message={errors.tech_stack} />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="cms-field">
                            <label class="cms-label" for="project-live"
                                >URL live</label
                            ><input
                                class="cms-input"
                                id="project-live"
                                name="live_url"
                                type="url"
                                bind:value={liveUrl}
                                aria-describedby="live-error"
                            /><InputError
                                id="live-error"
                                message={errors.live_url}
                            />
                        </div>
                        <div class="cms-field">
                            <label class="cms-label" for="project-repo"
                                >URL repository</label
                            ><input
                                class="cms-input"
                                id="project-repo"
                                name="repo_url"
                                type="url"
                                bind:value={repoUrl}
                                aria-describedby="repo-error"
                            /><InputError
                                id="repo-error"
                                message={errors.repo_url}
                            />
                        </div>
                    </div>
                </section>
            </div>
            <aside class="cms-panel h-fit space-y-5">
                <h2 class="font-semibold">Publikasi</h2>
                <input
                    type="hidden"
                    name="is_featured"
                    value={featured ? '1' : '0'}
                /><input
                    type="hidden"
                    name="is_published"
                    value={published ? '1' : '0'}
                />
                <label
                    class="flex min-h-11 cursor-pointer items-start gap-3"
                    for="project-published"
                    ><Checkbox
                        id="project-published"
                        bind:checked={published}
                    /><span class="text-sm"
                        ><strong class="block font-medium"
                            >Terbitkan proyek</strong
                        ><span class="cms-help"
                            >Tampil di portfolio dan halaman detail publik.</span
                        ></span
                    ></label
                >
                <label
                    class="flex min-h-11 cursor-pointer items-start gap-3"
                    for="project-featured"
                    ><Checkbox
                        id="project-featured"
                        bind:checked={featured}
                    /><span class="text-sm"
                        ><strong class="block font-medium"
                            >Proyek unggulan</strong
                        ><span class="cms-help"
                            >Diprioritaskan sebagai preview hero setelah terbit.</span
                        ></span
                    ></label
                >
                <div class="cms-field">
                    <label class="cms-label" for="project-order">Urutan</label
                    ><input
                        class="cms-input"
                        id="project-order"
                        name="sort_order"
                        type="number"
                        min="0"
                        bind:value={order}
                    />
                    <p class="cms-help">Angka kecil tampil lebih dahulu.</p>
                    <InputError message={errors.sort_order} />
                </div>
                <InputError message={errors.is_published} /><InputError
                    message={errors.is_featured}
                />
                <p class="cms-help">
                    Preview menampilkan isi form saat ini. Preview tidak
                    menyimpan atau menerbitkan proyek.
                </p>
            </aside>
        </div>
        <div class="cms-actionbar">
            <p
                role="status"
                aria-live="polite"
                class="text-sm text-muted-foreground"
            >
                {processing
                    ? 'Menyimpan proyek…'
                    : recentlySuccessful
                      ? 'Proyek tersimpan.'
                      : dirty
                        ? 'Perubahan belum disimpan'
                        : 'Tidak ada perubahan'}
            </p>
            <div class="flex flex-wrap gap-2">
                <Link
                    href={projects.index()}
                    class="studio-button studio-button-outline">Batal</Link
                ><Button variant="outline" onclick={() => (previewOpen = true)}
                    >Preview</Button
                ><Button type="submit" disabled={processing}
                    >{processing ? 'Menyimpan…' : 'Simpan proyek'}</Button
                >
            </div>
        </div>
    {/snippet}
</Form>
<Dialog bind:open={previewOpen}
    ><DialogContent class="max-w-4xl"
        ><DialogTitle>Preview proyek</DialogTitle><DialogDescription
            >Isi lokal dari form; belum disimpan.</DialogDescription
        >
        <article class="mt-6 space-y-6">
            <h2 class="wrap-break-word text-3xl font-semibold">
                {title || 'Judul proyek'}
            </h2>
            {#if coverPreview}<img
                    src={coverPreview}
                    alt={title || 'Cover proyek'}
                    width="1200"
                    height="750"
                    class="aspect-video w-full rounded-2xl bg-muted object-contain"
                />{/if}
            <p class="max-w-[68ch] whitespace-pre-line">
                {description || 'Deskripsi proyek akan tampil di sini.'}
            </p>
            <div class="flex flex-wrap gap-2">
                {#each techStack as tech (tech)}<span class="studio-tag"
                        >{tech}</span
                    >{/each}
            </div>
            {#each galleryPreview as url, i (url)}<img
                    src={url}
                    alt={'Preview galeri ' + (i + 1)}
                    width="1200"
                    height="800"
                    loading="lazy"
                    class="w-full rounded-xl"
                />{/each}
        </article></DialogContent
    ></Dialog
>
