<script module lang="ts">
    import { edit as editAppearance } from '@/routes/appearance';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Pengaturan tampilan',
                href: editAppearance(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
</script>

<AppHead title="Pengaturan tampilan" />

<h1 class="sr-only">Pengaturan tampilan</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Pengaturan tampilan"
        description="Update your account's appearance settings"
    />
    <AppearanceTabs />
</div>
