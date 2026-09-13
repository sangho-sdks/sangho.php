<?php
declare(strict_types=1);
namespace Sangho\Resource;
class PaymentLinks extends AbstractResource
{
    protected string $path = '/payment-links/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('paymentLinks.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('paymentLinks.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('paymentLinks.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('paymentLinks.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('paymentLinks.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function archive(string $id): array
    {
        $this->http->assertSecretKey('paymentLinks.archive');
        return $this->http->post("{$this->path}{$id}/archive/");
    }
    public function restore(string $id): array
    {
        $this->http->assertSecretKey('paymentLinks.restore');
        return $this->http->post("{$this->path}{$id}/restore/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
