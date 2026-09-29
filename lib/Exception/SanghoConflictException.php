<?php

declare(strict_types=1);

namespace Sangho\Exception;

/**
 * 409 — Conflit d'état métier autre qu'une clé d'idempotence (ex : `account_not_claimed`, `account_disabled`).
 * Le code précis est dans `errorCode`.
 */
class SanghoConflictException extends SanghoException
{
}
