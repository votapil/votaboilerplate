<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

/**
 * The SPA catch-all in routes/web.php must never shadow a backend web route.
 *
 * This is the collision every "Laravel API + SPA served from public/" project
 * walks into: a `/{any}` fallback swallows the admin panel, its assets and its
 * Livewire endpoints, regardless of registration order — and the panel starts
 * answering with the SPA shell, which then fails with a 500 nobody can explain.
 *
 * The exclusion list is a regex, so it is also easy to break subtly: an entry
 * without an anchor makes `/uploads` match the exclusion for `up`.
 */
test('the admin panel is not shadowed by the SPA fallback', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('the admin login page is served by Filament, not the SPA', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('wire:', false)  // raw Livewire attribute
        ->assertDontSee('__nuxt');   // present if the SPA shell were served instead
});

test('the SPA fallback excludes Livewire on whatever prefix it mounted', function () {
    // The prefix comes from Livewire itself, not from a literal: it is derived
    // from APP_KEY (/livewire-<8 hex>), and hunting the route table for something
    // starting with "livewire" would bake in the very assumption under test.
    //
    // Asserted against the route object rather than over HTTP on purpose. Whether
    // the panel survives an actual request depends on which of the two registered
    // first, and the whole reason the exclusion list exists is that this order
    // must not matter — so a passing HTTP call would prove luck, not the barrier.
    $prefix = EndpointResolver::prefix();
    $spa = Route::getRoutes()->getByName('spa');

    foreach (['/livewire.js', '/update', '/upload-file'] as $path) {
        $uri = $prefix.$path;

        expect($spa->matches(Request::create($uri, 'GET'), includingMethod: false))->toBeFalse(
            "the SPA fallback matches [{$uri}] and would serve the shell instead of Livewire"
        );
    }
});

test('a path that merely starts like an excluded prefix still reaches the SPA', function () {
    // `/up` is excluded; `/upload` must not be. Without an anchored regex the SPA
    // silently loses every route whose name begins with one of the keywords.
    $this->get('/upload')->assertOk();
});

test('the admin host serves the panel and nothing else', function () {
    config(['app.admin_domain' => 'admin.example.test']);

    $this->get('http://admin.example.test/')->assertRedirect('/admin');
    $this->get('http://admin.example.test/some/spa/path')->assertRedirect('/admin');
});

test('an ordinary host still serves the SPA from the root', function () {
    config(['app.admin_domain' => 'admin.example.test']);

    $this->get('http://app.example.test/')->assertOk();
});
