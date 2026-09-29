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
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
