<?php

namespace App\Support;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;

/**
 * EnumTransformer, but only for enums that asked for it.
 *
 * The package's EnumTransformer transforms every enum handed to it — it checks
 * that the class IS an enum and never that anyone wanted it published. Combined
 * with transformDirectories(app_path()) that means the first internal status or
 * state enum somebody adds ships its cases to the frontend, with no diff in the
 * webapp to review and no error anywhere.
 *
 * AttributedClassTransformer cannot cover this: it extends ClassTransformer and
 * emits object shapes, so on its own an attributed enum produces nothing at all.
 */
class AttributedEnumTransformer extends EnumTransformer
{
    public function transform(
        PhpClassNode $phpClassNode,
        TransformationContext $context
    ): Transformed|Untransformable {
        if (count($phpClassNode->getAttributes(TypeScript::class)) === 0) {
            return Untransformable::create();
        }

        return parent::transform($phpClassNode, $context);
    }
}
