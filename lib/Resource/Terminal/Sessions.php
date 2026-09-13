<?php

declare(strict_types=1);

namespace Sangho\Resource\Terminal;

use Sangho\HttpClient;

class Sessions
{
    private string $path = '/terminal/sessions/';

    public function __construct(private readonly HttpClient $http)
    {
    }

    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('terminal.sessions.list');
        return $this->http->get($this->path, $c);
    }

    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('terminal.sessions.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }

    public function create(array $p): array
    {
        $this->http->assertSecretKey('terminal.sessions.create');
        return $this->http->post($this->path, $p);
    }

    public function presentPaymentMethod(string $id, array $p = []): array
    {
        $this->http->assertSecretKey('terminal.sessions.presentPaymentMethod');
        return $this->http->post("{$this->path}{$id}/present-payment-method/", $p);
    }

    public function pollStatus(string $id): array
    {
        $this->http->assertSecretKey('terminal.sessions.pollStatus');
        return $this->http->get("{$this->path}{$id}/status/");
    }

    public function cancel(string $id): array
    {
        $this->http->assertSecretKey('terminal.sessions.cancel');
        return $this->http->post("{$this->path}{$id}/cancel/");
    }

    public function options(): array
    {
        return $this->http->options($this->path);
    }
}
