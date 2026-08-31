<?php

/*
|--------------------------------------------------------------------------
| Error messages
|--------------------------------------------------------------------------
|
| Read by App\Exceptions\ApiExceptionRenderer and by every BusinessException
| the app throws. Each key here is the localized half of one API failure; its
| machine-readable half is the key itself, which the renderer returns as
| `code` (errors.not_found -> "not_found").
|
| FAILURES ONLY. Success messages ("Profile updated", "Signed out") do not
| belong in this file — they end up here because it is the first translation
| file a project gets, and then nobody can tell an error catalogue from a
| grab bag of every string in the product. Put them in lang/{locale}/messages.php
| or next to the feature that emits them.
|
| Keep lang/en/errors.php and lang/ru/errors.php key-for-key identical.
|
*/

return [

    // HTTP-level failures, emitted by the central renderer.
    'unauthenticated' => 'Please sign in to continue.',
    'forbidden' => 'You do not have permission to perform this action.',
    'not_found' => 'The requested resource was not found.',
    'too_many_requests' => 'Too many requests. Please wait a moment and try again.',
    'server_error' => 'Something went wrong on our side. The team has been notified.',

    // Business rule failures: throw new BusinessException('errors.operation_failed').
    // Add one key per rule the user can actually hit, with a message that says what
    // to do next rather than what the code did.
    'operation_failed' => 'The operation could not be completed. Please try again.',

];
