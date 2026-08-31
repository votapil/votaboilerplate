<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Monolog handler that relays a log record to a Telegram chat.
 *
 * Two guards make the difference between an alert channel people read and one they mute:
 *
 * 1. The noise filter, which lives in App\Exceptions\ApiExceptionRenderer::isNoise() —
 *    scanner 404s and validation failures never reach this handler at all.
 * 2. The rate limit below. A cascade (a dependency going down takes every request with
 *    it) produces hundreds of identical alerts a minute; past ~20 the channel stops
 *    being information. The cap is per worker process and holds no state anywhere else
 *    ON PURPOSE: the outage that floods this handler is usually the cache/Redis outage,
 *    so a cache-backed counter would fail open exactly when it is needed.
 *
 * Sending is best-effort: a broken alerting path must never turn one failure into two.
 */
class TelegramLogHandler extends AbstractProcessingHandler
{
    /** Alerts per minute, per worker process. */
    private const RATE_LIMIT = 20;

    /** Telegram rejects messages over 4096 characters; leave room for the truncation notice. */
    private const MAX_MESSAGE_LENGTH = 4000;

    private const MAX_TRACE_LENGTH = 1500;

    /** Context keys rendered by hand below; anything else is appended as key: value. */
    private const KNOWN_CONTEXT_KEYS = ['exception', 'file', 'line', 'url', 'method', 'user_id', 'trace'];

    /** @var list<int> Unix timestamps of the alerts sent inside the current window. */
    private array $sentAt = [];

    public function __construct(
        private readonly string $botToken,
        private readonly string $chatId,
        Level $level = Level::Error,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if ($this->botToken === '' || $this->chatId === '') {
            return;
        }

        if ($this->isRateLimited()) {
            return;
        }

        try {
            // Hard connect timeout: a hung host must not hold a worker (or a queued job)
            // for as long as it feels like — that is how one dead endpoint takes a fleet
            // down. Same rule applies to every outbound call in the app.
            Http::connectTimeout(2)
                ->timeout(5)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $this->chatId,
                    'text' => $this->format($record),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            $this->sentAt[] = time();
        } catch (Throwable) {
            // Best effort. Logging must not raise.
        }
    }

    private function format(LogRecord $record): string
    {
        $context = $record->context;

        // Laravel's own logger passes the Throwable itself under 'exception'; callers that
        // build the context by hand pass a class name. Accept both, and fall back to the
        // exception for file/line/trace so a plain Log::error($msg, ['exception' => $e])
        // arrives complete.
        $exception = $context['exception'] ?? null;
        $exceptionClass = match (true) {
            $exception instanceof Throwable => $exception::class,
            is_string($exception) => $exception,
            default => null,
        };
        $file = $context['file'] ?? ($exception instanceof Throwable ? $exception->getFile() : null);
        $line = $context['line'] ?? ($exception instanceof Throwable ? $exception->getLine() : null);
        $trace = $context['trace'] ?? ($exception instanceof Throwable ? $exception->getTraceAsString() : null);

        $lines = [
            sprintf('%s <b>%s</b> — %s', $this->emoji($record->level), $record->level->name, config('app.env')),
            '',
        ];

        if ($exceptionClass !== null) {
            $lines[] = '📍 <code>'.$this->escape($exceptionClass).'</code>';
        }

        $lines[] = '💬 '.$this->escape($this->clamp($record->message, 500));

        if (is_string($file)) {
            $where = str_replace(base_path().'/', '', $file);
            $lines[] = '📁 <code>'.$this->escape($where).($line !== null ? ':'.$line : '').'</code>';
        }

        if (is_string($context['url'] ?? null)) {
            $lines[] = '🌐 '.$this->escape(trim(($context['method'] ?? '').' '.$context['url']));
        }

        if (! empty($context['user_id'])) {
            $lines[] = '👤 user #'.$this->escape((string) $context['user_id']);
        }

        // Whatever else the caller passed. Without this, context is silently dropped and
        // people learn to stop attaching it.
        foreach ($context as $key => $value) {
            if (in_array($key, self::KNOWN_CONTEXT_KEYS, true) || ! is_scalar($value)) {
                continue;
            }

            $lines[] = '• '.$this->escape($key.': '.$this->clamp((string) $value, 300));
        }

        if (is_string($trace) && $trace !== '') {
            $lines[] = '';
            $lines[] = '📋 <b>Stack trace</b>';
            $lines[] = '<pre>'.$this->escape($this->truncateTrace($trace)).'</pre>';
        }

        $text = implode("\n", $lines);

        return mb_strlen($text) > self::MAX_MESSAGE_LENGTH
            ? mb_substr($text, 0, self::MAX_MESSAGE_LENGTH).'… (truncated)'
            : $text;
    }

    private function emoji(Level $level): string
    {
        return match ($level) {
            Level::Emergency, Level::Alert => '💀',
            Level::Critical => '🔥',
            Level::Error => '🔴',
            Level::Warning => '🟠',
            default => 'ℹ️',
        };
    }

    /** Keep the top frames — the bottom of a trace is framework plumbing. */
    private function truncateTrace(string $trace): string
    {
        $kept = [];
        $length = 0;

        foreach (explode("\n", $trace) as $index => $line) {
            $length += mb_strlen($line);

            if ($length > self::MAX_TRACE_LENGTH) {
                $kept[] = '… ('.(substr_count($trace, "\n") + 1 - $index).' more frames)';
                break;
            }

            $kept[] = $line;
        }

        return implode("\n", $kept);
    }

    private function clamp(string $value, int $limit): string
    {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit).'…' : $value;
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function isRateLimited(): bool
    {
        $windowStart = time() - 60;

        $this->sentAt = array_values(array_filter($this->sentAt, static fn (int $ts): bool => $ts > $windowStart));

        return count($this->sentAt) >= self::RATE_LIMIT;
    }
}
