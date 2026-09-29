<?php

declare(strict_types=1);

namespace Sangho\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Sangho\Exception\SanghoConflictException;
use Sangho\Exception\SanghoIdempotencyException;
use Sangho\Exception\SanghoNotFoundException;
use Sangho\Exception\SanghoPublicKeyException;
use Sangho\Exception\SanghoTimeoutException;
use Sangho\Exception\SanghoValidationException;
use Sangho\HttpClient;
use Sangho\SanghoClient;

/** Marketplace / Connect, clé d'idempotence exposée et non-rejeu d'un POST après un timeout. */
class ConnectTest extends TestCase
{
    private const ACCOUNT_ID = 'acct_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /** @var list<array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function account(array $extra = []): array
    {
        return $extra + [
            'id' => self::ACCOUNT_ID, 'object' => 'account', 'external_id' => 'seller-1', 'email' => 'ada@example.com',
            'business_name' => 'Boutique Ada', 'status' => 'pending_claim', 'charges_enabled' => false,
            'payouts_enabled' => false, 'kyc_level' => 0, 'livemode' => false, 'created' => 1780000000,
        ];
    }

    /** @param list<Response|\Throwable> $responses */
    private function makeClient(array $responses, string $key = 'sk_test_abc123456789', int $maxRetries = 2): SanghoClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $client = new SanghoClient($key, 'https://api.sangho.ga/v1', 30, $maxRetries);
        $http = (new \ReflectionProperty($client->connect->accounts, 'http'))->getValue($client->connect->accounts);
        (new \ReflectionProperty(HttpClient::class, 'guzzle'))->setValue($http, new Client(['handler' => $stack, 'http_errors' => false]));
        // toutes les ressources partagent le même HttpClient
        return $client;
    }

    private function lastBody(): array
    {
        $request = end($this->history)['request'];
        return json_decode((string) $request->getBody(), true);
    }

    private function lastHeader(string $name): string
    {
        return end($this->history)['request']->getHeaderLine($name);
    }

    public function testCreateAccountEnvoieLeCorpsEtRetourneLeClaimToken(): void
    {
        $client = $this->makeClient([new Response(201, [], json_encode($this->account(['claim_token' => 'tok_xxx'])))]);
        $account = $client->connect->accounts->create(
            ['external_id' => 'seller-1', 'email' => 'ada@example.com', 'business_name' => 'Boutique Ada']
        );
        $this->assertSame(['external_id' => 'seller-1', 'email' => 'ada@example.com', 'business_name' => 'Boutique Ada'], $this->lastBody());
        $this->assertSame('tok_xxx', $account['claim_token']);
        $this->assertStringEndsWith('connect/accounts/', (string) end($this->history)['request']->getUri());
    }

    public function testCleIdempotenceFournieEnArgument(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->account()))]);
        $client->connect->accounts->create(['external_id' => 'seller-1', 'email' => 'ada@example.com'], 'connect-account-seller-1');
        $this->assertSame('connect-account-seller-1', $this->lastHeader('Idempotency-Key'));
    }

    public function testCleIdempotenceDansLeCorpsEstRetireeDuJson(): void
    {
        $client = $this->makeClient([new Response(201, [], json_encode(['id' => 'pi_1']))]);
        $client->paymentIntents->create(['amount' => 5000, 'currency' => 'XAF', 'idempotency_key' => 'order-42']);
        $this->assertSame('order-42', $this->lastHeader('Idempotency-Key'));
        $this->assertArrayNotHasKey('idempotency_key', $this->lastBody());
    }

    public function testCreationsExistantesAcceptentUneCle(): void
    {
        $client = $this->makeClient([new Response(201, [], json_encode(['id' => 'cs_1']))]);
        $client->checkoutSessions->create(['line_items' => [], 'success_url' => 'https://a.b/'], 'cs-42');
        $this->assertSame('cs-42', $this->lastHeader('Idempotency-Key'));
    }

    public function testSansCleUneCleEstGeneree(): void
    {
        $client = $this->makeClient([new Response(200, [], json_encode($this->account()))]);
        $client->connect->accounts->create(['external_id' => 'seller-1', 'email' => 'ada@example.com']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $this->lastHeader('Idempotency-Key'));
    }

    public function testRetrieveListReissue(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode($this->account(['status' => 'active', 'charges_enabled' => true, 'kyc_level' => 1]))),
            new Response(200, [], json_encode(['object' => 'list', 'data' => [$this->account()]])),
            new Response(200, [], json_encode(['id' => self::ACCOUNT_ID, 'claim_token' => 'tok_new'])),
        ]);
        $this->assertTrue($client->connect->accounts->retrieve(self::ACCOUNT_ID)['charges_enabled']);
        $this->assertCount(1, $client->connect->accounts->list()['data']);
        $this->assertSame('tok_new', $client->connect->accounts->reissueClaimToken(self::ACCOUNT_ID)['claim_token']);
        $this->assertStringEndsWith('/claim-token/', (string) end($this->history)['request']->getUri());
    }

    public function testCreateKycSession(): void
    {
        $session = ['object' => 'kyc_session', 'url' => 'https://dash.sangho.ga/connect/kyc/?session=abc', 'expires_at' => 1780003600, 'account' => self::ACCOUNT_ID];
        $client = $this->makeClient([new Response(201, [], json_encode($session))]);
        $result = $client->connect->accounts->createKycSession(self::ACCOUNT_ID, 'https://evangzat.com/wallet/', 'https://evangzat.com/wallet/');
        $this->assertSame(['return_url' => 'https://evangzat.com/wallet/', 'refresh_url' => 'https://evangzat.com/wallet/'], $this->lastBody());
        $this->assertStringEndsWith('/kyc-session/', (string) end($this->history)['request']->getUri());
        $this->assertStringContainsString('/connect/kyc/', $result['url']);
    }

    public function testKycSessionCompteNonReclameEstUnConflitMetier(): void
    {
        $client = $this->makeClient([
            new Response(409, [], json_encode(['error' => ['code' => 'account_not_claimed', 'message' => 'Compte non réclamé.']])),
        ]);
        try {
            $client->connect->accounts->createKycSession(self::ACCOUNT_ID, 'https://evangzat.com/wallet/');
            $this->fail('Exception attendue');
        } catch (SanghoConflictException $e) {
            $this->assertSame('account_not_claimed', $e->errorCode);
            $this->assertSame(409, $e->statusCode);
            $this->assertNotInstanceOf(SanghoIdempotencyException::class, $e);
        }
    }

    public function testErreurs404Et422DuFormatImbrique(): void
    {
        $client = $this->makeClient([
            new Response(404, [], json_encode(['error' => ['code' => 'resource_missing', 'message' => 'Compte introuvable.']])),
            new Response(422, [], json_encode(['code' => 'invalid_url', 'message' => 'https requis'])),
        ]);
        try {
            $client->connect->accounts->createKycSession(self::ACCOUNT_ID, 'https://x.y/');
            $this->fail('Exception attendue');
        } catch (SanghoNotFoundException $e) {
            $this->assertSame('resource_missing', $e->errorCode);
        }
        $this->expectException(SanghoValidationException::class);
        $client->connect->accounts->createKycSession(self::ACCOUNT_ID, 'http://x.y/');
    }

    public function test409SansCodeResteUneErreurDIdempotence(): void
    {
        $client = $this->makeClient([new Response(409, [], json_encode(['message' => 'Conflict']))]);
        $this->expectException(SanghoIdempotencyException::class);
        $client->connect->accounts->create(['external_id' => 'x', 'email' => 'a@b.c']);
    }

    public function testCleSecreteExigee(): void
    {
        $client = new SanghoClient('pk_test_abc123456789');
        $this->expectException(SanghoPublicKeyException::class);
        $client->connect->accounts->list();
    }

    public function testPostNonRejoueApresTimeoutSansCle(): void
    {
        $timeout = new ConnectException('cURL error 28: Operation timed out', new Request('POST', 'x'));
        $client = $this->makeClient([$timeout, $timeout, $timeout]);
        try {
            $client->connect->accounts->create(['external_id' => 'x', 'email' => 'a@b.c']);
            $this->fail('Exception attendue');
        } catch (SanghoTimeoutException) {
            $this->assertCount(1, $this->history);
        }
    }

    public function testPostRejoueApresTimeoutAvecCleFournie(): void
    {
        $timeout = new ConnectException('cURL error 28: Operation timed out', new Request('POST', 'x'));
        $client = $this->makeClient([$timeout, new Response(200, [], json_encode($this->account()))]);
        $account = $client->connect->accounts->create(['external_id' => 'x', 'email' => 'a@b.c'], 'k-1');
        $this->assertSame(self::ACCOUNT_ID, $account['id']);
        $this->assertCount(2, $this->history);
    }
}
