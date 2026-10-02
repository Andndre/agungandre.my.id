<script lang="ts">
    import { Crepe } from '@milkdown/crepe';
    import { insert } from '@milkdown/kit/utils';
    import { onMount } from 'svelte';
    import { blogBlockRemark, calloutNode, embedNode } from '@/lib/blog-blocks';
    import { store as uploadImage } from '@/routes/admin/posts/images';
    import '@milkdown/crepe/theme/common/style.css';
    import '@milkdown/crepe/theme/frame.css';

    let { value = $bindable('') }: { value?: string } = $props();
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
                'Image upload failed. Check the file type and storage settings.',
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
                'HTTPS URL for YouTube video or link card',
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
                error = 'Enter a valid HTTPS URL.';

                return;
            }

            editor.editor.action(
                insert(`\n\n\`\`\`embed\n${url.trim()}\n\`\`\`\n`),
            );
        } else {
            editor.editor.action(
                insert(`\n\n\`\`\`${kind}\nWrite your note here.\n\`\`\`\n`),
            );
        }

        value = editor.getMarkdown();
        error = '';
    }

    onMount(() => {
        const instance = new Crepe({
            root,
            defaultValue: value,
            featureConfigs: { 'image-block': { onUpload: upload } },
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
                editor = instance;
            })
            .catch(() => {
                error = 'Editor could not start.';
            });

        return () => {
            void instance.destroy();
        };
    });
</script>

<div class="space-y-3">
    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            class="rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
            onclick={() => insertBlock('callout:info')}>+ Note</button
        >
        <button
            type="button"
            class="rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
            onclick={() => insertBlock('callout:warning')}>+ Warning</button
        >
        <button
            type="button"
            class="rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
            onclick={() => insertBlock('embed')}>+ Embed</button
        >
    </div>
    <div
        bind:this={root}
        class="blog-editor min-h-96 overflow-hidden rounded-xl border bg-background"
    ></div>
    {#if error}<p class="text-sm text-destructive" role="alert">{error}</p>{/if}
</div>
