<?php

declare(strict_types=1);

namespace Sangho\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sangho\HttpClient;
use Sangho\Sangho;

/**
 * Alignement avec l'API Sangho réelle (backend/api) : chaque test correspond à une route du backend ; les méthodes
 * retirées appelaient des routes inexistantes (404/405) et ne doivent pas revenir.
 */
final class ApiAlignmentTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function http(array $responses): HttpClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $http = new HttpClient('sk_test_abc123456789', 'https://api.sangho.ga/v1', 30, 0);
        $prop = new \ReflectionProperty(HttpClient::class, 'guzzle');
        $prop->setValue($http, new Client(['handler' => $stack, 'http_errors' => false, 'base_uri' => 'https://api.sangho.ga/v1/']));
        return $http;
    }

    private function json(array $body, int $status = 200): Response
    {
        return new Response($status, [], json_encode($body));
    }

    private function lastCall(): string
    {
        $r = $this->history[count($this->history) - 1]['request'];
        return $r->getMethod() . ' ' . $r->getUri()->getPath() . ($r->getUri()->getQuery() ? '?' . $r->getUri()->getQuery() : '');
    }

    public function testSubscriptionsReactivate(): void
    {
        $r = new \Sangho\Resource\Subscriptions($this->http([$this->json(['id' => 'sub_1'])]));
        $this->assertSame('sub_1', $r->reactivate('sub_1')['id']);
        $this->assertSame('POST /v1/subscriptions/sub_1/reactivate/', $this->lastCall());
    }

    public function testReceiptsPdfUrl(): void
    {
        $r = new \Sangho\Resource\Receipts($this->http([$this->json(['url' => 'https://x/y.pdf', 'expires_at' => '2026-01-01T00:00:00Z'])]));
        $this->assertStringEndsWith('.pdf', $r->getPdfUrl('rcp_1')['url']);
        $this->assertSame('GET /v1/receipts/rcp_1/pdf/', $this->lastCall());
    }

    public function testCustomerPaymentMethodsUseTheCustomerFilter(): void
    {
        $r = new \Sangho\Resource\Customers($this->http([$this->json(['count' => 0, 'data' => []])]));
        $this->assertSame([], $r->listPaymentMethods('cus_1')['data']);
        $this->assertSame('GET /v1/payment-methods/?customer=cus_1', $this->lastCall());
    }

    public function testPaymentIntentsDeleteReturnsTheCanceledIntent(): void
    {
        $r = new \Sangho\Resource\PaymentIntents($this->http([$this->json(['id' => 'pi_1', 'status' => 'canceled'])]));
        $this->assertSame('canceled', $r->delete('pi_1')['status']);
        $this->assertSame('DELETE /v1/payment-intents/pi_1/', $this->lastCall());
    }

    public function testServerErrorsAreRetried(): void
    {
        $http = new HttpClient('sk_test_abc123456789', 'https://api.sangho.ga/v1', 30, 1);
        $stack = HandlerStack::create(new MockHandler([$this->json(['message' => 'boom'], 503), $this->json(['id' => 'ok'])]));
        (new \ReflectionProperty(HttpClient::class, 'guzzle'))->setValue($http, new Client(['handler' => $stack, 'http_errors' => false, 'base_uri' => 'https://api.sangho.ga/v1/']));
        $this->assertSame('ok', $http->get('/products/x/')['id']);
    }

    /** @return array<string, array{string, string}> */
    public static function phantoms(): array
    {
        return [
            'apps.rollSecret' => ['Apps', 'rollSecret'], 'customers.listTransactions' => ['Customers', 'listTransactions'],
            'invoices.finalize' => ['Invoices', 'finalize'], 'partners.create' => ['Partners', 'create'],
            'partners.update' => ['Partners', 'update'], 'partners.delete' => ['Partners', 'delete'],
            'paymentMethods.create' => ['PaymentMethods', 'create'], 'paymentMethods.update' => ['PaymentMethods', 'update'],
            'paymentMethods.delete' => ['PaymentMethods', 'delete'], 'products.archive' => ['Products', 'archive'],
            'products.restore' => ['Products', 'restore'], 'receipts.send' => ['Receipts', 'send'],
            'refunds.update' => ['Refunds', 'update'], 'security.rollSecretKey' => ['Security', 'rollSecretKey'],
            'security.listSessions' => ['Security', 'listSessions'], 'security.revokeSession' => ['Security', 'revokeSession'],
        ];
    }

    #[DataProvider('phantoms')]
    public function testPhantomEndpointsAreGone(string $class, string $method): void
    {
        $this->assertFalse(method_exists("Sangho\\Resource\\{$class}", $method));
    }
}
