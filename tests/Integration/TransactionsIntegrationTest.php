<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;

/**
 * @group integration
 */
class TransactionsIntegrationTest extends IntegrationTestCase
{
    public function testListTransactions(): void
    {
        $result = self::$client->transactions->list(['page_size' => 5]);

        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['results']);
        $this->assertLessThanOrEqual(5, count($result['results']));
    }

    public function testListTransactionsOrderedByDate(): void
    {
        $result = self::$client->transactions->list([
            'ordering'  => '-created_at',
            'page_size' => 10,
        ]);

        $this->assertArrayHasKey('results', $result);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->transactions->retrieve('trans_doesnotexist000');
    }

    public function testOptionsReturnsSchema(): void
    {
        $schema = self::$client->transactions->options();
        $this->assertIsArray($schema);
    }
}
