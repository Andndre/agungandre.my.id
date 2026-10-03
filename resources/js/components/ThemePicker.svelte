<script lang="ts">
    import Monitor from 'lucide-svelte/icons/monitor';
    import Moon from 'lucide-svelte/icons/moon';
    import Sun from 'lucide-svelte/icons/sun';
    import { themeState } from '@/lib/theme.svelte';
    let { language = 'en' }: { language?: 'en' | 'id' } = $props();
    const { appearance, updateAppearance } = themeState();
    const options = [
        {
            value: 'light' as const,
            Icon: Sun,
            en: 'Light theme',
            id: 'Tema terang',
        },
        {
            value: 'dark' as const,
            Icon: Moon,
            en: 'Dark theme',
            id: 'Tema gelap',
        },
        {
            value: 'system' as const,
            Icon: Monitor,
            en: 'System theme',
            id: 'Ikuti sistem',
        },
    ];
</script>

<div
    class="theme-picker"
    role="group"
    aria-label={language === 'id' ? 'Pilihan tema' : 'Color theme'}
>
    {#each options as { value, Icon, en, id } (value)}
        <button
            type="button"
            class="theme-option"
            aria-label={language === 'id' ? id : en}
            title={language === 'id' ? id : en}
            aria-pressed={appearance.value === value}
            onclick={() => updateAppearance(value)}
        >
            <Icon class="size-4" />
        </button>
    {/each}
</div>
