<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ExternalLink from 'lucide-svelte/icons/external-link';
    import FileText from 'lucide-svelte/icons/file-text';
    import FolderGit2 from 'lucide-svelte/icons/folder-git-2';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Settings from 'lucide-svelte/icons/settings';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import { home } from '@/routes';
    import admin from '@/routes/admin';
    import { dashboard } from '@/routes/admin/index';
    import { edit } from '@/routes/profile';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const mainNavItems: NavItem[] = [
        {
            title: 'Ringkasan',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    const adminNavItems: NavItem[] = [
        {
            title: 'Proyek',
            href: admin.projects.index(),
            icon: FolderGit2,
        },
        {
            title: 'Pengaturan',
            href: edit(),
            icon: Settings,
        },
    ];

    const canManagePosts = $derived(
        Boolean(
            (page.props.auth as { canManagePosts?: boolean }).canManagePosts,
        ),
    );
    const postNavItems: NavItem[] = [
        { title: 'Tulisan', href: admin.posts.index(), icon: FileText },
    ];

    const footerNavItems: NavItem[] = [
        { title: 'Buka portfolio', href: home(), icon: ExternalLink },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={toUrl(dashboard())}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
        <NavMain items={adminNavItems} label="Konten" />
        {#if canManagePosts}<NavMain
                items={postNavItems}
                label="Publikasi"
            />{/if}
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
