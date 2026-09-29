<?php

declare(strict_types=1);

namespace Sangho\Exception;

/**
 * Signature de webhook refusée. `reason` : `malformed` (en-tête illisible, statut 400), `expired` (horodatage hors
 * tolérance, 400) ou `mismatch` (aucune signature ne correspond à un secret, 401).
 *
 * Sous-classe de `SanghoException` : les anciens `catch (SanghoException)` continuent de fonctionner et `errorCode`
 * garde les valeurs historiques (`invalid_signature`, `stale_event`).
 */
class SanghoWebhookSignatureException extends SanghoException
{
    public const MALFORMED = 'malformed';
    public const EXPIRED = 'expired';
    public const MISMATCH = 'mismatch';

    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct(
            $message,
            $reason === self::EXPIRED ? 'stale_event' : 'invalid_signature',
            $reason === self::MISMATCH ? 401 : 400,
            [],
            null,
            $reason === self::MISMATCH ? 'AUTHENTICATION_ERROR' : 'VALIDATION_ERROR',
        );
    }
}
