<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import BlogEditor from '@/components/BlogEditor.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
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
</script>

<Form
    {action}
    method="post"
    class="space-y-6"
    options={{ preserveScroll: true }}
>
    {#snippet children({ errors, processing })}
        {#if post}<input type="hidden" name="_method" value="put" />{/if}
        <div class="space-y-5 rounded-xl border bg-card p-6">
            <div class="space-y-2">
                <Label for="post-title">Judul</Label>
                <Input
                    id="post-title"
                    name="title"
                    required
                    maxlength="255"
                    bind:value={title}
                />
                <InputError message={errors.title} />
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
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                ></textarea>
                <InputError message={errors.excerpt} />
            </div>
            {#if post}<p class="text-sm text-muted-foreground">
                    Slug tetap: <code>{post.slug}</code>
                </p>{/if}
        </div>

        <div class="space-y-3 rounded-xl border bg-card p-6">
            <div>
                <h2 class="font-semibold">Konten</h2>
                <p class="text-sm text-muted-foreground">
                    Gunakan toolbar untuk gambar dan link. Blok khusus bisa
                    ditambah dari tombol di bawah.
                </p>
            </div>
            <BlogEditor bind:value={content} />
            <input type="hidden" name="content" value={content} />
            <InputError message={errors.content} />
        </div>

        <div class="space-y-4 rounded-xl border bg-card p-6">
            <h2 class="font-semibold">Publikasi</h2>
            <div class="flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm"
                    ><input
                        type="radio"
                        value="draft"
                        bind:group={publication}
                    /> Draf</label
                >
                <label class="flex items-center gap-2 text-sm"
                    ><input type="radio" value="now" bind:group={publication} /> Terbit
                    sekarang</label
                >
                <label class="flex items-center gap-2 text-sm"
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
                        type="datetime-local"
                        required
                        bind:value={schedule}
                    />
                    <InputError message={errors.published_at} />
                </div>
            {/if}
            <InputError message={errors.publication} />
        </div>

        <div class="flex justify-end gap-3">
            <Link
                href={posts.index()}
                class="rounded-lg border px-4 py-2 text-sm hover:bg-muted"
                >Batal</Link
            >
            <Button type="submit" disabled={processing}
                >{processing ? 'Menyimpan…' : 'Simpan artikel'}</Button
            >
        </div>
    {/snippet}
</Form>
