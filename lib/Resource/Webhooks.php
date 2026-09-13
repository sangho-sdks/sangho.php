<?php

declare(strict_types=1);

namespace Sangho\Resource;

use Sangho\Exception\SanghoException;

class Webhooks extends AbstractResource
{
    protected string $path = '/webhooks/';
    public function list(array $c = []): array
    {
        $this->http->assertSecretKey('webhooks.list');
        return $this->http->get($this->path, $c);
    }
    public function retrieve(string $id): array
    {
        $this->http->assertSecretKey('webhooks.retrieve');
        return $this->http->get("{$this->path}{$id}/");
    }
    public function create(array $p): array
    {
        $this->http->assertSecretKey('webhooks.create');
        return $this->http->post($this->path, $p);
    }
    public function update(string $id, array $p): array
    {
        $this->http->assertSecretKey('webhooks.update');
        return $this->http->patch("{$this->path}{$id}/", $p);
    }
    public function delete(string $id): void
    {
        $this->http->assertSecretKey('webhooks.delete');
        $this->http->delete("{$this->path}{$id}/");
    }
    public function rollSecret(string $id): array
    {
        $this->http->assertSecretKey('webhooks.rollSecret');
        return $this->http->post("{$this->path}{$id}/roll-secret/");
    }
    public function disable(string $id): array
    {
        $this->http->assertSecretKey('webhooks.disable');
        return $this->http->post("{$this->path}{$id}/disable/");
    }
    public function enable(string $id): array
    {
        $this->http->assertSecretKey('webhooks.enable');
        return $this->http->post("{$this->path}{$id}/enable/");
    }
    public function sendTestEvent(string $id, string $eventType): array
    {
        $this->http->assertSecretKey('webhooks.sendTestEvent');
        return $this->http->post("{$this->path}{$id}/test/", ['event_type' => $eventType]);
    }
    public function listDeliveries(string $id, array $c = []): array
    {
        $this->http->assertSecretKey('webhooks.listDeliveries');
        return $this->http->get("{$this->path}{$id}/deliveries/", $c);
    }
    public function retrieveDelivery(string $id, string $deliveryId): array
    {
        $this->http->assertSecretKey('webhooks.retrieveDelivery');
        return $this->http->get("{$this->path}{$id}/deliveries/{$deliveryId}/");
    }
    public function retryDelivery(string $id, string $deliveryId): array
    {
        $this->http->assertSecretKey('webhooks.retryDelivery');
        return $this->http->post("{$this->path}{$id}/deliveries/{$deliveryId}/retry/");
    }
    public function options(): array
    {
        return $this->http->options($this->path);
    }
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string $secret,
        int $tolerance = 300
    ): array {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $p) {
            [$k, $v] = explode('=', $p, 2) + [null, null];
            $parts[$k] = $v;
        }
        if (!($parts['t'] ?? null) || !($parts['v1'] ?? null)) {
            throw new SanghoException('Invalid Sangho-Signature header.', 'invalid_signature');
        }
        if (abs(time() - (int) $parts['t']) > $tolerance) {
            throw new SanghoException('Webhook timestamp too old.', 'stale_event');
        }
        $expected = hash_hmac('sha256', "{$parts['t']}.{$payload}", $secret);
        if (!hash_equals($expected, $parts['v1'])) {
            throw new SanghoException('Webhook signature mismatch.', 'invalid_signature');
        }
        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
}
