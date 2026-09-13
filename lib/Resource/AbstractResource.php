<?php

declare(strict_types=1);

namespace Sangho\Resource;

use Sangho\HttpClient;

abstract class AbstractResource
{
    public function __construct(protected HttpClient $http)
    {
    }
}
