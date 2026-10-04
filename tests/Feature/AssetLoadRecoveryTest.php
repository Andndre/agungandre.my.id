<?php

use function Pest\Laravel\get;

test('initial HTML includes independent asset recovery outside the Inertia mount', function (string $routeName, string $component, string $label) {
    $this->withoutVite();

    get(route($routeName))->assertSuccessful()
        ->assertSeeInOrder(['vite:preloadError', 'id="asset-load-recovery"', 'id="app"'], false)
        ->assertSee('hidden data-page-component="'.$component.'" aria-label="'.$label.'"', false)
        ->assertSee('role="alert" aria-atomic="true"', false)
        ->assertSee('id="asset-recovery-reload"', false)
        ->assertSee('id="asset-recovery-close"', false);
})->with([
    'login' => ['login', 'auth/Login', 'Pemulihan halaman'],
    'portfolio' => ['home', 'Welcome', 'Page recovery'],
]);

test('Inertia responses keep recovery markup in the existing document', function () {
    get(route('login'))->assertSuccessful();

    get(route('login'), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => Inertia\Inertia::getVersion(),
    ])->assertSuccessful()
        ->assertJsonPath('component', 'auth/Login')
        ->assertDontSee('id="asset-load-recovery"', false);
});
