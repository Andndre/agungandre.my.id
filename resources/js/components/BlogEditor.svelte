<script lang="ts">
    import { Crepe } from '@milkdown/crepe';
    import { insert } from '@milkdown/kit/utils';
    import { onMount } from 'svelte';
    import { blogBlockRemark, calloutNode, embedNode } from '@/lib/blog-blocks';
    import { store as uploadImage } from '@/routes/admin/posts/images';
    import '@milkdown/crepe/theme/common/style.css';
    import '@milkdown/crepe/theme/frame.css';

    let {
        value = $bindable(''),
        onReady,
    }: { value?: string; onReady?: (markdown: string) => void } = $props();
    let root: HTMLDivElement;
    let editor: Crepe | null = null;
    let error = $state('');

    async function upload(file: File): Promise<string> {
        const body = new FormData();
        body.append('image', file);
        const token = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content;
        const response = await fetch(uploadImage().url, {
            method: 'POST',
            body,
            headers: {
                'X-CSRF-TOKEN': token ?? '',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(
                'Gambar gagal diunggah. Periksa jenis file dan pengaturan penyimpanan.',
            );
        }

        const result: { url: string } = await response.json();

        return result.url;
    }

    function insertBlock(
        kind: 'callout:info' | 'callout:warning' | 'embed',
    ): void {
        if (!editor) {
            return;
        }

        if (kind === 'embed') {
            const url = window.prompt(
                'URL HTTPS untuk video YouTube atau kartu tautan',
            );

            if (!url) {
                return;
            }

            try {
                const parsed = new URL(url);

                if (
                    parsed.protocol !== 'https:' ||
                    parsed.username ||
                    parsed.password
                ) {
                    throw new Error();
                }
            } catch {
                error = 'Masukkan URL HTTPS yang valid.';

                return;
            }

            editor.editor.action(
                insert(`\n\n\`\`\`embed\n${url.trim()}\n\`\`\`\n`),
            );
        } else {
            editor.editor.action(
                insert(
                    `\n\n\`\`\`${kind}\nTulis catatan Anda di sini.\n\`\`\`\n`,
                ),
            );
        }

        value = editor.getMarkdown();
        error = '';
    }

    onMount(() => {
        let active = true;
        const instance = new Crepe({
            root,
            defaultValue: value,
            featureConfigs: {
                placeholder: { text: 'Mulai menulis…' },
                'image-block': {
                    onUpload: upload,
                    inlineUploadButton: 'Unggah',
                    inlineUploadPlaceholderText: 'atau tempel tautan',
                    blockUploadButton: 'Unggah gambar',
                    blockConfirmButton: 'Konfirmasi',
                    blockCaptionPlaceholderText: 'Tulis keterangan gambar',
                    blockUploadPlaceholderText: 'atau tempel tautan',
                },
                toolbar: {
                    boldLabel: 'Tebal',
                    italicLabel: 'Miring',
                    codeLabel: 'Kode',
                    linkLabel: 'Tautan',
                    strikethroughLabel: 'Coret',
                    latexLabel: 'Rumus',
                },
                'link-tooltip': { inputPlaceholder: 'Tempel tautan…' },
                'code-mirror': {
                    searchPlaceholder: 'Cari bahasa',
                    copyText: 'Salin',
                    noResultText: 'Tidak ada hasil',
                    previewToggleText: (previewOnly) =>
                        previewOnly ? 'Edit' : 'Sembunyikan',
                },
                'block-edit': {
                    textGroup: {
                        label: 'Teks',
                        text: { label: 'Paragraf' },
                        h1: { label: 'Judul 1' },
                        h2: { label: 'Judul 2' },
                        h3: { label: 'Judul 3' },
                        h4: { label: 'Judul 4' },
                        h5: { label: 'Judul 5' },
                        h6: { label: 'Judul 6' },
                        quote: { label: 'Kutipan' },
                        divider: { label: 'Pemisah' },
                    },
                    listGroup: {
                        label: 'Daftar',
                        bulletList: { label: 'Daftar poin' },
                        orderedList: { label: 'Daftar nomor' },
                        taskList: { label: 'Daftar tugas' },
                    },
                    advancedGroup: {
                        label: 'Lanjutan',
                        image: { label: 'Gambar' },
                        codeBlock: { label: 'Blok kode' },
                        table: { label: 'Tabel' },
                        math: { label: 'Rumus' },
                    },
                },
            },
        });
        instance.editor.use([...blogBlockRemark, calloutNode, embedNode]);
        instance.on((listener) => {
            listener.markdownUpdated((_ctx, markdown) => {
                value = markdown;
            });
        });
        void instance
            .create()
            .then(() => {
                if (!active) {
                    void instance.destroy();

                    return;
                }

                editor = instance;
                value = instance.getMarkdown();
                onReady?.(value);
            })
            .catch(() => {
                if (active) {
                    error = 'Editor tidak dapat dimulai.';
                }
            });

        return () => {
            active = false;
            void instance.destroy();
        };
    });
</script>

<div class="space-y-3">
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="min-h-11 rounded-md border px-3 text-sm hover:bg-muted"
            onclick={() => insertBlock('callout:info')}>+ Catatan</button
        >
        <button
            type="button"
            class="min-h-11 rounded-md border px-3 text-sm hover:bg-muted"
            onclick={() => insertBlock('callout:warning')}>+ Peringatan</button
        >
        <button
            type="button"
            class="min-h-11 rounded-md border px-3 text-sm hover:bg-muted"
            onclick={() => insertBlock('embed')}>+ Sematan</button
        >
    </div>
    <div
        bind:this={root}
        class="blog-editor min-h-96 overflow-hidden rounded-xl border bg-background"
    ></div>
    {#if error}<p class="text-sm text-destructive" role="alert">{error}</p>{/if}
</div>
