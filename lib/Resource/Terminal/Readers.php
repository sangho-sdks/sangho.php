<?php

declare(strict_types=1);

namespace Sangho\Resource\Terminal;

use Sangho\HttpClient;

class Readers
{
    private string $path = '/terminal/readers/';

    public function __construct(private readonly HttpClient $http)
    {
    }

    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('terminal.readers.list');
        return $this->http->get($this->path, $c);
    }

    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('terminal.readers.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }

    public function create(array $p): array
    {
        $this->http->assertSecretKey('terminal.readers.create');
        return $this->http->post($this->path, $p);
    }

    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('terminal.readers.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }

    /** Désactive un lecteur (soft-delete). */
    public function disable(string $id): void
    {
        $this->http->assertSecretKey('terminal.readers.disable');
        $this->http->delete("{$this->path}{$id}/");
    }

    public function refreshToken(string $id): array
    {
        $this->http->assertSecretKey('terminal.readers.refreshToken');
        return $this->http->post("{$this->path}{$id}/refresh-token/");
    }

    public function heartbeat(string $id): array
    {
        $this->http->assertSecretKey('terminal.readers.heartbeat');
        return $this->http->post("{$this->path}{$id}/heartbeat/");
    }

    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
