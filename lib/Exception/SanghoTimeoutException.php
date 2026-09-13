<?php

declare(strict_types=1);

namespace Sangho\Exception;

/**
 * Requête expirée (timeout). Même raisonnement que SanghoNetworkException.
 */
class SanghoTimeoutException extends SanghoException
{
    public function __construct(float $timeout)
    {
        parent::__construct("Request timed out after {$timeout}s.", 'timeout_error', 0, [], type: 'TIMEOUT_ERROR');
    }
}
