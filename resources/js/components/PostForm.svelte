<script lang="ts">
    import { Form, Link, useHttp } from '@inertiajs/svelte';
    import { untrack, onDestroy } from 'svelte';
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
    import posts from '@/routes/admin/posts';
    import type { AdminPost } from '@/types/blog';

    let { post = null }: { post?: AdminPost | null } = $props();
    let title = $state(untrack(() => post?.title ?? ''));
    let excerpt = $state(untrack(() => post?.excerpt ?? ''));
    let content = $state(untrack(() => post?.content ?? ''));
    let publication = $state<'draft' | 'now' | 'scheduled'>(
        untrack(() =>
            !post?.published_at
                ? 'draft'
                : new Date(post.published_at) > new Date()
                  ? 'scheduled'
                  : 'now',
        ),
    );

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
    const action = $derived(
        post ? posts.update({ post: post.id }).url : posts.store().url,
    );
    const scheduledIso = $derived.by(() => {
        if (publication !== 'scheduled' || !schedule) {
            return '';
        }

        const date = new Date(schedule);

        return Number.isNaN(date.getTime()) ? '' : date.toISOString();
    });
    let previewOpen = $state(false);
    const signature = $derived(
        JSON.stringify({ title, excerpt, content, publication, schedule }),
    );
    let savedSignature = $state(untrack(() => signature));
    const dirty = $derived(signature !== savedSignature);
    let previewHtml = $state('');
    let previewError = $state('');
    let previewTrigger: HTMLElement | null = null;
    const preview = useHttp<{ content: string }, { contentHtml: string }>({
        content: '',
    });
    async function openPreview(): Promise<void> {
        if (!previewOpen) {
            previewTrigger = document.activeElement as HTMLElement;
        }

        previewOpen = true;
        previewHtml = '';
        previewError = '';
        preview.content = content;

        try {
            const result = await preview.post(posts.preview().url);
            previewHtml = result.contentHtml;
        } catch {
            previewError =
                preview.errors.content ||
                'Preview gagal dimuat. Isi form tetap tersedia; coba lagi.';
        }
    }
    onDestroy(() => preview.cancel());
</script>

<Form
    {action}
    method="post"
    class="space-y-6"
    options={{ preserveScroll: true }}
    onSuccess={() => (savedSignature = signature)}
>
    {#snippet children({ errors, processing, recentlySuccessful })}
        {#if post}<input type="hidden" name="_method" value="put" />{/if}
        <div class="cms-panel space-y-5">
            <div class="space-y-2">
                <Label for="post-title">Judul</Label>
                <Input
                    id="post-title"
                    name="title"
                    required
                    maxlength="255"
                    bind:value={title}
                    aria-invalid={Boolean(errors.title)}
                    aria-describedby="post-title-error"
                />
                <InputError id="post-title-error" message={errors.title} />
            </div>
            <div class="space-y-2">
                <Label for="post-excerpt">Ringkasan</Label>
                <textarea
                    id="post-excerpt"
                    name="excerpt"
                    required
                    maxlength="1000"
                    rows="3"
                    bind:value={excerpt}
                    aria-invalid={Boolean(errors.excerpt)}
                    aria-describedby="post-excerpt-error"
                    class="cms-input"
                ></textarea>
                <InputError id="post-excerpt-error" message={errors.excerpt} />
            </div>
            {#if post}<p class="text-sm text-muted-foreground">
                    Slug tetap: <code>{post.slug}</code>
                </p>{/if}
        </div>

        <div class="cms-panel space-y-3">
            <div>
                <h2 class="font-semibold">Konten</h2>
                <p class="text-sm text-muted-foreground">
                    Gunakan toolbar untuk gambar dan link. Blok khusus bisa
                    ditambah dari tombol di bawah.
                </p>
            </div>
            <BlogEditor
                bind:value={content}
                onReady={(markdown) => {
                    savedSignature = JSON.stringify({
                        ...JSON.parse(savedSignature),
                        content: markdown,
                    });
                }}
            />
            <input type="hidden" name="content" value={content} />
            <InputError message={errors.content} />
        </div>

        <div class="cms-panel space-y-4">
            <h2 class="font-semibold">Publikasi</h2>
            <div class="flex flex-wrap gap-5">
                <label
                    class="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                    ><input
                        type="radio"
                        value="draft"
                        bind:group={publication}
                    /> Draf</label
                >
                <label
                    class="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                    ><input type="radio" value="now" bind:group={publication} /> Terbit
                    sekarang</label
                >
                <label
                    class="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                    ><input
                        type="radio"
                        value="scheduled"
                        bind:group={publication}
                    /> Jadwalkan</label
                >
            </div>
            <input type="hidden" name="publication" value={publication} />
            <input type="hidden" name="published_at" value={scheduledIso} />
            {#if publication === 'scheduled'}
                <div class="max-w-xs space-y-2">
                    <Label for="post-schedule"
                        >Waktu terbit (zona waktu perangkat)</Label
                    >
                    <Input
                        id="post-schedule"
                        aria-invalid={Boolean(errors.published_at)}
                        aria-describedby="schedule-error"
                        type="datetime-local"
                        required
                        bind:value={schedule}
                    />
                    <InputError
                        id="schedule-error"
                        message={errors.published_at}
                    />
                </div>
            {/if}
            <InputError message={errors.publication} />
        </div>

        <div class="cms-actionbar">
            <p
                role="status"
                aria-live="polite"
                class="text-sm text-muted-foreground"
            >
                {processing
                    ? 'Menyimpan artikel…'
                    : recentlySuccessful
                      ? 'Artikel tersimpan.'
                      : dirty
                        ? 'Perubahan belum disimpan'
                        : 'Tidak ada perubahan'}
            </p>
            <Button
                variant="outline"
                onclick={openPreview}
                disabled={preview.processing}>Preview</Button
            >
            <Link href={posts.index()} class="studio-button quiet">Batal</Link>
            <Button type="submit" disabled={processing}
                >{processing ? 'Menyimpan…' : 'Simpan artikel'}</Button
            >
        </div>
    {/snippet}
</Form>
<Dialog bind:open={previewOpen}
    ><DialogContent
        class="max-w-4xl"
        onCloseAutoFocus={(event) => {
            event.preventDefault();

            if (previewTrigger?.isConnected) {
                previewTrigger.focus();
            }
        }}
        ><DialogTitle>Preview artikel</DialogTitle><DialogDescription
            >Markdown dirender dan disanitasi di server, tanpa menyimpan
            artikel.</DialogDescription
        >
        <div role="status" class="mt-5 text-sm text-muted-foreground">
            {preview.processing ? 'Menyiapkan preview…' : ''}
        </div>
        {#if previewError}<p role="alert" class="mt-4 text-sm text-destructive">
                {previewError}
            </p>
            <Button class="mt-4" variant="outline" onclick={openPreview}
                >Coba lagi</Button
            >{:else if !preview.processing}<article class="mt-6">
                <h2 class="break-words text-3xl font-semibold">
                    {title || 'Judul artikel'}
                </h2>
                <p class="mt-4 text-muted-foreground">{excerpt}</p>
                <div class="studio-prose prose mt-8 max-w-none">
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -- sanitized by the existing server renderer, identical to the public article -->
                    {@html previewHtml}
                </div>
            </article>{/if}
    </DialogContent></Dialog
>
