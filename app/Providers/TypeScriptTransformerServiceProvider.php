<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
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
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(app_path())

            // The base provider points this at resource_path('js/generated'), which
            // this project does not have: the frontend is a separate Nuxt app.
            ->outputDirectory(base_path('webapp/types'))
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
