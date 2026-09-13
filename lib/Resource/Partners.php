<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Partners extends AbstractResource
{
    protected string $path = '/partners/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('partners.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('partners.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('partners.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('partners.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('partners.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
