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
use Sangho\Exception\SanghoNotFoundException;
use Sangho\Exception\SanghoValidationException;
use Sangho\Exception\SanghoPublicKeyException;

class CustomersTest extends TestCase
{
    private function makeClient(array $responses): Customers
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $http    = new HttpClient('sk_test_abc123456789', 'https://api.sangho.ga/v1');

        // Inject mock via reflection
        $ref  = new \ReflectionProperty(HttpClient::class, 'guzzle');
        $ref->setAccessible(true);
        $ref->setValue($http, new Client(['handler' => $handler, 'http_errors' => false]));

        return new Customers($http);
    }

    public function testListCustomers(): void
    {
        $customers = $this->makeClient([
            new Response(200, [], json_encode([
                'count' => 1, 'next' => null, 'previous' => null,
                'results' => [['id' => 'cust_1', 'email' => 'a@b.com']]
            ]))
        ]);

        $result = $customers->list();
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('a@b.com', $result['results'][0]['email']);
    }

    public function testRetrieveCustomer(): void
    {
        $customers = $this->makeClient([
            new Response(200, [], json_encode(['id' => 'cust_1', 'email' => 'a@b.com']))
        ]);

        $customer = $customers->retrieve('cust_1');
        $this->assertEquals('cust_1', $customer['id']);
    }

    public function testPublicKeyThrows(): void
    {
        $this->expectException(SanghoPublicKeyException::class);
        $http = new HttpClient('pk_test_abc123456789');
        $res  = new Customers($http);
        $res->list();
    }
}
