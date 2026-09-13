<?php

declare(strict_types=1);

namespace Sangho\Tests\Integration;

use Sangho\Exception\SanghoNotFoundException;

/**
 * @group integration
 */
class SubscriptionsIntegrationTest extends IntegrationTestCase
{
    public function testListSubscriptions(): void
    {
        $result = self::$client->subscriptions->list(['page_size' => 5]);

        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['results']);
    }

    public function testRetrieveNonexistentThrowsNotFound(): void
    {
        $this->expectException(SanghoNotFoundException::class);
        self::$client->subscriptions->retrieve('sub_doesnotexist000');
    }

    public function testOptionsReturnsSchema(): void
    {
        $schema = self::$client->subscriptions->options();
        $this->assertIsArray($schema);
    }
}
