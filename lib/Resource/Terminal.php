<?php

declare(strict_types=1);

namespace Sangho\Resource;

use Sangho\HttpClient;
use Sangho\Resource\Terminal\{Offline, Readers, Sessions};

/**
 * $client->terminal->readers->*   — gestion des lecteurs de carte physiques
 * $client->terminal->sessions->*  — sessions de paiement in-person
 * $client->terminal->offline->*   — synchronisation des transactions hors-ligne
 */
class Terminal
{
    public readonly Readers $readers;
    public readonly Sessions $sessions;
    public readonly Offline $offline;

    public function __construct(HttpClient $http)
    {
        $this->readers = new Readers($http);
        $this->sessions = new Sessions($http);
        $this->offline = new Offline($http);
    }
}
