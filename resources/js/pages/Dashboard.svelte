<script module lang="ts">
    import { dashboard as adminDashboard } from '@/routes/admin/index';
    import { index as adminProjectsIndex, create as adminProjectsCreate, edit as adminProjectsEdit } from '@/routes/admin/projects';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Admin',
                href: adminDashboard(),
            },
            {
                title: 'Dashboard',
                href: adminDashboard(),
            },
        ],
    };
</script>

<script lang="ts">
    import {
        FolderGit2,
        Globe,
        FileText,
        Plus,
        Pencil,
        ArrowRight,
    } from 'lucide-svelte/icons';
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardAction } from '@/components/ui/card';

    let {
        stats = { total: 0, featured: 0, published: 0, drafts: 0 },
        recentProjects = [],
    }: {
        stats?: {
            total: number;
            featured: number;
            published: number;
            drafts: number;
        };
        recentProjects?: Array<{
            id: number;
            title: string;
            is_featured: boolean;
            is_published: boolean;
            created_at: string;
        }>;
    } = $props();

    function getStatusBadge(project: { is_featured: boolean; is_published: boolean }) {
        if (project.is_featured) {
            return { label: 'Featured', variant: 'default' as const };
        }

        if (project.is_published) {
            return { label: 'Published', variant: 'secondary' as const };
        }

        return { label: 'Draft', variant: 'outline' as const };
    }

    function formatDate(dateStr: string) {
        return new Date(dateStr).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }
</script>

<AppHead title="Dashboard" />

<div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
    <!-- Stats Row -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <span class="text-sm font-medium">Total Projects</span>
                <FolderGit2 class="text-muted-foreground size-4" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{stats.total}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <span class="text-sm font-medium">Featured</span>
                <span class="size-4 text-yellow-500">★</span>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{stats.featured}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <span class="text-sm font-medium">Published</span>
                <Globe class="text-muted-foreground size-4" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{stats.published}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <span class="text-sm font-medium">Drafts</span>
                <FileText class="text-muted-foreground size-4" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{stats.drafts}</div>
            </CardContent>
        </Card>
    </div>

    <!-- Recent Projects -->
    <Card>
        <CardHeader class="flex flex-row items-center justify-between">
            <span class="text-base font-semibold">Recent Projects</span>
            <CardAction>
                <Button variant="ghost" size="sm" asChild>
                    <a href={adminProjectsIndex().url} class="inline-flex items-center gap-2">
                        View all
                        <ArrowRight class="size-3" />
                    </a>
                </Button>
            </CardAction>
        </CardHeader>
        <CardContent>
            {#if recentProjects.length === 0}
                <p class="text-muted-foreground py-6 text-center text-sm">No projects yet.</p>
            {:else}
                <div class="divide-border divide-y border-y">
                    {#each recentProjects as project (project.id)}
                        {@const badge = getStatusBadge(project)}
                        <div class="flex items-center justify-between py-3">
                            <span class="font-medium">{project.title}</span>
                            <div class="flex items-center gap-3">
                                <Badge variant={badge.variant}>{badge.label}</Badge>
                                <span class="text-muted-foreground text-sm">
                                    {formatDate(project.created_at)}
                                </span>
                                <Button variant="ghost" size="icon" asChild>
                                    <a href={adminProjectsEdit({ project: project.id }).url}>
                                        <Pencil class="size-4" />
                                    </a>
                                </Button>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </CardContent>
    </Card>

    <!-- Quick Actions -->
    <div class="flex gap-3">
        <Button asChild>
            <a href={adminProjectsCreate().url} class="inline-flex items-center gap-2">
                <Plus class="size-4" />
                Add Project
            </a>
        </Button>
        <Button variant="secondary" asChild>
            <a href={adminProjectsIndex().url} class="inline-flex items-center gap-2">
                Manage Projects
                <ArrowRight class="size-4" />
            </a>
        </Button>
    </div>
</div>