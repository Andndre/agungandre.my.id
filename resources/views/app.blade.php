<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (() => {
            let preference = {{ Illuminate\Support\Js::from($appearance ?? 'system') }};
            try {
                const stored = localStorage.getItem('appearance');
                if (['light', 'dark', 'system'].includes(stored)) preference = stored;
            } catch (_) {}
            const dark = preference === 'dark' || (preference === 'system' && window.matchMedia(
                '(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            document.documentElement.dataset.appearance = preference;
        })();
    </script>

    <link rel="icon" href="/favicon.ico" sizes="any">

    @php
        $pageComponent = $page['component'] ?? '';
        $publicModules = [];

        if (in_array($pageComponent, ['Welcome', 'blog/Index', 'blog/Show', 'projects/Show'], true)
            && ! Illuminate\Support\Facades\Vite::isRunningHot()
            && Illuminate\Support\Facades\Vite::manifestHash()) {
            foreach (["resources/js/pages/{$pageComponent}.svelte", 'resources/js/layouts/PublicLayout.svelte'] as $module) {
                try {
                    $moduleUrl = Illuminate\Support\Facades\Vite::asset($module);
                    if ($moduleUrl) {
                        $publicModules[] = $moduleUrl;
                    }
                } catch (Illuminate\Foundation\ViteException) {
                    // Optional hints must not prevent rendering when a chunk is unavailable.
                }
            }
        }

        $heroCover = null;
        if ($pageComponent === 'Welcome') {
            $projects = collect(data_get($page, 'props.projects', []));
            $heroProject = $projects->firstWhere('is_featured', true) ?? $projects->first();
            $heroCover = data_get($heroProject, 'cover_image_url');
        }
    @endphp

    @if ($heroCover)
        <link rel="preload" as="image" href="{{ $heroCover }}" fetchpriority="high">
    @endif
    @foreach ($publicModules as $moduleUrl)
        <link rel="modulepreload" href="{{ $moduleUrl }}" crossorigin>
    @endforeach

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <x-inertia::head>
        <title>{{ config('app.name', 'Laravel') }}</title>
    </x-inertia::head>
</head>

<body class="font-sans antialiased">
    <x-inertia::app />
</body>

</html>
