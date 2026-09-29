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
    /** URL signée et expirante du PDF : `['url' => ..., 'expires_at' => ...]`. */
    public function getPdfUrl(string $id): array
    {
        $this->http->assertSecretKey('receipts.getPdfUrl');
        return $this->http->get("{$this->path}{$id}/pdf/");
    }

    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
