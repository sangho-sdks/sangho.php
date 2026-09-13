<?php
declare(strict_types=1);
namespace Sangho\Resource;
class Transactions extends AbstractResource
{
    protected string $path = '/transactions/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('transactions.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('transactions.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('transactions.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function cancel(string $id): array
    {
        $this->http->assertSecretKey('transactions.cancel');
        return $this->http->post("{$this->path}{$id}/cancel/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
