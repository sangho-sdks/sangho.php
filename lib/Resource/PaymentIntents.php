<?php

declare(strict_types=1);

namespace Sangho\Resource;

class PaymentIntents extends AbstractResource
{
    protected string $path = '/payment-intents/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('paymentIntents.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('paymentIntents.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('paymentIntents.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('paymentIntents.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function confirm(string $id, array $p = []): array
    {
        $this->http->assertSecretKey('paymentIntents.confirm');
        return $this->http->post("{$this->path}{$id}/confirm/", $p);
    }
    public function capture(string $id, array $p = []): array
    {
        $this->http->assertSecretKey('paymentIntents.capture');
        return $this->http->post("{$this->path}{$id}/capture/", $p);
    }
    public function cancel(string $id, array $p = []): array
    {
        $this->http->assertSecretKey('paymentIntents.cancel');
        return $this->http->post("{$this->path}{$id}/cancel/", $p);
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
