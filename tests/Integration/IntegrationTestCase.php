<?php
declare(strict_types=1);

namespace Sangho\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Sangho\SanghoClient;

/**
 * Classe de base pour tous les tests d'intégration Sangho PHP SDK.
 *
 * Variables d'environnement requises :
 *   SANGHO_TEST_SECRET_KEY   sk_test_xxx
 *   SANGHO_TEST_PUBLIC_KEY   pk_test_xxx
 *   SANGHO_API_BASE_URL      https://api.sangho.ga/v1 (optionnel)
 *
 * Lancement :
 *   SANGHO_TEST_SECRET_KEY=sk_test_xxx vendor/bin/phpunit --testsuite Integration
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static SanghoClient $client;
    protected static SanghoClient $pubClient;
    protected static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        $secretKey = getenv('SANGHO_TEST_SECRET_KEY');
        $publicKey = getenv('SANGHO_TEST_PUBLIC_KEY');

        if (!$secretKey) {
            self::markTestSkipped('SANGHO_TEST_SECRET_KEY not set — integration tests skipped.');
        }

        self::$baseUrl   = getenv('SANGHO_API_BASE_URL') ?: 'https://api.sangho.ga/v1';
        self::$client    = new SanghoClient($secretKey, self::$baseUrl);

        if ($publicKey) {
            self::$pubClient = new SanghoClient($publicKey, self::$baseUrl);
        }
    }

    /** Génère un email unique pour les tests. */
    protected function uniqueEmail(string $prefix = 'test'): string
    {
        return $prefix . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '@sangho-test.com';
    }

    /** Génère un nom unique. */
    protected function uniqueName(string $prefix = 'Test'): string
    {
        return $prefix . ' ' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}
