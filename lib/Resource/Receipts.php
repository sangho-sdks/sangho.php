<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Receipts extends AbstractResource
{
    protected string $path = '/receipts/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('receipts.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('receipts.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function send(string $id, ?string $email = null): array
    {
        $this->http->assertSecretKey('receipts.send');
        $b = $email ? ['email' => $email] : [];
        return $this->http->post("{$this->path}{$id}/send/", $b);
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
