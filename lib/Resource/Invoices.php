<?php
declare(strict_types=1);
namespace Sangho\Resource;
class Invoices extends AbstractResource
{
    protected string $path = '/invoices/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('invoices.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('invoices.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('invoices.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('invoices.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('invoices.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function pay(string $id, array $p = []): array
    {
        $this->http->assertSecretKey('invoices.pay');
        return $this->http->post("{$this->path}{$id}/pay/", $p);
    }
    public function finalize(string $id): array
    {
        $this->http->assertSecretKey('invoices.finalize');
        return $this->http->post("{$this->path}{$id}/finalize/");
    }
    public function void(string $id): array
    {
        $this->http->assertSecretKey('invoices.void');
        return $this->http->post("{$this->path}{$id}/void/");
    }
    public function markUncollectible(string $id): array
    {
        $this->http->assertSecretKey('invoices.markUncollectible');
        return $this->http->post("{$this->path}{$id}/mark-uncollectible/");
    }
    public function send(string $id): array
    {
        $this->http->assertSecretKey('invoices.send');
        return $this->http->post("{$this->path}{$id}/send/");
    }
    public function getPdfUrl(string $id): array
    {
        $this->http->assertSecretKey('invoices.getPdfUrl');
        return $this->http->get("{$this->path}{$id}/pdf/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
