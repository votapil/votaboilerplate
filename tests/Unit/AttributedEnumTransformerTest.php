<?php

use App\Enums\PermissionName;
use App\Support\AttributedEnumTransformer;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Tests\Support\Enums\UnpublishedEnum;

/**
 * The generator is pointed at the whole of app/, so the only thing keeping an
 * enum private is the #[TypeScript] attribute. The package's own EnumTransformer
 * does not check for it and publishes every enum it is handed.
 *
 * Two different mistakes are guarded here, because they fail differently: the
 * gate itself breaking, and the gate being correct but unplugged.
 */
function transformEnum(string $class): Transformed|Untransformable
{
    $node = PhpClassNode::fromClassString($class);

    return (new AttributedEnumTransformer)->transform($node, TransformationContext::createFromPhpClass($node));
}

test('an enum without #[TypeScript] never reaches the frontend', function () {
    expect(transformEnum(UnpublishedEnum::class))->toBeInstanceOf(Untransformable::class);
});

test('an enum carrying #[TypeScript] still does', function () {
    expect(transformEnum(PermissionName::class))->toBeInstanceOf(Transformed::class);
});

test('the generator is wired to the gated transformer, not the stock one', function () {
    // Testing the class alone would not catch the likelier regression: someone
    // re-runs typescript:install, or copies the documented stub, and the
    // ungated EnumTransformer lands back in the provider. The gate would still
    // be correct and would simply never run.
    $transformers = collect(app(TypeScriptTransformerConfig::class)->transformers)
        ->map(fn ($transformer) => is_string($transformer) ? $transformer : $transformer::class);

    expect($transformers)->toContain(AttributedEnumTransformer::class);

    $stock = $transformers->first(
        fn (string $class) => $class === EnumTransformer::class
    );

    expect($stock)->toBeNull('the ungated EnumTransformer is registered — every enum under app/ is being published');
});

test('generated types land somewhere TypeScript actually looks', function () {
    // webapp/types/ is outside every tsconfig Nuxt generates, so a file written
    // there compiles for nobody and the whole pipeline is decorative.
    expect(app(TypeScriptTransformerConfig::class)->outputDirectory)
        ->toStartWith(base_path('webapp/app'));
});
