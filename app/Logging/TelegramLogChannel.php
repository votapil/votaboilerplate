<?php

namespace App\Logging;

use Monolog\Level;
use Monolog\Logger;

/**
 * Factory for the 'telegram' log channel (see config/logging.php).
 *
 * This is the whole alerting stack, and deliberately so: a bot chat that pings a phone
 * costs nothing, needs no server of its own, and gets read. A self-hosted metrics stack
 * was built for the project this template comes from, ran on the same machine that kept
 * failing, and was never looked at during an incident.
 *
 * Credentials come from config/services.php ('telegram_error_bot'). With either value
 * missing the handler silently does nothing, which is what local and CI runs want — no
 * environment checks needed at the call sites.
 *
 * Usage: add 'telegram' to LOG_STACK (e.g. LOG_STACK=stderr,telegram) so anything logged
 * at error level or above is relayed; App\Exceptions\ApiExceptionRenderer also writes to
 * the channel directly for unhandled exceptions.
 */
class TelegramLogChannel
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Logger
    {
        $handler = new TelegramLogHandler(
            botToken: (string) config('services.telegram_error_bot.token'),
            chatId: (string) config('services.telegram_error_bot.chat_id'),
            level: Level::fromName($config['level'] ?? 'error'),
        );

        return new Logger('telegram', [$handler]);
    }
}
