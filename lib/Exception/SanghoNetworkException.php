<?php

declare(strict_types=1);

namespace Sangho\Exception;

/**
 * Erreur réseau (aucune réponse du serveur). Catégorie propre au SDK — la
 * requête n'a jamais atteint le backend, donc pas de `raw`/`code` métier.
 */
class SanghoNetworkException extends SanghoException
{
    public function __construct(string $message = 'Network error. Please check your connection.')
    {
        parent::__construct($message, 'network_error', 0, [], type: 'NETWORK_ERROR');
    }
}
