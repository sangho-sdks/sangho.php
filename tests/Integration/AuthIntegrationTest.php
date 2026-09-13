<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoAuthException;
use Sangho\Exception\SanghoPublicKeyException;
use Sangho\SanghoClient;

/**
 * @group integration
 */
class AuthIntegrationTest extends IntegrationTestCase
{
    public function testValidSecretKeyAuthenticates(): void
    {
        $result = self::$client->customers->list(['page_size' => 1]);
        $this->assertArrayHasKey('results', $result);
    }

    public function testInvalidKeyRaisesAuthError(): void
    {
        $this->expectException(SanghoAuthException::class);
        $bad = new SanghoClient('sk_test_invalidkeyXXXXXXXXXXXX', self::$baseUrl);
        $bad->customers->list();
    }

    public function testPublicKeyBlockedOnWrite(): void
    {
        if (!isset(self::$pubClient)) {
            $this->markTestSkipped('SANGHO_TEST_PUBLIC_KEY not set.');
        }
        $this->expectException(SanghoPublicKeyException::class);
        self::$pubClient->customers->list();
    }

    public function testInvalidPrefixThrowsInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SanghoClient('bad_key_no_prefix');
    }

    public function testAllValidPrefixesAccepted(): void
    {
        foreach (['sk_prod_', 'sk_test_', 'pk_prod_', 'pk_test_'] as $prefix) {
            $client = new SanghoClient($prefix . str_repeat('x', 20), self::$baseUrl);
            $this->assertInstanceOf(SanghoClient::class, $client);
        }
    }
}
