<?php

declare(strict_types=1);

namespace Sangho\Exception;

class SanghoException extends \RuntimeException
{
    private const KNOWN_TYPES = [
        'AUTHENTICATION_ERROR',
        'PERMISSION_ERROR',
        'NOT_FOUND_ERROR',
        'CONFLICT_ERROR',
        'VALIDATION_ERROR',
        'RATE_LIMIT_ERROR',
        'API_ERROR',
        'NETWORK_ERROR',
        'TIMEOUT_ERROR',
    ];

    public readonly string $errorCode;
    public readonly string $type;

    public function __construct(
        string $message = '',
        string $errorCode = 'api_error',
        public readonly int $statusCode = 0,
        public readonly array $raw = [],
        ?\Throwable $previous = null,
        string $type = 'API_ERROR',
    ) {
        parent::__construct($message, 0, $previous);

        // Le code métier précis renvoyé par le backend (raw['code']) prime
        // toujours sur le code par défaut de la sous-classe.
        $this->errorCode = (isset($raw['code']) && is_string($raw['code'])) ? $raw['code'] : $errorCode;

        $backendType = $raw['type'] ?? null;
        $this->type = (is_string($backendType) && in_array(strtoupper($backendType), self::KNOWN_TYPES, true))
            ? strtoupper($backendType)
            : $type;
    }
}
