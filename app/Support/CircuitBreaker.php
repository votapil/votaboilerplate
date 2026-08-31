<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * A fuse for calls to somebody else's server.
 *
 * The failure this exists to prevent is not "a request failed" — it is the storm that
 * follows. A third-party host stops answering but keeps connections open; every queued
 * job retries; each retry holds a socket and a DNS lookup; the machine runs out of
 * connection-tracking entries and its resolver dies. The site went down that way, and
 * the external API was never the part that mattered — the retries were.
 *
 * After `threshold` failures inside `window` seconds the breaker opens and callers skip
 * the target for `cooldown` seconds. State lives in the cache, so every worker shares one
 * verdict instead of each discovering the outage on its own.
 *
 * Rules that go with it, none of which this class can enforce for you:
 *   - every outbound call sets Http::connectTimeout() and timeout();
 *   - jobs calling third parties run on their own queue with a low maxProcesses, so a
 *     stuck lane cannot starve the rest;
 *   - retries are few and bounded (tries + retryUntil), never "keep trying".
 *
 * Usage:
 *
 *     $breaker = CircuitBreaker::fromConfig('payments', 'services.payments.circuit_breaker');
 *
 *     if ($breaker->isOpen()) {
 *         $this->release($breaker->cooldownSeconds());   // park the job, do not hammer
 *         return;
 *     }
 *
 *     $result = $breaker->call(fn () => Http::connectTimeout(2)->timeout(10)->get($url));
 */
class CircuitBreaker
{
    public function __construct(
        private readonly string $name,
        private readonly bool $enabled = true,
        private readonly int $threshold = 3,
        private readonly int $window = 120,
        private readonly int $cooldown = 300,
    ) {}

    /**
     * Build from a config block of the shape
     * ['enabled' => bool, 'threshold' => int, 'window' => int, 'cooldown' => int].
     * Missing keys fall back to the defaults above, so the block is optional.
     */
    public static function fromConfig(string $name, string $configKey): self
    {
        return new self(
            name: $name,
            enabled: (bool) config("{$configKey}.enabled", true),
            threshold: (int) config("{$configKey}.threshold", 3),
            window: (int) config("{$configKey}.window", 120),
            cooldown: (int) config("{$configKey}.cooldown", 300),
        );
    }

    /** True while the target is in cooldown after repeated failures. */
    public function isOpen(): bool
    {
        return $this->enabled && Cache::has($this->openKey());
    }

    /**
     * Run the callback through the breaker: a throw counts as a failure, a return counts
     * as a success. Callers that need to park a job instead of throwing should check
     * isOpen() first and use recordSuccess()/recordFailure() by hand.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function call(Closure $callback): mixed
    {
        try {
            $result = $callback();
        } catch (\Throwable $e) {
            $this->recordFailure();

            throw $e;
        }

        $this->recordSuccess();

        return $result;
    }

    /** A clean response means the target is healthy again — forget the failures. */
    public function recordSuccess(): void
    {
        if ($this->enabled) {
            Cache::forget($this->failKey());
        }
    }

    /** Count a failure and open the breaker once the threshold is reached. */
    public function recordFailure(): void
    {
        if (! $this->enabled) {
            return;
        }

        $failures = (int) Cache::get($this->failKey(), 0) + 1;

        if ($failures < $this->threshold) {
            // The counter expires on its own: failures spread over hours are not an outage.
            Cache::put($this->failKey(), $failures, $this->window);

            return;
        }

        Cache::put($this->openKey(), true, $this->cooldown);
        Cache::forget($this->failKey());

        Log::warning("Circuit breaker opened for {$this->name}", [
            'cooldown_seconds' => $this->cooldown,
            'threshold' => $this->threshold,
        ]);
    }

    /** How long to stay away — the right amount of time to release a job for. */
    public function cooldownSeconds(): int
    {
        return $this->cooldown;
    }

    private function openKey(): string
    {
        return "circuit:{$this->name}";
    }

    private function failKey(): string
    {
        return "circuit:{$this->name}:failures";
    }
}
