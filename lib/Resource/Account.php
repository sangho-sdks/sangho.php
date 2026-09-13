<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Account extends AbstractResource
{
    protected string $path = '/account/';

    /**
     * Renvoie l'App associée à la clé secrète utilisée pour cette requête.
     * Équivalent à apps->retrieve(id) sans avoir besoin de connaître l'id à
     * l'avance — pratique comme "qui suis-je" / health-check.
     */
    public function retrieve(): array
    {
        $this->http->assertSecretKey('account.retrieve');
        return $this->http->get($this->path);
    }
}
