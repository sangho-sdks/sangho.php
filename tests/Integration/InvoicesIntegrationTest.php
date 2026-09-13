<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;

/**
 * @group integration
 */
class InvoicesIntegrationTest extends IntegrationTestCase
{
    private static ?array $sharedCustomer = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$sharedCustomer = self::$client->customers->create([
            'email' => 'inv-' . substr(bin2hex(random_bytes(4)), 0, 8) . '@sangho-test.com',
            'name'  => 'Invoice Integration Customer',
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

    public function testCreateInvoice(): void
    {
        $invoice = self::$client->invoices->create([
            'customer' => self::$sharedCustomer['id'],
            'amount'   => 10000,
        ]);

        $this->assertNotEmpty($invoice['id']);
        $this->assertSame(10000, $invoice['amount']);
        $this->assertSame(self::$sharedCustomer['id'], $invoice['customer']);

        self::$client->invoices->delete($invoice['id']);
    }

    public function testRetrieveInvoice(): void
    {
        $invoice   = self::$client->invoices->create(['customer' => self::$sharedCustomer['id'], 'amount' => 5000]);
        $retrieved = self::$client->invoices->retrieve($invoice['id']);

        $this->assertSame($invoice['id'], $retrieved['id']);

        self::$client->invoices->delete($retrieved['id']);
    }

    public function testListInvoices(): void
    {
        $result = self::$client->invoices->list(['page_size' => 5]);

        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['results']);
    }

    public function testFinalizeAndVoidInvoice(): void
    {
        $invoice   = self::$client->invoices->create(['customer' => self::$sharedCustomer['id'], 'amount' => 3000]);
        $finalized = self::$client->invoices->finalize($invoice['id']);
        $this->assertContains($finalized['status'] ?? '', ['open', 'finalized']);

        $voided = self::$client->invoices->void($finalized['id']);
        $this->assertSame('void', $voided['status'] ?? 'void');
    }

    public function testDeleteDraftInvoice(): void
    {
        $invoice = self::$client->invoices->create(['customer' => self::$sharedCustomer['id'], 'amount' => 1500]);
        self::$client->invoices->delete($invoice['id']);

        $this->expectException(SanghoNotFoundException::class);
        self::$client->invoices->retrieve($invoice['id']);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->invoices->retrieve('inv_doesnotexist000');
    }
}
