<?php

declare(strict_types=1);

namespace Sangho\Resource;

class PaymentMethods extends AbstractResource
{
    protected string $path = '/payment-methods/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('paymentMethods.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('paymentMethods.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function setDefault(string $id): array
    {
        $this->http->assertSecretKey('paymentMethods.setDefault');
        return $this->http->post("{$this->path}{$id}/set-default/");
    }
    public function attach(string $id, string $customerId): array
    {
        $this->http->assertSecretKey('paymentMethods.attach');
        return $this->http->post("{$this->path}{$id}/attach/", ['customer' => $customerId]);
    }
    public function detach(string $id): array
    {
        $this->http->assertSecretKey('paymentMethods.detach');
        return $this->http->post("{$this->path}{$id}/detach/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
