import type { Node as MarkdownNode } from '@milkdown/kit/transformer';
import { $node, $remark } from '@milkdown/kit/utils';

type BlogMarkdownNode = MarkdownNode & {
    lang?: string;
    value?: string;
    children?: BlogMarkdownNode[];
};

function transformBlogBlocks(node: BlogMarkdownNode): void {
    if (node.type === 'code') {
        if (['callout:info', 'callout:warning'].includes(String(node.lang))) {
            node.type = 'blog_callout';
        } else if (node.lang === 'embed') {
            node.type = 'blog_embed';
        }
    }

    node.children?.forEach(transformBlogBlocks);
}

export const blogBlockRemark = $remark(
    'blogBlockRemark',
    () => () => (tree: MarkdownNode) =>
        transformBlogBlocks(tree as BlogMarkdownNode),
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

export const calloutNode = $node('blog_callout', () => ({
    content: 'text*',
    group: 'block',
    defining: true,
    priority: 100,
    attrs: { variant: { default: 'info' } },
    parseDOM: [
        {
            tag: 'aside[data-blog-callout]',
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
            { contenteditable: 'false' },
            node.attrs.variant === 'warning' ? 'Warning' : 'Note',
        ],
        ['div', 0],
    ],
    parseMarkdown: {
        match: (node) =>
            node.type === 'blog_callout' &&
            ['callout:info', 'callout:warning'].includes(String(node.lang)),
        runner: (state, node, type) => {
            state.openNode(type, { variant: String(node.lang).split(':')[1] });

            if (node.value) {
                state.addText(String(node.value));
            }

            state.closeNode();
        },
    },
    toMarkdown: {
        match: (node) => node.type.name === 'blog_callout',
        runner: (state, node) =>
            state.addNode('code', undefined, node.textContent, {
                lang: `callout:${node.attrs.variant}`,
            }),
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
