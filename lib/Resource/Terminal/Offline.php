<?php

declare(strict_types=1);

namespace Sangho\Resource\Terminal;

use Sangho\HttpClient;

class Offline
{
    private string $path = '/terminal/offline/sync/';

    public function __construct(private readonly HttpClient $http)
    {
    }

    public function sync(array $p): array
    {
        $this->http->assertSecretKey('terminal.offline.sync');
        return $this->http->post($this->path, $p);
    }

    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('terminal.offline.list');
        return $this->http->get($this->path, $c);
    }

    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
