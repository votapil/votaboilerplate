<?php

namespace Tests\Support\Enums;

/**
 * Stand-in for "any enum that is nobody's business outside PHP" — an order
 * state, an internal status, a feature flag. Deliberately carries no
 * #[TypeScript] attribute: its whole job is to be a class the TypeScript
 * generator must refuse, so that AttributedEnumTransformerTest has something
 * real to point at without inventing a fake in the test body.
 */
enum UnpublishedEnum: string
{
    case Internal = 'internal-only';
}
