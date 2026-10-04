export type PublicProjectSummary = {
    id: number;
    title: string;
    slug: string;
    description: string;
    cover_image_url: string | null;
    tech_stack: string[];
    live_url: string | null;
    repo_url: string | null;
    is_featured: boolean;
};
export type PublicProjectDetail = PublicProjectSummary & {
    gallery_urls: string[];
};
export type AdminProject = PublicProjectDetail & {
    cover_image: string | null;
    images: string[] | null;
    is_published: boolean;
    sort_order: number;
    created_at: string;
};
