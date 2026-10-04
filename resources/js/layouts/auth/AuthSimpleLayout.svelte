<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowLeft from 'lucide-svelte/icons/arrow-left';
    import type { Snippet } from 'svelte';
    import ThemePicker from '@/components/ThemePicker.svelte';
    import { home } from '@/routes';
    let {
        title = '',
        description = '',
        children,
    }: { title?: string; description?: string; children?: Snippet } = $props();
</script>

<div class="auth-shell">
    <div class="auth-art-side">
        <Link href={home()} class="studio-wordmark"
            >agung andre<span aria-hidden="true">.</span></Link
        >
        <div>
            <p class="section-kicker">Portfolio / Studio Engineering</p>
            <h2
                class="mt-6 text-5xl leading-tight font-semibold tracking-tight"
            >
                Ide yang jelas.<br />Sistem yang solid.
            </h2>
            <p class="mt-6 max-w-sm text-lg text-muted-foreground">
                Ruang kerja untuk merawat karya, berbagi pemikiran, dan
                membangun langkah berikutnya.
            </p>
            <div
                class="mt-12 rounded-[28px] border border-border bg-background/60 p-8"
            >
                <p class="font-mono text-xs text-primary">01 / INTERFACE</p>
                <div class="my-4 h-px bg-border"></div>
                <p class="font-mono text-xs text-primary">02 / APPLICATION</p>
                <div class="my-4 h-px bg-border"></div>
                <p class="font-mono text-xs text-primary">03 / DATA</p>
            </div>
        </div>
        <p class="text-sm text-muted-foreground">
            Agung Andre · Full-stack developer
        </p>
    </div>
    <div class="auth-form-side">
        <div class="mb-12 flex flex-wrap items-center justify-between gap-4">
            <Link href={home()} class="studio-wordmark"
                >agung andre<span aria-hidden="true">.</span></Link
            ><ThemePicker language="id" />
        </div>
        <div class="mx-auto w-full max-w-sm">
            <p class="section-kicker">Ruang kerja pribadi</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">{title}</h1>
            <p class="mt-3 mb-8 text-sm leading-6 text-muted-foreground">
                {description}
            </p>
            {@render children?.()}
        </div>
        <Link
            class="mt-12 inline-flex min-h-11 items-center gap-2 self-start text-sm text-muted-foreground hover:text-primary"
            href={home()}
            ><ArrowLeft class="size-4" aria-hidden="true" />Kembali ke portfolio</Link
        >
    </div>
</div>
