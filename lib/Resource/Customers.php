<?php

declare(strict_types=1);

namespace Sangho\Resource;

class Customers extends AbstractResource
{
    protected string $path = '/customers/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('customers.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('customers.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('customers.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('customers.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('customers.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function listTransactions(string $id, array $c = []): array
    {
        $this->http->assertSecretKey('customers.listTransactions');
        return $this->http->get("{$this->path}{$id}/transactions/", $c);
    }
    public function listPaymentMethods(string $id): array
    {
        $this->http->assertSecretKey('customers.listPaymentMethods');
        return $this->http->get("{$this->path}{$id}/payment-methods/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
