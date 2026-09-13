<?php

declare(strict_types=1);

namespace Sangho\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Sangho\HttpClient;
use Sangho\Resource\Customers;
use Sangho\Exception\SanghoPublicKeyException;
use Sangho\Exception\SanghoRateLimitException;

class HttpClientTest extends TestCase
{
    private function makeClient(
        array $responses,
        string $apiKey = 'sk_test_abc123456789',
        int $maxRetries = 0
    ): Customers {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http    = new HttpClient($apiKey, 'https://api.sangho.ga/v1', 30, $maxRetries);

        $ref = new \ReflectionProperty(HttpClient::class, 'guzzle');
        $ref->setAccessible(true);
        $ref->setValue($http, new Client(['handler' => $handler, 'http_errors' => false]));

        return new Customers($http);
    }

    public function testRateLimitReadsRetryAfterField(): void
    {
        $customers = $this->makeClient([
            new Response(429, [], json_encode(['message' => 'Rate limit', 'retry_after' => 30])),
        ]);

        try {
            $customers->list();
            $this->fail('Expected SanghoRateLimitException');
        } catch (SanghoRateLimitException $e) {
            $this->assertSame(30, $e->retryAfter);
        }
    }

    public function testPublicKeyErrorCaseInsensitive(): void
    {
        $customers = $this->makeClient([
            new Response(403, [], json_encode(['message' => 'nope', 'code' => 'PUBLIC_KEY_NOT_ALLOWED'])),
        ]);

        $this->expectException(SanghoPublicKeyException::class);
        $customers->retrieve('x');
    }

    public function testErrorExposesType(): void
    {
        $customers = $this->makeClient([
            new Response(401, [], json_encode(['message' => 'bad key'])),
        ]);

        try {
            $customers->list();
            $this->fail('Expected exception');
        } catch (\Sangho\Exception\SanghoAuthException $e) {
            $this->assertSame('AUTHENTICATION_ERROR', $e->type);
        }
    }

    public function testInvalidKeyPrefixRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpClient('pk_live_' . str_repeat('x', 20));
    }

    public function testNonHttpsBaseUrlRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpClient('sk_test_' . str_repeat('x', 20), 'http://api.sangho.ga/v1');
    }

    public function testLocalhostHttpAllowed(): void
    {
        $http = new HttpClient('sk_test_' . str_repeat('x', 20), 'http://localhost:8000/v1');
        $this->assertSame('secret', $http->keyType);
    }
}
