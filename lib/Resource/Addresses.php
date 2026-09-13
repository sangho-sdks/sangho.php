<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Addresses extends AbstractResource
{
    protected string $path = '/addresses/';

    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('addresses.list');
        return $this->http->get($this->path, $c);
    }

    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('addresses.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }

    public function create(array $p): array
    {
        $this->http->assertSecretKey('addresses.create');
        return $this->http->post($this->path, $p);
    }

    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('addresses.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }

    public function delete(string $id): void
    {
        $this->http->assertSecretKey('addresses.delete');
        $this->http->delete("{$this->path}{$id}/");
    }

    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
