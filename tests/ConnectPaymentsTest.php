<?php

declare(strict_types=1);

namespace Sangho\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Sangho\Exception\SanghoPublicKeyException;
use Sangho\Exception\SanghoValidationException;
use Sangho\HttpClient;
use Sangho\SanghoClient;

/** Paiements Connect (séquestre) : release / refund / freeze, soldes et retraits, sans réseau. */
class ConnectPaymentsTest extends TestCase
{
    private const PAY = 'cpay_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    private const ACCT = 'acct_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    /** @param list<Response> $responses */
    private function makeClient(array $responses, string $key = 'sk_test_abc123456789'): SanghoClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $client = new SanghoClient($key, 'https://api.sangho.ga/v1', 30, 1);
        $http = (new \ReflectionProperty($client->connect->accounts, 'http'))->getValue($client->connect->accounts);
        (new \ReflectionProperty(HttpClient::class, 'guzzle'))->setValue($http, new Client(['handler' => $stack, 'http_errors' => false]));
        return $client;
    }

    private function lastBody(): array
    {
        return json_decode((string) end($this->history)['request']->getBody(), true);
    }

    public function testReleaseEnvoieLaCleDIdempotence(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode(['status' => 'released']))]);
        $client->connect->payments->release(self::PAY, 'release-ORDER-1');
        $request = end($this->history)['request'];
        $this->assertSame('release-ORDER-1', $request->getHeaderLine('Idempotency-Key'));
        $this->assertStringEndsWith('connect/payments/' . self::PAY . '/release/', (string) $request->getUri());
    }

    public function testReleaseSansCleEstRefuseSansAppelReseau(): void
    {
        $client = $this->makeClient([]);
        $this->expectException(SanghoValidationException::class);
        $client->connect->payments->release(self::PAY, '');
    }

    public function testRefundScopeMontantEtMotif(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode(['status' => 'partially_refunded']))]);
        $client->connect->payments->refund(self::PAY, 'amount', 'refund-1', 1500, 'geste');
        $this->assertSame(['scope' => 'amount', 'amount' => 1500, 'reason' => 'geste'], $this->lastBody());
    }

    public function testRefundScopeInvalide(): void
    {
        $client = $this->makeClient([]);
        $this->expectException(SanghoValidationException::class);
        $client->connect->payments->refund(self::PAY, 'tout', 'refund-1');
    }

    public function testFreezeUnfreezeEtSimulation(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['status' => 'frozen'])),
            new Response(200, [], json_encode(['status' => 'held'])),
            new Response(200, [], json_encode(['status' => 'held'])),
        ]);
        $this->assertSame('frozen', $client->connect->payments->freeze(self::PAY)['status']);
        $this->assertSame('held', $client->connect->payments->unfreeze(self::PAY)['status']);
        $client->connect->payments->simulatePayment(self::PAY);
        $this->assertStringEndsWith('simulate-payment/', (string) end($this->history)['request']->getUri());
    }

    public function testSoldeEtRetraits(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['available' => '2000.00', 'held' => '25000.00'])),
            new Response(201, [], json_encode(['id' => 'cpo_x', 'status' => 'pending'])),
            new Response(200, [], json_encode(['object' => 'list', 'data' => []])),
        ]);
        $this->assertSame('25000.00', $client->connect->accounts->balance(self::ACCT)['held']);
        $client->connect->accounts->createPayout(self::ACCT, 1000, 'mobile:077000000', 'payout-1');
        $this->assertSame(['amount' => 1000, 'destination' => 'mobile:077000000'], $this->lastBody());
        $this->assertSame([], $client->connect->accounts->listPayouts(self::ACCT)['data']);
    }

    public function testCleSecreteObligatoire(): void
    {
        $client = new SanghoClient('pk_test_abc123456789');
        $this->expectException(SanghoPublicKeyException::class);
        $client->connect->payments->retrieve(self::PAY);
    }
}
