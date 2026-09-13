<?php
declare(strict_types=1);
namespace Sangho\Resource;
class CheckoutSessions extends AbstractResource
{
    protected string $path = '/checkout-sessions/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('checkoutSessions.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        // Le backend autorise explicitement la clé publique sur cette action
        // (page de confirmation côté navigateur) — ne pas la bloquer ici.
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('checkoutSessions.create');
        return $this->http->post($this->path, $p);
    }
    public function expire(string $id): array
    {
        $this->http->assertSecretKey('checkoutSessions.expire');
        return $this->http->post("{$this->path}{$id}/expire/");
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('checkoutSessions.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
