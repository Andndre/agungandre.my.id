export type PostSummary = {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    reading_time: number;
    published_at: string;
};

export type AdminPost = Omit<PostSummary, 'published_at'> & {
    content: string;
    updated_at: string;
    published_at: string | null;
};

export type PostPage<T> = {
    data: T[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
