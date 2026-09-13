<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;
use Sangho\Exception\SanghoValidationException;
use Sangho\Exception\SanghoPublicKeyException;
use Sangho\Tests\Integration\IntegrationTestCase;

/**
 * @group integration
 */
class CustomersIntegrationTest extends IntegrationTestCase
{
    private static ?array $sharedCustomer = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Crée un client partagé pour tous les tests du groupe
        self::$sharedCustomer = self::$client->customers->create([
            'email' => 'shared-' . substr(bin2hex(random_bytes(4)), 0, 8) . '@sangho-test.com',
            'name'  => 'Shared Integration Customer',
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

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function testCreateCustomer(): void
    {
        $email    = $this->uniqueEmail('create');
        $customer = self::$client->customers->create([
            'email' => $email,
            'name'  => 'Jean Ondo',
            'phone' => '+24177000001',
        ]);

        $this->assertNotEmpty($customer['id']);
        $this->assertSame($email, $customer['email']);
        $this->assertSame('Jean Ondo', $customer['name']);
        $this->assertArrayHasKey('created_at', $customer);
        $this->assertArrayHasKey('status', $customer);

        self::$client->customers->delete($customer['id']);
    }

    public function testRetrieveCustomer(): void
    {
        $retrieved = self::$client->customers->retrieve(self::$sharedCustomer['id']);

        $this->assertSame(self::$sharedCustomer['id'], $retrieved['id']);
        $this->assertSame(self::$sharedCustomer['email'], $retrieved['email']);
    }

    public function testListCustomersReturnsPaginated(): void
    {
        $result = self::$client->customers->list(['page_size' => 5]);

        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('next', $result);
        $this->assertIsArray($result['results']);
        $this->assertLessThanOrEqual(5, count($result['results']));
    }

    public function testListCustomersFilterByStatus(): void
    {
        $result = self::$client->customers->list(['status' => 'active', 'page_size' => 10]);

        foreach ($result['results'] as $customer) {
            $this->assertSame('active', $customer['status']);
        }
    }

    public function testListCustomersSearch(): void
    {
        $result = self::$client->customers->list(['search' => self::$sharedCustomer['email']]);
        $ids    = array_column($result['results'], 'id');

        $this->assertContains(self::$sharedCustomer['id'], $ids);
    }

    public function testUpdateCustomer(): void
    {
        $updated = self::$client->customers->update(self::$sharedCustomer['id'], [
            'phone' => '+24177888888',
        ]);

        $this->assertSame(self::$sharedCustomer['id'], $updated['id']);
        $this->assertSame('+24177888888', $updated['phone']);
    }

    public function testDeleteCustomerReturnsNull(): void
    {
        $customer = self::$client->customers->create([
            'email' => $this->uniqueEmail('del'),
            'name'  => 'À supprimer',
        ]);

        self::$client->customers->delete($customer['id']);

        $this->expectException(SanghoNotFoundException::class);
        self::$client->customers->retrieve($customer['id']);
    }

    public function testListTransactions(): void
    {
        $result = self::$client->customers->listTransactions(self::$sharedCustomer['id']);

        $this->assertArrayHasKey('results', $result);
        $this->assertIsArray($result['results']);
    }

    // ── Erreurs ───────────────────────────────────────────────────────────────

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->customers->retrieve('cust_doesnotexist000');
    }

    public function testCreateDuplicateEmailThrowsValidation(): void
    {
        $this->expectException(SanghoValidationException::class);
        self::$client->customers->create([
            'email' => self::$sharedCustomer['email'],
            'name'  => 'Duplicate',
        ]);
    }

    public function testCreateInvalidEmailThrowsValidation(): void
    {
        try {
            self::$client->customers->create(['email' => 'bad-email', 'name' => 'X']);
            $this->fail('SanghoValidationException expected');
        } catch (SanghoValidationException $e) {
            $this->assertArrayHasKey('email', $e->getFieldErrors());
        }
    }

    public function testPublicKeyCannotList(): void
    {
        if (!isset(self::$pubClient)) {
            $this->markTestSkipped('SANGHO_TEST_PUBLIC_KEY not set.');
        }
        $this->expectException(SanghoPublicKeyException::class);
        self::$pubClient->customers->list();
    }

    public function testOptionsReturnsSchema(): void
    {
        $schema = self::$client->customers->options();
        $this->assertIsArray($schema);
        $this->assertTrue(isset($schema['name']) || isset($schema['actions']));
    }
}
