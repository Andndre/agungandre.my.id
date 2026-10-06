<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.appearance')

    <link rel="icon" href="/favicon.ico" sizes="any">

    @php
        $pageComponent = $page['component'] ?? '';
        $publicModules = [];

        if (in_array($pageComponent, ['Welcome', 'projects/Show'], true)
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

    @include('partials.asset-load-recovery')

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <x-inertia::head>
        <title>{{ config('app.name', 'Laravel') }}</title>
    </x-inertia::head>
</head>

<body class="font-sans antialiased">
    <aside id="asset-load-recovery" hidden data-page-component="{{ $page['component'] ?? '' }}" aria-label="{{ str_starts_with($page['component'] ?? '', 'auth/') || str_starts_with($page['component'] ?? '', 'admin/') || str_starts_with($page['component'] ?? '', 'settings/') ? 'Pemulihan halaman' : 'Page recovery' }}">
        <div class="asset-recovery-panel">
            <div role="alert" aria-atomic="true">
                <h2 id="asset-recovery-title"></h2>
                <p id="asset-recovery-message"></p>
                <p id="asset-recovery-unsaved" hidden></p>
            </div>
            <div class="asset-recovery-actions">
                <button type="button" id="asset-recovery-reload" aria-describedby="asset-recovery-title asset-recovery-message asset-recovery-unsaved"></button>
                <button type="button" id="asset-recovery-close"></button>
            </div>
        </div>
    </aside>
    <x-inertia::app />
</body>

</html>
