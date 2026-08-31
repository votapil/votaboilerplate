<?php

namespace App\Exceptions;

use Exception;
use InvalidArgumentException;

/**
 * A business rule said no, and the user needs to hear why.
 *
 * It carries a TRANSLATION KEY, never a translated sentence: ApiExceptionRenderer
 * performs the lookup exactly once, at render time, in the caller's locale.
 *
 * Translating at the throw site is the mistake this class is shaped to prevent.
 * `__()` returns its own input when it misses, so `new BusinessException(__('errors.x'))`
 * looks like it works while the key, its placeholders and the user's language are all
 * gone for good — the same message ships to every locale. The constructor guard below
 * turns that into a loud failure everywhere except production.
 *
 * Usage:
 *     throw new BusinessException('errors.operation_failed');
 *     throw new BusinessException('errors.quota_exceeded', 409, ['limit' => 5]);
 */
class BusinessException extends Exception
{
    /**
     * What a translation key looks like: dotted, no spaces. A translated sentence
     * ("You are out of quota.") cannot match it.
     */
    private const KEY_PATTERN = '/^[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)+$/';

    /**
     * @param  string  $messageKey  Translation key, e.g. 'errors.operation_failed'.
     * @param  int  $statusCode  HTTP status the renderer answers with.
     * @param  array<string, mixed>  $messageParams  Placeholders for the translation.
     */
    public function __construct(
        public readonly string $messageKey,
        public readonly int $statusCode = 422,
        public readonly array $messageParams = [],
    ) {
        if (! preg_match(self::KEY_PATTERN, $messageKey) && ! app()->isProduction()) {
            throw new InvalidArgumentException(
                "BusinessException expects a translation key such as 'errors.operation_failed', got: {$messageKey}"
            );
        }

        // The parent message stays UNtranslated on purpose: logs and alerts then show one
        // stable string per error class instead of N localized variants of the same thing.
        parent::__construct($messageKey, $statusCode);
    }
}
