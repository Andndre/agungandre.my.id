import { imageBlockSchema } from '@milkdown/kit/component/image-block';
import { remarkCtx } from '@milkdown/kit/core';
import type { Ctx } from '@milkdown/kit/ctx';
import type { Node as MarkdownNode } from '@milkdown/kit/transformer';
import { SerializerState } from '@milkdown/kit/transformer';
import { $node, $remark } from '@milkdown/kit/utils';

type BlogMarkdownNode = MarkdownNode & {
    lang?: string;
    value?: string;
    children?: BlogMarkdownNode[];
};

export function configureBlogImages(ctx: Ctx): void {
    ctx.update(imageBlockSchema.key, (previous) => (context) => {
        const schema = previous(context);

        return {
            ...schema,
            attrs: {
                ...schema.attrs,
                alt: { default: '', validate: 'string' },
            },
            parseMarkdown: {
                ...schema.parseMarkdown,
                runner: (state, node, type) => {
                    const alt = String(node.alt ?? '');
                    const ratio = Number(alt);
                    state.addNode(type, {
                        src: String(node.url ?? ''),
                        caption: String(node.title ?? ''),
                        alt,
                        ratio: Number.isFinite(ratio) && ratio > 0 ? ratio : 1,
                    });
                },
            },
            toMarkdown: {
                ...schema.toMarkdown,
                runner: (state, node) => {
                    state.openNode('paragraph');
                    state.addNode('image', undefined, undefined, {
                        title: node.attrs.caption || undefined,
                        url: node.attrs.src,
                        alt: node.attrs.alt || node.attrs.caption,
                    });
                    state.closeNode();
                },
            },
        };
    });
}

function transformBlogBlocks(
    node: BlogMarkdownNode,
    parse: (markdown: string) => MarkdownNode,
): void {
    if (node.type === 'code') {
        if (['callout:info', 'callout:warning'].includes(String(node.lang))) {
            node.type = 'blog_callout';
            node.children =
                (parse(String(node.value ?? '')) as BlogMarkdownNode)
                    .children ?? [];

            if (!node.children.length) {
                node.children = [{ type: 'paragraph', children: [] }];
            }
        } else if (node.lang === 'embed') {
            node.type = 'blog_embed';
        }
    }

    node.children?.forEach((child) => transformBlogBlocks(child, parse));
}

export const blogBlockRemark = $remark(
    'blogBlockRemark',
    (ctx) => () => (tree: MarkdownNode) =>
        transformBlogBlocks(
            tree as BlogMarkdownNode,
            (markdown) => ctx.get(remarkCtx).parse(markdown) as MarkdownNode,
        ),
);

const youtubeEmbed = (value: string): string | null => {
    try {
        const url = new URL(value.trim());

        if (url.protocol !== 'https:') {
            return null;
        }

        let id: string | null = null;

        if (
            ['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(
                url.hostname,
            ) &&
            url.pathname === '/watch'
        ) {
            id = url.searchParams.get('v');
        } else if (url.hostname === 'youtu.be') {
            id = url.pathname.slice(1);
        }

        return id && /^[A-Za-z0-9_-]{11}$/.test(id)
            ? `https://www.youtube-nocookie.com/embed/${id}`
            : null;
    } catch {
        return null;
    }
};

export const calloutNode = $node('blog_callout', (ctx) => ({
    content: 'block+',
    group: 'block',
    defining: true,
    priority: 100,
    attrs: { variant: { default: 'info' } },
    parseDOM: [
        {
            tag: 'aside[data-blog-callout]',
            contentElement: '[data-callout-content]',
            getAttrs: (dom) => ({
                variant: (dom as HTMLElement).dataset.blogCallout,
            }),
        },
    ],
    toDOM: (node) => [
        'aside',
        {
            class: `blog-callout blog-callout-${node.attrs.variant}`,
            'data-blog-callout': node.attrs.variant,
        },
        [
            'strong',
            { contenteditable: 'false', class: 'blog-callout-label' },
            node.attrs.variant === 'warning' ? 'Peringatan' : 'Catatan',
        ],
        ['div', { 'data-callout-content': '' }, 0],
    ],
    parseMarkdown: {
        match: (node) =>
            node.type === 'blog_callout' &&
            ['callout:info', 'callout:warning'].includes(String(node.lang)),
        runner: (state, node, type) => {
            state.openNode(type, { variant: String(node.lang).split(':')[1] });

            state.next(node.children);
            state.closeNode();
        },
    },
    toMarkdown: {
        match: (node) => node.type.name === 'blog_callout',
        runner: (state, node) =>
            state.addNode(
                'code',
                undefined,
                new SerializerState(node.type.schema)
                    .run(node.type.schema.nodes.doc.create(null, node.content))
                    .toString(ctx.get(remarkCtx))
                    .trimEnd(),
                {
                    lang: `callout:${node.attrs.variant}`,
                },
            ),
    },
}));

export const embedNode = $node('blog_embed', () => ({
    group: 'block',
    atom: true,
    defining: true,
    priority: 100,
    attrs: { url: { default: '' } },
    parseDOM: [
        {
            tag: 'div[data-blog-embed]',
            getAttrs: (dom) => ({
                url: (dom as HTMLElement).dataset.blogEmbed,
            }),
        },
    ],
    toDOM: (node) => {
        const value = String(node.attrs.url).trim();
        const video = youtubeEmbed(value);

        if (video) {
            return [
                'div',
                { class: 'blog-video', 'data-blog-embed': value },
                [
                    'iframe',
                    {
                        src: video,
                        title: 'YouTube video',
                        loading: 'lazy',
                        allowfullscreen: 'true',
                    },
                ],
            ];
        }

        try {
            const url = new URL(value);

            if (url.protocol === 'https:' && !url.username && !url.password) {
                return [
                    'div',
                    { 'data-blog-embed': value },
                    [
                        'a',
                        {
                            class: 'blog-link-card',
                            href: value,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                        },
                        `${url.hostname} ↗`,
                    ],
                ];
            }
        } catch {
            // An invalid URL is displayed as text in the editor.
        }

        return [
            'div',
            { class: 'blog-embed-error', 'data-blog-embed': value },
            'Invalid embed URL',
        ];
    },
    parseMarkdown: {
        match: (node) => node.type === 'blog_embed',
        runner: (state, node, type) =>
            state.addNode(type, { url: String(node.value ?? '').trim() }),
    },
    toMarkdown: {
        match: (node) => node.type.name === 'blog_embed',
        runner: (state, node) =>
            state.addNode('code', undefined, String(node.attrs.url), {
                lang: 'embed',
            }),
    },
}));
