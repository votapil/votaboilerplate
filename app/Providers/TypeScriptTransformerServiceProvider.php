<?php

namespace App\Providers;

use App\Support\AttributedEnumTransformer;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

/**
 * What crosses the PHP/TypeScript border, and where it lands.
 *
 * v3 of the package dropped config/typescript-transformer.php entirely — the
 * settings live here now, which is why this file exists at all.
 *
 * Scope is deliberately narrow. Only classes carrying #[TypeScript] are
 * emitted, and today that is exactly one: App\Enums\PermissionName. DTOs stay
 * hand-written on the frontend, in the store that owns them — see
 * webapp/CLAUDE.md for why the opposite rule was tried and abandoned.
 */
class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            // The stub registers the package's own EnumTransformer. It is replaced
            // here because it publishes EVERY enum under app_path() — it checks that
            // a class is an enum and never that anyone asked for it — so the first
            // internal status enum would leak its cases to the frontend silently.
            // See App\Support\AttributedEnumTransformer; the attributed-class
            // transformer cannot stand in for it, as it emits object shapes and
            // returns nothing for an enum.
            ->transformer(AttributedClassTransformer::class)
            ->transformer(AttributedEnumTransformer::class)
            ->transformDirectories(app_path())

            // app/types, not types: the base provider points at
            // resource_path('js/generated') which this project does not have, and
            // webapp/types is outside every tsconfig Nuxt generates — a file written
            // there compiles for nobody. app/types is covered by `app/**/*` and
            // already holds page-meta.d.ts.
            ->outputDirectory(base_path('webapp/app/types'))
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))

            // A timestamp arrives over the wire as an ISO string, never as a Carbon.
            // Without these the generated type claims an object the JSON never holds.
            ->replaceType(\DateTime::class, 'string')
            ->replaceType(\DateTimeImmutable::class, 'string')
            ->replaceType(CarbonInterface::class, 'string')
            ->replaceType(CarbonImmutable::class, 'string')
            ->replaceType(Carbon::class, 'string');

        // No formatter on purpose. PrettierFormatter (the stub's default) shells out
        // to a node binary that does not exist in the PHP container, so the command
        // would die on a cosmetic step.
    }
}
