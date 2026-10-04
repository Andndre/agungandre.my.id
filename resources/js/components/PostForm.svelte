<script lang="ts">
    import { Link, router, useForm, useHttp } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import Eye from 'lucide-svelte/icons/eye';
    import Settings2 from 'lucide-svelte/icons/settings-2';
    import { untrack, onMount, onDestroy, tick } from 'svelte';
    import BlogEditor from '@/components/BlogEditor.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogTitle,
        DialogDescription,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Sheet,
        SheetContent,
        SheetTitle,
        SheetDescription,
    } from '@/components/ui/sheet';
    import posts from '@/routes/admin/posts';
    import type { AdminPost } from '@/types/blog';

    let { post = null }: { post?: AdminPost | null } = $props();
    let contentEditor: { currentMarkdown: () => string };
    const form = useForm({
        title: untrack(() => post?.title ?? ''),
        excerpt: untrack(() => post?.excerpt ?? ''),
        content: untrack(() => post?.content ?? ''),
        publication: untrack(() =>
            !post?.published_at
                ? 'draft'
                : new Date(post.published_at) > new Date()
                  ? 'scheduled'
                  : 'now',
        ) as 'draft' | 'now' | 'scheduled',
        published_at: '',
    });
    const deletion = useForm({});
    function localDateTime(value: string | null | undefined): string {
        if (!value) {
            return '';
        }

        const date = new Date(value);

        return new Date(date.getTime() - date.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);
    }
    let schedule = $state(untrack(() => localDateTime(post?.published_at)));
    const scheduledIso = $derived.by(() => {
        if (form.publication !== 'scheduled' || !schedule) {
            return '';
        }

        const date = new Date(schedule);

        return Number.isNaN(date.getTime()) ? '' : date.toISOString();
    });
    const signature = $derived(
        JSON.stringify({
            title: form.title,
            excerpt: form.excerpt,
            content: form.content,
            publication: form.publication,
            schedule,
        }),
    );
    let savedSignature = $state(untrack(() => signature));
    const dirty = $derived(signature !== savedSignature);
    let wide = $state(false);
    let settingsOpen = $state(false);
    let confirmingDelete = $state(false);
    let leavingOpen = $state(false);
    let pendingVisit: (() => void) | null = null;
    let allowNavigation = false;
    let saveError = $state('');
    let timezone = $state('zona waktu perangkat');
    let previewOpen = $state(false);
    let previewHtml = $state('');
    let previewError = $state('');
    let previewTitle = $state('');
    let previewExcerpt = $state('');
    let previewTrigger: HTMLElement | null = null;
    let previewVersion = 0;
    const preview = useHttp<{ content: string }, { contentHtml: string }>({
        content: '',
    });

    function save(event: SubmitEvent): void {
        event.preventDefault();

        if (form.processing || deletion.processing) {
            return;
        }

        form.content = contentEditor.currentMarkdown();

        const submittedSignature = signature;
        const submitted = { ...form.data(), published_at: scheduledIso };
        saveError = '';
        form.transform(() => submitted).submit(
            post ? posts.update({ post: post.id }) : posts.store(),
            {
                preserveScroll: true,
                onSuccess: () => {
                    savedSignature = submittedSignature;
                    form.defaults(submitted);
                },
                onError: async (errors) => {
                    if (
                        !wide &&
                        (errors.excerpt ||
                            errors.publication ||
                            errors.published_at)
                    ) {
                        settingsOpen = true;
                    }

                    await tick();
                    document
                        .querySelector<HTMLElement>('[aria-invalid="true"]')
                        ?.focus();
                },
            },
        );
    }

    async function openPreview(): Promise<void> {
        form.content = contentEditor.currentMarkdown();

        if (!previewOpen) {
            previewTrigger = document.activeElement as HTMLElement;
        }

        const version = ++previewVersion;
        preview.cancel();
        previewOpen = true;
        previewHtml = '';
        previewError = '';
        previewTitle = form.title;
        previewExcerpt = form.excerpt;
        preview.content = form.content;

        try {
            const result = await preview.post(posts.preview().url);

            if (version === previewVersion && previewOpen) {
                previewHtml = result.contentHtml;
            }
        } catch {
            if (version === previewVersion && previewOpen) {
                previewError =
                    preview.errors.content ||
                    'Preview gagal dimuat. Isi Anda tetap tersedia; coba lagi.';
            }
        }
    }

    function closePreview(open: boolean): void {
        previewOpen = open;

        if (!open) {
            previewVersion++;
            preview.cancel();
        }
    }

    function deleteArticle(): void {
        if (!post || deletion.processing) {
            return;
        }

        allowNavigation = true;
        deletion.delete(posts.destroy({ post: post.id }).url, {
            onError: () => {
                allowNavigation = false;
            },
        });
    }

    onMount(() => {
        const media = window.matchMedia('(min-width: 1280px)');
        const updateWidth = (): void => {
            wide = media.matches;

            if (wide) {
                settingsOpen = false;
            }
        };
        updateWidth();
        media.addEventListener('change', updateWidth);
        timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const beforeUnload = (event: BeforeUnloadEvent): void => {
            if (dirty && !allowNavigation) {
                event.preventDefault();
                event.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', beforeUnload);
        const removeBefore = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (!allowNavigation && dirty && visit.method === 'get') {
                event.preventDefault();
                pendingVisit = () => router.visit(visit.url, visit);
                leavingOpen = true;
            }
        });
        const removeNetwork = router.on('networkError', (event) => {
            if (form.processing || deletion.processing) {
                event.preventDefault();
                allowNavigation = false;
                saveError =
                    'Koneksi terputus. Perubahan belum disimpan; coba lagi.';
            }
        });
        const removeException = router.on('httpException', (event) => {
            if (form.processing || deletion.processing) {
                event.preventDefault();
                allowNavigation = false;
                saveError =
                    'Artikel gagal disimpan. Perubahan tetap tersedia; coba lagi.';
            }
        });

        return () => {
            media.removeEventListener('change', updateWidth);
            window.removeEventListener('beforeunload', beforeUnload);
            removeBefore();
            removeNetwork();
            removeException();
        };
    });
    onDestroy(() => {
        preview.cancel();
        form.cancel();
        deletion.cancel();
    });
</script>

{#snippet settings()}
    <fieldset class="space-y-6" disabled={!post && form.processing}>
        <div class="space-y-2">
            <Label for="post-excerpt">Ringkasan</Label>
            <textarea
                id="post-excerpt"
                maxlength="1000"
                rows="5"
                bind:value={form.excerpt}
                aria-invalid={Boolean(form.errors.excerpt)}
                aria-describedby="post-excerpt-error"
                class="cms-input"
            ></textarea>
            <p class="text-xs text-muted-foreground">
                Ditampilkan pada daftar tulisan dan pembuka artikel.
            </p>
            <InputError id="post-excerpt-error" message={form.errors.excerpt} />
        </div>
        <fieldset class="space-y-2">
            <legend class="mb-2 text-sm font-semibold">Publikasi</legend>
            {#each [{ value: 'draft', label: 'Draf' }, { value: 'now', label: 'Terbit sekarang' }, { value: 'scheduled', label: 'Jadwalkan' }] as option (option.value)}
                <label
                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-md px-2 text-sm hover:bg-muted"
                >
                    <input
                        type="radio"
                        name="publication"
                        value={option.value}
                        bind:group={form.publication}
                        aria-describedby="post-publication-error"
                    />{option.label}
                </label>
            {/each}
            <InputError
                id="post-publication-error"
                message={form.errors.publication}
            />
        </fieldset>
        {#if form.publication === 'scheduled'}
            <div class="space-y-2">
                <Label for="post-schedule">Waktu terbit</Label>
                <Input
                    id="post-schedule"
                    type="datetime-local"
                    bind:value={schedule}
                    aria-invalid={Boolean(form.errors.published_at)}
                    aria-describedby="schedule-timezone schedule-error"
                />
                <p id="schedule-timezone" class="text-xs text-muted-foreground">
                    {timezone}
                </p>
                <InputError
                    id="schedule-error"
                    message={form.errors.published_at}
                />
            </div>
        {/if}
        {#if post}
            <div class="space-y-2 border-t pt-5">
                <p class="text-sm font-semibold">Alamat artikel</p>
                <p class="text-sm wrap-anywhere text-muted-foreground">
                    <code>{post.slug}</code>
                </p>
                <p class="text-xs text-muted-foreground">
                    Alamat tetap saat judul diubah.
                </p>
            </div>
            <div class="border-t pt-5">
                <Button
                    variant="ghost"
                    class="text-destructive"
                    onclick={() => (confirmingDelete = true)}
                    >Hapus artikel</Button
                >
            </div>
        {/if}
    </fieldset>
{/snippet}

<form id="post-form" class="article-workspace" onsubmit={save} novalidate>
    <header class="article-actionbar">
        <div class="flex min-w-0 items-center gap-3">
            <Link
                href={posts.index()}
                class="grid size-11 shrink-0 place-items-center rounded-md hover:bg-muted"
                aria-label="Kembali ke daftar tulisan"
                ><ArrowLeft class="size-5" aria-hidden="true" /></Link
            >
            <div class="min-w-0">
                <h1 class="text-sm font-semibold">
                    {post ? 'Edit artikel' : 'Artikel baru'}
                </h1>
                <p
                    role="status"
                    aria-live="polite"
                    class="text-xs text-muted-foreground"
                >
                    {form.processing
                        ? 'Menyimpan artikel…'
                        : dirty
                          ? 'Perubahan belum disimpan'
                          : form.recentlySuccessful
                            ? 'Artikel tersimpan.'
                            : 'Tidak ada perubahan'}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="status-label"
                >{form.publication === 'draft'
                    ? 'Draf'
                    : form.publication === 'scheduled'
                      ? 'Terjadwal'
                      : 'Terbit'}</span
            >
            <Button
                variant="ghost"
                onclick={openPreview}
                disabled={preview.processing}
                ><Eye class="size-4" aria-hidden="true" />Preview</Button
            >
            {#if !wide}<Button
                    variant="outline"
                    aria-label="Pengaturan artikel"
                    onclick={() => (settingsOpen = true)}
                    ><Settings2 class="size-4" aria-hidden="true" /><span
                        class="hidden sm:inline">Pengaturan</span
                    ></Button
                >{/if}
            <Button
                type="submit"
                disabled={form.processing || deletion.processing}
                >{form.processing ? 'Menyimpan…' : 'Simpan artikel'}</Button
            >
        </div>
    </header>
    {#if saveError}<p role="alert" class="px-6 py-3 text-sm text-destructive">
            {saveError}
        </p>{/if}
    <div class="article-columns">
        <section
            class="article-canvas"
            aria-label="Dokumen artikel"
            inert={!post && form.processing}
        >
            <Label for="post-title" class="sr-only">Judul artikel</Label>
            <textarea
                id="post-title"
                class="article-title"
                rows="2"
                maxlength="255"
                placeholder="Judul artikel"
                bind:value={form.title}
                aria-invalid={Boolean(form.errors.title)}
                aria-describedby="post-title-error"
            ></textarea>
            <InputError id="post-title-error" message={form.errors.title} />
            <BlogEditor
                bind:this={contentEditor}
                bind:value={form.content}
                invalid={Boolean(form.errors.content)}
                onReady={(markdown) => {
                    savedSignature = JSON.stringify({
                        ...JSON.parse(savedSignature),
                        content: markdown,
                    });
                    form.defaults('content', markdown);
                }}
            />
            <InputError id="post-content-error" message={form.errors.content} />
        </section>
        {#if wide}<aside
                class="article-settings"
                aria-label="Pengaturan artikel"
            >
                <h2 class="mb-6 text-sm font-semibold">Pengaturan artikel</h2>
                {@render settings()}
            </aside>{/if}
    </div>
</form>
{#if !wide}
    <Sheet bind:open={settingsOpen}
        ><SheetContent
            onCloseAutoFocus={(event) => {
                event.preventDefault();
                document
                    .querySelector<HTMLElement>(
                        'button[aria-label="Pengaturan artikel"]',
                    )
                    ?.focus();
            }}
            ><SheetTitle>Pengaturan artikel</SheetTitle><SheetDescription
                >Atur ringkasan dan waktu publikasi sebelum menyimpan.</SheetDescription
            >
            <div class="mt-4">{@render settings()}</div></SheetContent
        ></Sheet
    >
{/if}
<Dialog open={previewOpen} onOpenChange={closePreview}>
    <DialogContent
        class="max-w-4xl"
        onCloseAutoFocus={(event) => {
            event.preventDefault();

            if (previewTrigger?.isConnected) {
                previewTrigger.focus();
            }
        }}
    >
        <DialogTitle>Preview artikel</DialogTitle><DialogDescription
            >Tampilan artikel dengan perubahan saat ini. Preview tidak menyimpan
            artikel.</DialogDescription
        >
        {#if preview.processing}<p
                role="status"
                class="mt-5 animate-pulse text-sm text-muted-foreground"
            >
                Menyiapkan preview…
            </p>{/if}
        {#if previewError}<p role="alert" class="mt-4 text-sm text-destructive">
                {previewError}
            </p>
            <Button class="mt-4" variant="outline" onclick={openPreview}
                >Coba lagi</Button
            >
        {:else if !preview.processing}
            <article class="mx-auto mt-6 max-w-prose">
                <h2 class="text-3xl font-semibold wrap-anywhere">
                    {previewTitle || 'Judul artikel'}
                </h2>
                <p class="mt-4 text-muted-foreground">{previewExcerpt}</p>
                <div class="studio-prose prose mt-8 max-w-none">
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -- sanitized by the shared server renderer -->
                    {@html previewHtml}
                </div>
            </article>
        {/if}
    </DialogContent>
</Dialog>
<Dialog bind:open={confirmingDelete}
    ><DialogContent
        ><DialogTitle>Hapus artikel?</DialogTitle><DialogDescription
            >Artikel akan dihapus permanen, termasuk perubahan yang belum
            disimpan.</DialogDescription
        >
        <div class="mt-5 flex justify-end gap-2">
            <Button variant="outline" onclick={() => (confirmingDelete = false)}
                >Batal</Button
            ><Button
                variant="destructive"
                disabled={deletion.processing}
                onclick={deleteArticle}
                >{deletion.processing ? 'Menghapus…' : 'Ya, hapus'}</Button
            >
        </div></DialogContent
    ></Dialog
>
<Dialog bind:open={leavingOpen}
    ><DialogContent
        ><DialogTitle>Tinggalkan perubahan?</DialogTitle><DialogDescription
            >Perubahan artikel belum disimpan. Simpan terlebih dahulu jika ingin
            mempertahankannya.</DialogDescription
        >
        <div class="mt-5 flex justify-end gap-2">
            <Button variant="outline" onclick={() => (leavingOpen = false)}
                >Tetap menulis</Button
            ><Button
                onclick={() => {
                    allowNavigation = true;
                    leavingOpen = false;
                    pendingVisit?.();
                }}>Tinggalkan</Button
            >
        </div></DialogContent
    ></Dialog
>
