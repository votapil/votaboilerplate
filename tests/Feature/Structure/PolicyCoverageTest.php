<?php

use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;

/**
 * A Filament resource without a policy is not "unprotected pending review" — it is
 * open to every account that can reach the panel, because Filament falls back to
 * allowing the action when no policy answers. In the project this template comes
 * from that is exactly how editors and moderators ended up able to open the Users
 * resource and promote themselves to admin; it took a manual audit five months
 * after the resources were generated to find it.
 *
 * The generator scaffolds resources in bulk, so the guard has to be structural: a
 * new resource with no policy must fail the build, not wait for an audit.
 *
 * Until the first resource exists this test skips — it arms itself automatically.
 */
test('every Filament resource model has a policy', function () {
    $files = glob(app_path('Filament/Resources/*Resource.php')) ?: [];

    if ($files === []) {
        $this->markTestSkipped('No Filament resources yet — this guard arms itself with the first one.');
    }

    foreach ($files as $file) {
        /** @var class-string<Filament\Resources\Resource> $class */
        $class = 'App\\Filament\\Resources\\'.basename($file, '.php');
        $model = $class::getModel();

        expect(Gate::getPolicyFor($model))->not->toBeNull(
            "Filament resource {$class} exposes {$model} with no policy — anyone who can open the panel can edit it"
        );
    }
});
