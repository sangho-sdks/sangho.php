<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;

/**
 * @group integration
 */
class PaymentIntentsIntegrationTest extends IntegrationTestCase
{
    private static ?array $sharedCustomer = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$sharedCustomer = self::$client->customers->create([
            'email' => 'pi-test-' . substr(bin2hex(random_bytes(4)), 0, 8) . '@sangho-test.com',
            'name'  => 'PI Integration Customer',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$sharedCustomer) {
            try {
                self::$client->customers->delete(self::$sharedCustomer['id']);
            } catch (\Throwable) {
            }
        }
    }

    public function testCreatePaymentIntent(): void
    {
        $intent = self::$client->paymentIntents->create([
            'amount'      => 10000,
            'customer'    => self::$sharedCustomer['id'],
            'description' => 'Integration test',
        ]);

        $this->assertNotEmpty($intent['id']);
        $this->assertSame(10000, $intent['amount']);
        $this->assertSame(self::$sharedCustomer['id'], $intent['customer']);
        $this->assertArrayHasKey('status', $intent);

        self::$client->paymentIntents->cancel($intent['id']);
    }

    public function testRetrievePaymentIntent(): void
    {
        $intent    = self::$client->paymentIntents->create([
            'amount'   => 5000,
            'customer' => self::$sharedCustomer['id'],
        ]);
        $retrieved = self::$client->paymentIntents->retrieve($intent['id']);

        $this->assertSame($intent['id'], $retrieved['id']);
        $this->assertSame(5000, $retrieved['amount']);

        self::$client->paymentIntents->cancel($intent['id']);
    }

    public function testListPaymentIntents(): void
    {
        $result = self::$client->paymentIntents->list(['page_size' => 5]);
        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['results']);
    }

    public function testCancelPaymentIntent(): void
    {
        $intent   = self::$client->paymentIntents->create([
            'amount'   => 2500,
            'customer' => self::$sharedCustomer['id'],
        ]);
        $canceled = self::$client->paymentIntents->cancel($intent['id']);

        $this->assertSame('canceled', $canceled['status']);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->paymentIntents->retrieve('pay_doesnotexist000');
    }
}
