<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;

/**
 * @group integration
 */
class ProductsIntegrationTest extends IntegrationTestCase
{
    private static ?array $sharedProduct = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$sharedProduct = self::$client->products->create([
            'name'        => 'Shared Product ' . substr(bin2hex(random_bytes(3)), 0, 6),
            'price'       => 5000,
            'description' => 'Integration test product',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$sharedProduct) {
            try {
                self::$client->products->delete(self::$sharedProduct['id']);
            } catch (\Throwable) {
            }
        }
    }

    public function testCreateProduct(): void
    {
        $product = self::$client->products->create([
            'name'  => $this->uniqueName('Product'),
            'price' => 12000,
        ]);

        $this->assertNotEmpty($product['id']);
        $this->assertSame(12000, $product['price']);
        $this->assertArrayHasKey('created_at', $product);

        self::$client->products->delete($product['id']);
    }

    public function testRetrieveProduct(): void
    {
        $retrieved = self::$client->products->retrieve(self::$sharedProduct['id']);
        $this->assertSame(self::$sharedProduct['id'], $retrieved['id']);
        $this->assertSame(self::$sharedProduct['name'], $retrieved['name']);
    }

    public function testListProducts(): void
    {
        $result = self::$client->products->list(['page_size' => 5]);

        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertIsArray($result['results']);
        $this->assertLessThanOrEqual(5, count($result['results']));
    }

    public function testUpdateProduct(): void
    {
        $updated = self::$client->products->update(self::$sharedProduct['id'], ['price' => 9999]);
        $this->assertSame(9999, $updated['price']);
    }

    public function testArchiveAndRestore(): void
    {
        $product  = self::$client->products->create(['name' => $this->uniqueName('Archive'), 'price' => 1000]);
        $archived = self::$client->products->archive($product['id']);
        $this->assertContains($archived['status'] ?? '', ['archived', 'inactive']);

        $restored = self::$client->products->restore($product['id']);
        $this->assertSame('active', $restored['status'] ?? 'active');

        self::$client->products->delete($product['id']);
    }

    public function testDeleteProduct(): void
    {
        $product = self::$client->products->create(['name' => $this->uniqueName('Del'), 'price' => 500]);
        self::$client->products->delete($product['id']);

        $this->expectException(SanghoNotFoundException::class);
        self::$client->products->retrieve($product['id']);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->products->retrieve('prod_doesnotexist000');
    }

    public function testOptionsReturnsSchema(): void
    {
        $schema = self::$client->products->options();
        $this->assertIsArray($schema);
    }
}
