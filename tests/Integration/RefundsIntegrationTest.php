<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;

/**
 * @group integration
 */
class RefundsIntegrationTest extends IntegrationTestCase
{
    public function testListRefunds(): void
    {
        $result = self::$client->refunds->list(['page_size' => 5]);

        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['results']);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->refunds->retrieve('ref_doesnotexist000');
    }

    public function testOptionsReturnsSchema(): void
    {
        $schema = self::$client->refunds->options();
        $this->assertIsArray($schema);
    }
}
