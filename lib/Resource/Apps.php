<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Apps extends AbstractResource
{
    protected string $path = '/apps/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('apps.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('apps.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('apps.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('apps.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('apps.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function rollSecret(string $id): array
    {
        $this->http->assertSecretKey('apps.rollSecret');
        return $this->http->post("{$this->path}{$id}/roll-secret/");
    }
    public function keys(string $id): array
    {
        $this->http->assertSecretKey('apps.keys');
        return $this->http->get("{$this->path}{$id}/keys/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
