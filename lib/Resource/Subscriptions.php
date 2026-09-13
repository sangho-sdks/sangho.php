<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Subscriptions extends AbstractResource
{
    protected string $path = '/subscriptions/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('subscriptions.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('subscriptions.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('subscriptions.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('subscriptions.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function cancel(string $id, array $p = []): array
    {
        $this->http->assertSecretKey('subscriptions.cancel');
        return $this->http->post("{$this->path}{$id}/cancel/", $p);
    }
    public function pause(string $id): array
    {
        $this->http->assertSecretKey('subscriptions.pause');
        return $this->http->post("{$this->path}{$id}/pause/");
    }
    public function resume(string $id): array
    {
        $this->http->assertSecretKey('subscriptions.resume');
        return $this->http->post("{$this->path}{$id}/resume/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
