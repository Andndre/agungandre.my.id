@props(['title', 'description'])

@php
    $links = [
        ['label' => 'Work', 'href' => route('home').'#work', 'documentNavigation' => true],
        ['label' => 'About', 'href' => route('home').'#about', 'documentNavigation' => true],
        ['label' => 'Writing', 'href' => route('blog.index'), 'documentNavigation' => true],
        ['label' => 'Contact', 'href' => route('home').'#contact', 'documentNavigation' => true],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" @class(['dark' => ($appearance ?? 'system') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - Agung Andre</title>
    <meta name="description" content="{{ $description }}">
    @include('partials.appearance')
    <link rel="icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/public-blog.ts'])
</head>
<body class="font-sans antialiased">
    <a href="#main-content" class="fixed top-2 left-2 z-50 -translate-y-24 rounded-lg bg-primary p-3 text-primary-foreground focus:translate-y-0">Skip to content</a>
    <header class="studio-header">
        <div class="studio-shell studio-nav">
            <a href="{{ route('home') }}" class="wordmark" aria-label="Andre — home"><span class="wordmark-symbol" aria-hidden="true">a.</span>andre<span class="text-primary">.</span></a>
            <nav class="hidden items-center gap-7 text-sm md:flex" aria-label="Main navigation">
                @foreach ($links as $link)
                    <a href="{{ $link['href'] }}" class="inline-flex min-h-11 items-center text-muted-foreground hover:text-foreground">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div id="blog-controls" hidden data-links="{{ json_encode($links, JSON_THROW_ON_ERROR) }}"></div>
            <details id="blog-navigation-fallback" class="relative md:hidden">
                <summary class="theme-option cursor-pointer list-none [&::-webkit-details-marker]:hidden" aria-label="Open navigation">
                    <x-public-icon name="menu" class="size-5" />
                </summary>
                <nav class="absolute top-full right-0 mt-3 flex min-w-48 flex-col gap-2 rounded-xl border border-border bg-popover p-4 text-popover-foreground shadow-xl" aria-label="Mobile navigation">
                    @foreach ($links as $link)
                        <a href="{{ $link['href'] }}" class="studio-link">{{ $link['label'] }}<x-public-icon /></a>
                    @endforeach
                </nav>
            </details>
        </div>
    </header>
    <main id="main-content" tabindex="-1">{{ $slot }}</main>
    <footer class="studio-shell studio-footer">
        <span>© {{ now()->year }} Anak Agung Gede Andre Kusuma</span>
        <div class="flex flex-wrap gap-5">
            <a href="https://github.com/Andndre" class="hover:text-primary">GitHub <x-public-icon /></a>
            <a href="https://linkedin.com/in/andndre" class="hover:text-primary">LinkedIn <x-public-icon /></a>
            @auth
                <a href="{{ route('admin.dashboard') }}" class="hover:text-primary">Manage portfolio <x-public-icon /></a>
            @endauth
        </div>
    </footer>
</body>
</html>
