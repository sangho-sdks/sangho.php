<?php

declare(strict_types=1);

namespace Sangho\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sangho\Exception\SanghoException;
use Sangho\Exception\SanghoWebhookSignatureException;
use Sangho\Resource\Webhooks;
use Sangho\SanghoClient;

/** Signature des webhooks : erreurs typées, rotation, tolérance, corps brut (spéc. SDK-06). */
class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private function body(): string
    {
        return json_encode([
            'id' => 'evt_1', 'type' => 'kyc.updated', 'created' => 1780000100,
            'data' => ['object' => ['id' => 'acct_' . str_repeat('b', 32), 'status' => 'active', 'charges_enabled' => true, 'kyc_level' => 1]],
        ]);
    }

    private function sign(string $secret, int $ts, string $payload): string
    {
        return hash_hmac('sha256', "{$ts}.{$payload}", $secret);
    }

    private function reasonOf(string $header, ?string $payload = null, string|array $secret = self::SECRET, int $tolerance = 300): SanghoWebhookSignatureException
    {
        try {
            Webhooks::constructEvent($payload ?? $this->body(), $header, $secret, $tolerance);
        } catch (SanghoWebhookSignatureException $e) {
            return $e;
        }
        $this->fail('SanghoWebhookSignatureException attendue');
    }

    public function testSignatureValideRetourneLEvenementKyc(): void
    {
        $ts = time();
        $event = Webhooks::constructEvent($this->body(), "t={$ts},v1=" . $this->sign(self::SECRET, $ts, $this->body()), self::SECRET);
        $this->assertSame('kyc.updated', $event['type']);
        $this->assertTrue($event['data']['object']['charges_enabled']);
    }

    public function testExposeSurLeClientEtGenerateTestHeader(): void
    {
        $header = SanghoClient::generateTestHeader($this->body(), self::SECRET);
        $this->assertSame('kyc.updated', SanghoClient::constructEvent($this->body(), $header, self::SECRET)['type']);
        $this->assertMatchesRegularExpression('/^t=1780000000,v1=[0-9a-f]{64}$/', Webhooks::generateTestHeader('x', self::SECRET, 1780000000));
    }

    public function testCorpsUtf8(): void
    {
        $payload = json_encode(['type' => 'account.updated', 'data' => ['object' => ['business_name' => 'Café Épicé']]], JSON_UNESCAPED_UNICODE);
        $header = Webhooks::generateTestHeader($payload, self::SECRET);
        $this->assertSame('account.updated', Webhooks::constructEvent($payload, $header, self::SECRET)['type']);
    }

    public function testCorpsAltereMismatch401(): void
    {
        $ts = time();
        $e = $this->reasonOf("t={$ts},v1=" . $this->sign(self::SECRET, $ts, $this->body()), $this->body() . ' ');
        $this->assertSame('mismatch', $e->reason);
        $this->assertSame(401, $e->statusCode);
    }

    public function testMauvaisSecretMismatch(): void
    {
        $ts = time();
        $this->assertSame('mismatch', $this->reasonOf("t={$ts},v1=" . $this->sign('autre', $ts, $this->body()))->reason);
    }

    public function testHorodatageExpireOuFutur(): void
    {
        foreach ([time() - 3600, time() + 3600] as $ts) {
            $e = $this->reasonOf("t={$ts},v1=" . $this->sign(self::SECRET, $ts, $this->body()));
            $this->assertSame('expired', $e->reason);
            $this->assertSame(400, $e->statusCode);
            $this->assertSame('stale_event', $e->errorCode);
        }
    }

    public function testTolerancePersonnalisee(): void
    {
        $ts = time() - 600;
        $header = "t={$ts},v1=" . $this->sign(self::SECRET, $ts, $this->body());
        $this->reasonOf($header);
        $this->assertSame('kyc.updated', Webhooks::constructEvent($this->body(), $header, self::SECRET, 900)['type']);
    }

    /** @return array<string, array{string}> */
    public static function malformedHeaders(): array
    {
        return ['vide' => [''], 'texte' => ['nimportequoi'], 't non numérique' => ['t=abc,v1=deadbeef'], 'sans v1' => ['t=123'],
                'sans t' => ['v1=deadbeef'], 'vides' => ['t=,v1=']];
    }

    #[DataProvider('malformedHeaders')]
    public function testEnTeteMalForme(string $header): void
    {
        $this->assertSame('malformed', $this->reasonOf($header)->reason);
    }

    public function testRotationPlusieursV1(): void
    {
        $ts = time();
        $header = "t={$ts},v1=" . $this->sign('ancien-secret', $ts, $this->body()) . ',v1=' . $this->sign(self::SECRET, $ts, $this->body());
        $this->assertSame('kyc.updated', Webhooks::constructEvent($this->body(), $header, self::SECRET)['type']);
    }

    public function testRotationPlusieursSecrets(): void
    {
        $ts = time();
        $header = "t={$ts},v1=" . $this->sign('ancien-secret', $ts, $this->body());
        $this->assertSame('kyc.updated', Webhooks::constructEvent($this->body(), $header, [self::SECRET, 'ancien-secret'])['type']);
        $this->assertSame('mismatch', $this->reasonOf($header, null, [self::SECRET])->reason);
    }

    public function testCorpsNonJson(): void
    {
        $ts = time();
        $this->expectException(SanghoException::class);
        $this->expectExceptionMessage('valid JSON');
        Webhooks::constructEvent('pas du json', "t={$ts},v1=" . $this->sign(self::SECRET, $ts, 'pas du json'), self::SECRET);
    }

    public function testSousClasseDeSanghoException(): void
    {
        $this->assertInstanceOf(SanghoException::class, new SanghoWebhookSignatureException('mismatch', 'x'));
    }
}
