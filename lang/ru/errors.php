<?php

/*
|--------------------------------------------------------------------------
| Error messages (Russian)
|--------------------------------------------------------------------------
|
| Mirror of lang/en/errors.php — same keys, same order. See that file for what
| belongs here (failures only) and what does not (success messages).
|
*/

return [

    // HTTP-level failures, emitted by the central renderer.
    'unauthenticated' => 'Войдите, чтобы продолжить.',
    'forbidden' => 'У вас нет прав на это действие.',
    'not_found' => 'Запрашиваемый ресурс не найден.',
    'too_many_requests' => 'Слишком много запросов. Подождите немного и попробуйте снова.',
    'server_error' => 'Что-то пошло не так на нашей стороне. Команда уже знает о проблеме.',

    // Business rule failures: throw new BusinessException('errors.operation_failed').
    'operation_failed' => 'Не удалось выполнить операцию. Попробуйте ещё раз.',

];
