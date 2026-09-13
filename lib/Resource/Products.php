<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Products extends AbstractResource
{
    protected string $path = '/products/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('products.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('products.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('products.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('products.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('products.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function archive(string $id): array
    {
        $this->http->assertSecretKey('products.archive');
        return $this->http->post("{$this->path}{$id}/archive/");
    }
    public function restore(string $id): array
    {
        $this->http->assertSecretKey('products.restore');
        return $this->http->post("{$this->path}{$id}/restore/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
