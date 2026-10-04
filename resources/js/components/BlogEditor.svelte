<script lang="ts">
    import { Crepe } from '@milkdown/crepe';
    import { commandsCtx, editorViewCtx } from '@milkdown/kit/core';
    import { clearTextInCurrentBlockCommand } from '@milkdown/kit/preset/commonmark';
    import type {
        Selection,
        SelectionBookmark,
    } from '@milkdown/kit/prose/state';
    import { NodeSelection, TextSelection } from '@milkdown/kit/prose/state';
    import { insert, replaceAll } from '@milkdown/kit/utils';
    import Plus from 'lucide-svelte/icons/plus';
    import { onMount, tick } from 'svelte';
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
        blogBlockRemark,
        calloutNode,
        configureBlogImages,
        embedNode,
    } from '@/lib/blog-blocks';
    import { store as uploadImage } from '@/routes/admin/posts/images';
    import '@milkdown/crepe/theme/common/style.css';
    import '@milkdown/crepe/theme/frame.css';

    let {
        value = $bindable(''),
        onReady,
        invalid = false,
        errorId = 'post-content-error',
    }: {
        value?: string;
        onReady?: (markdown: string) => void;
        invalid?: boolean;
        errorId?: string;
    } = $props();
    let root: HTMLDivElement;
    let editor = $state.raw<Crepe | null>(null);
    let mode = $state<'visual' | 'markdown'>('visual');
    let embedOpen = $state(false);
    let embedUrl = $state('');
    let embedError = $state('');
    let bookmark: SelectionBookmark | null = null;
    let syncing = false;
    let error = $state('');
    let showImageHelp = $state(false);
    const svg = (paths: string): string =>
        `<svg class="article-block-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    const icons = {
        'callout:info': svg(
            '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>',
        ),
        'callout:warning': svg(
            '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4m0 4h.01"/>',
        ),
        embed: svg(
            '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71m2.25 5.82a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        ),
    };
    $effect(() => {
        editor?.editor.action((ctx) =>
            ctx.get(editorViewCtx).setProps({
                attributes: {
                    role: 'textbox',
                    'aria-label': 'Konten artikel',
                    'aria-multiline': 'true',
                    'aria-invalid': String(invalid),
                    'aria-describedby': errorId,
                },
            }),
        );
    });

    async function upload(file: File): Promise<string> {
        const body = new FormData();
        body.append('image', file);
        const token = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content;

        let uploadError =
            'Gambar gagal diunggah. Coba lagi atau pilih gambar lain.';

        try {
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
                if (response.status === 422) {
                    const validation: { errors?: { image?: string[] } } =
                        await response.json();
                    uploadError = validation.errors?.image?.[0] ?? uploadError;
                }

                throw new Error(uploadError);
            }

            const result: { url: string } = await response.json();
            error = '';

            return result.url;
        } catch (cause) {
            error = uploadError;
            root.querySelectorAll<HTMLInputElement>(
                'input[type="file"]',
            ).forEach((input) => (input.value = ''));

            throw cause;
        }
    }

    export function currentMarkdown(): string {
        return mode === 'visual' && editor ? editor.getMarkdown() : value;
    }

    function insertBlock(
        kind: 'callout:info' | 'callout:warning' | 'embed',
    ): void {
        if (!editor) {
            return;
        }

        if (kind === 'embed') {
            editor.editor.action((ctx) => {
                bookmark = ctx.get(editorViewCtx).state.selection.getBookmark();
            });
            embedUrl = '';
            embedError = '';
            embedOpen = true;

            return;
        } else {
            editor.editor.action(
                insert(
                    `\n\n\`\`\`${kind}\nTulis catatan Anda di sini.\n\`\`\`\n`,
                ),
            );
        }

        value = editor.getMarkdown();
        error = '';
        editor.editor.action((ctx) => ctx.get(editorViewCtx).focus());
    }

    function showMenu(): void {
        editor?.editor.action((ctx) => {
            const view = ctx.get(editorViewCtx);
            const { $from: selectionFrom } = view.state.selection;

            if (
                selectionFrom.parent.content.size ||
                !selectionFrom.parent.isTextblock
            ) {
                const position = selectionFrom.depth
                    ? selectionFrom.after(1)
                    : view.state.doc.content.size;
                const transaction = view.state.tr.insert(
                    position,
                    view.state.schema.nodes.paragraph.create(),
                );
                transaction.setSelection(
                    TextSelection.create(transaction.doc, position + 1),
                );
                view.dispatch(transaction);
            }

            view.focus();
            (ctx.get('menuAPICtx') as { show: (pos: number) => void }).show(
                view.state.selection.from,
            );
        });
    }

    function addEmbed(event: SubmitEvent): void {
        event.preventDefault();

        try {
            const parsed = new URL(embedUrl.trim());

            if (
                parsed.protocol !== 'https:' ||
                parsed.username ||
                parsed.password ||
                /\s/.test(embedUrl.trim())
            ) {
                throw new Error();
            }
        } catch {
            embedError =
                'Masukkan satu URL HTTPS yang valid, tanpa kredensial.';

            return;
        }

        editor?.editor.action((ctx) => {
            const view = ctx.get(editorViewCtx);

            if (bookmark) {
                view.dispatch(
                    view.state.tr.setSelection(
                        bookmark.resolve(view.state.doc),
                    ),
                );
            }
        });
        editor?.editor.action(
            insert(`\n\n\`\`\`embed\n${embedUrl.trim()}\n\`\`\`\n`),
        );

        if (editor) {
            value = editor.getMarkdown();
        }

        embedOpen = false;
        error = '';
    }

    async function changeMode(next: 'visual' | 'markdown'): Promise<void> {
        if (mode === next) {
            return;
        }

        if (next === 'visual') {
            if (!editor) {
                return;
            }

            try {
                syncing = true;
                editor.editor.action(replaceAll(value));
                value = editor.getMarkdown();
                error = '';
            } catch {
                error =
                    'Markdown tidak dapat dibuka di editor visual. Isi Anda tetap tersedia; periksa Markdown lalu coba lagi.';

                return;
            } finally {
                syncing = false;
            }
        } else if (editor) {
            value = editor.getMarkdown();
        }

        mode = next;
        await tick();

        if (next === 'visual') {
            editor?.editor.action((ctx) => ctx.get(editorViewCtx).focus());
        } else {
            root.parentElement
                ?.querySelector<HTMLTextAreaElement>('textarea')
                ?.focus();
        }
    }

    onMount(() => {
        let active = true;
        const instance = new Crepe({
            root,
            defaultValue: value,
            featureConfigs: {
                placeholder: {
                    text: 'Mulai menulis, atau ketik / untuk menambah blok…',
                },
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
                    buildMenu: (builder) => {
                        const group = builder.addGroup('article', 'Artikel');

                        for (const kind of [
                            'callout:info',
                            'callout:warning',
                            'embed',
                        ] as const) {
                            group.addItem(kind, {
                                label:
                                    kind === 'callout:info'
                                        ? 'Catatan'
                                        : kind === 'callout:warning'
                                          ? 'Peringatan'
                                          : 'Sematan',
                                icon: icons[kind],
                                onRun: (ctx) => {
                                    ctx.get(commandsCtx).call(
                                        clearTextInCurrentBlockCommand.key,
                                    );
                                    insertBlock(kind);
                                },
                            });
                        }
                    },
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
        instance.editor
            .config(configureBlogImages)
            .use([...blogBlockRemark, calloutNode, embedNode]);
        function updateImageHelp(selection: Selection | undefined): void {
            showImageHelp =
                selection instanceof NodeSelection &&
                selection.node.type.name === 'image-block' &&
                !selection.node.attrs.src;
        }

        instance.on((listener) => {
            listener.updated((ctx) =>
                updateImageHelp(ctx.get(editorViewCtx).state?.selection),
            );
            listener.selectionUpdated((_ctx, selection) =>
                updateImageHelp(selection),
            );
            listener.markdownUpdated((_ctx, markdown) => {
                if (!syncing && mode === 'visual') {
                    value = markdown;
                }
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
            .catch((cause: unknown) => {
                console.error('Article editor initialization failed', cause);

                if (active) {
                    error = 'Editor tidak dapat dimulai.';
                    mode = 'markdown';
                }
            });

        return () => {
            active = false;
            void instance.destroy();
        };
    });
</script>

<div class="article-editor">
    <div class="article-editor-tools">
        <div class="flex gap-1" role="group" aria-label="Mode editor">
            <Button
                variant={mode === 'visual' ? 'secondary' : 'ghost'}
                aria-pressed={mode === 'visual'}
                disabled={!editor}
                onclick={() => changeMode('visual')}>Visual</Button
            >
            <Button
                variant={mode === 'markdown' ? 'secondary' : 'ghost'}
                aria-pressed={mode === 'markdown'}
                disabled={!editor && !error}
                onclick={() => changeMode('markdown')}>Markdown</Button
            >
        </div>
        {#if mode === 'visual'}<Button
                variant="ghost"
                disabled={!editor}
                onpointerdown={(event: PointerEvent) => event.preventDefault()}
                onclick={showMenu}
                ><Plus class="size-4" aria-hidden="true" />Tambah blok</Button
            >{/if}
    </div>
    {#if mode === 'visual' && showImageHelp}
        <p class="cms-help py-2">
            JPEG, PNG, WebP · Maks. 10 MB / 12 MP · Tanpa animasi
        </p>
    {/if}
    {#if !editor && !error}<div
            class="min-h-48 animate-pulse py-8 text-sm text-muted-foreground"
            role="status"
        >
            Menyiapkan editor…
        </div>{/if}
    <div
        bind:this={root}
        class="blog-editor"
        hidden={mode === 'markdown'}
    ></div>
    {#if mode === 'markdown'}<textarea
            id="post-markdown"
            aria-label="Konten Markdown"
            aria-invalid={invalid}
            aria-describedby={errorId}
            class="article-source"
            spellcheck="false"
            bind:value
        ></textarea>{/if}
    {#if error}<p class="text-sm text-destructive" role="alert">{error}</p>{/if}
</div>
<Dialog bind:open={embedOpen}>
    <DialogContent
        onCloseAutoFocus={(event) => {
            event.preventDefault();
            editor?.editor.action((ctx) => ctx.get(editorViewCtx).focus());
        }}
    >
        <DialogTitle>Tambah sematan</DialogTitle>
        <DialogDescription
            >Tempel tautan video YouTube atau halaman HTTPS untuk ditampilkan
            sebagai kartu tautan.</DialogDescription
        >
        <form class="mt-5 space-y-4" onsubmit={addEmbed}>
            <div class="space-y-2">
                <Label for="embed-url">URL sematan</Label><Input
                    id="embed-url"
                    type="url"
                    required
                    bind:value={embedUrl}
                    aria-invalid={Boolean(embedError)}
                    aria-describedby="embed-url-error"
                    placeholder="https://…"
                />{#if embedError}<p
                        id="embed-url-error"
                        class="text-sm text-destructive"
                        role="alert"
                    >
                        {embedError}
                    </p>{/if}
            </div>
            <div class="flex justify-end gap-2">
                <Button variant="outline" onclick={() => (embedOpen = false)}
                    >Batal</Button
                ><Button type="submit">Tambahkan</Button>
            </div>
        </form>
    </DialogContent>
</Dialog>
