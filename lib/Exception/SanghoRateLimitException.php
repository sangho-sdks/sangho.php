<?php

declare(strict_types=1);

namespace Sangho\Exception;

class SanghoRateLimitException extends SanghoException
{
    public function __construct(public readonly int $retryAfter = 60, array $raw = [])
    {
        $message = (is_string($raw['message'] ?? null))
            ? $raw['message']
            : "Rate limit exceeded. Retry after {$retryAfter}s.";

        parent::__construct($message, 'rate_limit_exceeded', 429, $raw, type: 'RATE_LIMIT_ERROR');
    }
}
