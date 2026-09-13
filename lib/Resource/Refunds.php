<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Refunds extends AbstractResource
{
    protected string $path = '/refunds/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('refunds.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('refunds.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('refunds.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('refunds.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function cancel(string $id): array
    {
        $this->http->assertSecretKey('refunds.cancel');
        return $this->http->post("{$this->path}{$id}/cancel/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
