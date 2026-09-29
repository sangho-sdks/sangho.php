<?php

declare(strict_types=1);

namespace Sangho\Resource;

use Sangho\Exception\SanghoException;
use Sangho\Exception\SanghoWebhookSignatureException;

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
    public function create(array $p, ?string $idempotencyKey = null): array
    {
        $this->http->assertSecretKey('webhooks.create');
        return $this->http->post($this->path, $p, $idempotencyKey);
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
    /**
     * Vérifie la signature HMAC-SHA256 et retourne l'événement (tableau).
     *
     * `Sangho-Signature: t=<ts>,v1=<hex>[,v1=<hex>…]` ; message signé "<ts>.<corps brut>". Plusieurs `v1` (et une liste
     * de secrets) sont acceptés pour la rotation ; comparaison à temps constant. Passez le corps BRUT reçu.
     *
     * @param string|list<string> $secret
     * @return array<string, mixed>
     * @throws SanghoWebhookSignatureException `reason` : malformed / expired / mismatch
     */
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string|array $secret,
        int $tolerance = 300
    ): array {
        [$timestamp, $signatures] = self::parseHeader($signatureHeader);

        if (abs(time() - $timestamp) > $tolerance) {
            throw new SanghoWebhookSignatureException(SanghoWebhookSignatureException::EXPIRED, 'Webhook timestamp too old.');
        }

        $matched = false;
        foreach ((array) $secret as $candidate) {
            $candidate = (string) $candidate;
            if ($candidate === '') {
                continue;
            }
            $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $candidate);
            foreach ($signatures as $received) {   // pas de court-circuit : temps indépendant du v1 correspondant
                if (hash_equals($expected, $received)) {
                    $matched = true;
                }
            }
        }
        if (!$matched) {
            throw new SanghoWebhookSignatureException(SanghoWebhookSignatureException::MISMATCH, 'Webhook signature mismatch.');
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new SanghoException('Webhook body is not valid JSON.', 'invalid_payload', 400);
        }
        return is_array($event) ? $event : [];
    }

    /**
     * Génère un en-tête `Sangho-Signature` valide pour tester votre endpoint.
     */
    public static function generateTestHeader(string $payload, string $secret, ?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        return "t={$ts},v1=" . hash_hmac('sha256', "{$ts}.{$payload}", $secret);
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    private static function parseHeader(string $header): array
    {
        $malformed = fn() => new SanghoWebhookSignatureException(
            SanghoWebhookSignatureException::MALFORMED,
            'Invalid Sangho-Signature header.'
        );
        if ($header === '') {
            throw $malformed();
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            if (!str_contains($part, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $part, 2));
            if ($key === 't') {
                if (!ctype_digit($value)) {
                    throw $malformed();
                }
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }
        if ($timestamp === null || $signatures === []) {
            throw $malformed();
        }
        return [$timestamp, $signatures];
    }
}
