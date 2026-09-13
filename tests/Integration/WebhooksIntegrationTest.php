<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoException;
use Sangho\Exception\SanghoNotFoundException;
use Sangho\Resource\Webhooks;

/**
 * @group integration
 */
class WebhooksIntegrationTest extends IntegrationTestCase
{
    private static ?array $sharedWebhook = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$sharedWebhook = self::$client->webhooks->create([
            'url'    => 'https://webhook.site/' . bin2hex(random_bytes(8)),
            'events' => ['payment_intent.succeeded', 'customer.created'],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$sharedWebhook) {
            try {
                self::$client->webhooks->delete(self::$sharedWebhook['id']);
            } catch (\Throwable) {
            }
        }
    }

    public function testCreateWebhook(): void
    {
        $wh = self::$client->webhooks->create([
            'url'    => 'https://webhook.site/' . bin2hex(random_bytes(8)),
            'events' => ['payment_intent.succeeded'],
        ]);

        $this->assertNotEmpty($wh['id']);
        $this->assertContains('payment_intent.succeeded', $wh['events'] ?? []);

        self::$client->webhooks->delete($wh['id']);
    }

    public function testRetrieveWebhook(): void
    {
        $retrieved = self::$client->webhooks->retrieve(self::$sharedWebhook['id']);
        $this->assertSame(self::$sharedWebhook['id'], $retrieved['id']);
    }

    public function testListWebhooks(): void
    {
        $result = self::$client->webhooks->list();
        $this->assertArrayHasKey('results', $result);
    }

    public function testRollSecret(): void
    {
        $result = self::$client->webhooks->rollSecret(self::$sharedWebhook['id']);
        $this->assertSame(self::$sharedWebhook['id'], $result['id']);
    }

    public function testListDeliveries(): void
    {
        $result = self::$client->webhooks->listDeliveries(self::$sharedWebhook['id']);
        $this->assertArrayHasKey('results', $result);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->webhooks->retrieve('wh_doesnotexist000');
    }

    // ── Vérification de signature (offline) ──────────────────────────────────

    public function testConstructEventValidSignature(): void
    {
        $secret  = 'whsec_test_integration';
        $payload = json_encode(['event' => 'payment_intent.succeeded']);
        $ts      = time();
        $sig     = hash_hmac('sha256', "{$ts}.{$payload}", $secret);
        $header  = "t={$ts},v1={$sig}";

        $event = Webhooks::constructEvent($payload, $header, $secret);
        $this->assertSame('payment_intent.succeeded', $event['event']);
    }

    public function testConstructEventWrongSecretRaises(): void
    {
        $this->expectException(SanghoException::class);
        $payload = '{"event":"test"}';
        $ts      = time();
        $sig     = hash_hmac('sha256', "{$ts}.{$payload}", 'correct');
        Webhooks::constructEvent($payload, "t={$ts},v1={$sig}", 'wrong');
    }

    public function testConstructEventStaleTimestampRaises(): void
    {
        $this->expectException(SanghoException::class);
        $secret  = 'whsec_test';
        $payload = '{"event":"test"}';
        $oldTs   = time() - 600;
        $sig     = hash_hmac('sha256', "{$oldTs}.{$payload}", $secret);
        Webhooks::constructEvent($payload, "t={$oldTs},v1={$sig}", $secret, 300);
    }
}
